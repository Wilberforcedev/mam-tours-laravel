<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\PaymentService;
use App\Services\NotificationService;
use App\Services\WebhookService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    private PaymentService $paymentService;
    private NotificationService $notificationService;
    private WebhookService $webhookService;

    public function __construct(PaymentService $paymentService, NotificationService $notificationService, WebhookService $webhookService)
    {
        $this->paymentService = $paymentService;
        $this->notificationService = $notificationService;
        $this->webhookService = $webhookService;
    }

    /**
     * Show payment page for a booking
     */
    public function show(Booking $booking)
    {
        // Ensure user can only pay for their own bookings
        if ($booking->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to booking');
        }

        if ($booking->payment_status === 'completed') {
            return redirect()->route('bookings.show', $booking)
                ->with('info', 'This booking has already been paid for.');
        }

        return view('payments.show', compact('booking'));
    }

    /**
     * Process Stripe payment
     */
    public function processStripe(Request $request, Booking $booking)
    {
        $validator = Validator::make($request->all(), [
            'payment_method_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($booking->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized access to booking',
            ], 403);
        }

        $result = $this->paymentService->createStripePayment($booking);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'client_secret' => $result['client_secret'],
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error'],
        ], 400);
    }

    /**
     * Confirm Stripe payment
     */
    public function confirmStripe(Request $request, Booking $booking)
    {
        $validator = Validator::make($request->all(), [
            'payment_intent_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($booking->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized access to booking',
            ], 403);
        }

        $success = $this->paymentService->confirmStripePayment(
            $request->payment_intent_id,
            $booking
        );

        if ($success) {
            // Send payment confirmation notification
            $this->notificationService->sendPaymentConfirmation($booking);

            return response()->json([
                'success' => true,
                'message' => 'Payment completed successfully!',
                'redirect_url' => route('bookings.show', $booking),
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => 'Payment confirmation failed',
        ], 400);
    }

    /**
     * Process mobile money payment
     */
    public function processMobileMoney(Request $request, Booking $booking)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|regex:/^[0-9+\-\s]+$/',
            'provider' => 'required|in:mtn,airtel',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($booking->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized access to booking',
            ], 403);
        }

        $result = $this->paymentService->processMobileMoneyPayment(
            $booking,
            $request->phone_number,
            $request->provider
        );

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'transaction_id' => $result['transaction_id'],
                'message' => $result['message'],
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error'],
        ], 400);
    }

    /**
     * Webhook for mobile money payment confirmation
     */
    public function mobileMoneyWebhook(Request $request)
    {
        $secret = config('app.webhook_secret');
        $signature = $request->header('X-Webhook-Signature');

        if (!$secret) {
            return response()->json(['error' => 'Webhook is not configured'], 503);
        }

        if (!$signature || !$this->webhookService->verifySignature($request->getContent(), $signature, $secret)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|string|max:255',
            'status' => 'required|in:pending,completed,failed,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid webhook payload'], 422);
        }

        if ($request->input('status') !== 'completed') {
            return response()->json(['status' => 'received']);
        }

        $booking = DB::transaction(function () use ($request) {
            $booking = Booking::where('payment->transaction_id', $request->input('transaction_id'))
                ->where('payment_method', 'mobile_money')
                ->lockForUpdate()
                ->first();

            if (!$booking || $booking->payment_status !== 'pending') {
                return null;
            }

            $payment = $booking->payment ?? [];
            $payment['completed_at'] = now();
            $payment['webhook_received_at'] = now();

            $booking->update([
                'payment_status' => 'completed',
                'payment' => $payment,
            ]);

            return $booking;
        });

        if (!$booking) {
            return response()->json(['status' => 'received']);
        }

        $this->notificationService->sendPaymentConfirmation($booking);

        return response()->json(['status' => 'received']);
    }

    /**
     * IntaSend sends the configured challenge in each webhook. Match api_ref, invoice,
     * amount and currency before recording payment; never trust state alone.
     */
    public function intaSendWebhook(Request $request)
    {
        $challengeSecret = config('services.intasend.webhook_challenge');
        if (!$challengeSecret) {
            return response()->json(['error' => 'IntaSend webhook is not configured'], 503);
        }
        $challenge = (string) $request->input('challenge', '');
        if ($challenge === '' || !hash_equals((string) $challengeSecret, $challenge)) {
            return response()->json(['error' => 'Invalid webhook challenge'], 401);
        }

        $payload = $request->all();
        if (($payload['topic'] ?? '') !== 'collection_event') {
            return response()->json(['received' => true]);
        }

        $apiReference = (string) ($payload['api_ref'] ?? '');
        $invoiceId = (string) ($payload['invoice_id'] ?? '');
        $state = strtoupper((string) ($payload['state'] ?? ''));
        if ($apiReference === '' || $invoiceId === '') {
            return response()->json(['received' => true]);
        }

        $booking = Booking::where('payment->provider_api_reference', $apiReference)->first();
        if (!$booking) {
            \Illuminate\Support\Facades\Log::warning('IntaSend callback references unknown booking', ['invoice_id' => $invoiceId]);
            return response()->json(['received' => true]);
        }

        $expectedAmount = (int) round((float) ($booking->pricing['total'] ?? $booking->totalPrice ?? 0));
        $receivedAmount = (int) round((float) ($payload['value'] ?? 0));
        $currency = strtoupper((string) ($payload['currency'] ?? ''));
        if ($currency !== 'UGX' || $receivedAmount !== $expectedAmount) {
            \Illuminate\Support\Facades\Log::warning('IntaSend callback does not match booking quote', [
                'booking_id' => $booking->id,
                'invoice_id' => $invoiceId,
                'currency_matches' => $currency === 'UGX',
                'amount_matches' => $receivedAmount === $expectedAmount,
            ]);
            return response()->json(['received' => true]);
        }

        if (!in_array($state, ['COMPLETE', 'FAILED', 'CANCELED', 'CANCELLED', 'PENDING', 'PROCESSING', 'RETRY', 'PARTIAL'], true)) {
            return response()->json(['received' => true]);
        }

        $confirmationResult = \Illuminate\Support\Facades\DB::transaction(function () use ($booking, $payload, $invoiceId, $apiReference, $state, $expectedAmount) {
            $locked = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $newlyCompleted = false;
            $payment = array_merge($locked->payment ?? [], [
                'provider' => 'intasend',
                'provider_invoice_id' => $invoiceId,
                'provider_api_reference' => $apiReference,
                'provider_reference' => $payload['provider_ref'] ?? null,
                'provider_state' => $state,
                'provider_updated_at' => now()->toIso8601String(),
            ]);

            if ($state === 'COMPLETE') {
                if ($locked->payment_status !== 'completed') {
                    $locked->update(['payment_method' => 'mobile_money', 'payment_status' => 'completed', 'payment' => $payment]);
                    $newlyCompleted = true;
                    \App\Models\FinancialTransaction::firstOrCreate(
                        ['provider_reference' => $invoiceId],
                        [
                            'booking_id' => $locked->id,
                            'user_id' => $locked->user_id,
                            'type' => 'payment',
                            'status' => 'completed',
                            'amount_minor' => $expectedAmount * 100,
                            'currency' => 'UGX',
                            'provider' => 'intasend',
                            'idempotency_key' => 'intasend-payment-' . $invoiceId,
                            'metadata' => ['provider_reference' => $payload['provider_ref'] ?? null, 'api_reference' => $apiReference],
                            'occurred_at' => now(),
                        ]
                    );
                }
            } elseif (in_array($state, ['FAILED', 'CANCELED', 'CANCELLED'], true)) {
                if ($locked->payment_status !== 'completed') {
                    $locked->update(['payment_status' => 'failed', 'payment' => $payment]);
                }
            } else {
                $locked->update(['payment' => $payment]);
            }
            return ['booking' => $locked->fresh(), 'newly_completed' => $newlyCompleted];
        });

        if ($state === 'COMPLETE' && $confirmationResult['newly_completed']) {
            $this->notificationService->sendPaymentConfirmation($confirmationResult['booking']);
        }
        return response()->json(['received' => true]);
    }

    /**
     * Confirm Stripe events using the provider signature, not a client-supplied status.
     */
    public function stripeWebhook(Request $request)
    {
        $secret = config('services.stripe.webhook.secret');
        if (!$secret) {
            return response()->json(['error' => 'Stripe webhook is not configured'], 503);
        }

        try {
            $event = \Stripe\Webhook::constructEvent(
                $request->getContent(),
                $request->header('Stripe-Signature', ''),
                $secret
            );
        } catch (\UnexpectedValueException $e) {
            return response()->json(['error' => 'Invalid webhook payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response()->json(['error' => 'Invalid webhook signature'], 400);
        }

        if ($event->type !== 'payment_intent.succeeded') {
            return response()->json(['received' => true]);
        }

        $intent = $event->data->object;
        $bookingId = $intent->metadata->booking_id ?? null;
        if (!$bookingId) {
            \Illuminate\Support\Facades\Log::warning('Stripe succeeded event missing booking metadata', ['event_id' => $event->id]);
            return response()->json(['received' => true]);
        }

        $booking = Booking::find($bookingId);
        if (!$booking) {
            \Illuminate\Support\Facades\Log::warning('Stripe succeeded event references unknown booking', ['event_id' => $event->id]);
            return response()->json(['received' => true]);
        }
        if (in_array($booking->payment_status, ['completed', 'refunded'], true)) {
            return response()->json(['received' => true]);
        }

        $confirmed = $this->paymentService->confirmStripePayment($intent->id, $booking);
        if ($confirmed) {
            $this->notificationService->sendPaymentConfirmation($booking->fresh());
        } else {
            \Illuminate\Support\Facades\Log::warning('Stripe event did not match booking quote', [
                'event_id' => $event->id,
                'booking_id' => $booking->id,
            ]);
        }

        // Return 200 after signature validation to avoid retrying a permanently mismatched event forever.
        return response()->json(['received' => true]);
    }

    /**
     * Generate invoice PDF
     */
    public function generateInvoice(Booking $booking)
    {
        if ($booking->user_id !== Auth::id() && !Auth::user()->is_admin) {
            abort(403, 'Unauthorized access to invoice');
        }

        if ($booking->payment_status !== 'completed') {
            return redirect()->back()->with('error', 'Invoice can only be generated for completed payments.');
        }

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('invoices.booking', compact('booking'));
        
        return $pdf->download("invoice-booking-{$booking->id}.pdf");
    }
}
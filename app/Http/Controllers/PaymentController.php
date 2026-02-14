<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\PaymentService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    private PaymentService $paymentService;
    private NotificationService $notificationService;

    public function __construct(PaymentService $paymentService, NotificationService $notificationService)
    {
        $this->paymentService = $paymentService;
        $this->notificationService = $notificationService;
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
            $request->phone_number
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
        // Validate webhook signature in production
        $transactionId = $request->input('transaction_id');
        $status = $request->input('status');
        
        if ($status === 'completed') {
            $booking = Booking::where('payment->transaction_id', $transactionId)->first();
            
            if ($booking) {
                $booking->update([
                    'payment_status' => 'completed',
                    'payment' => array_merge($booking->payment ?? [], [
                        'completed_at' => now(),
                        'webhook_received_at' => now(),
                    ]),
                ]);

                // Send payment confirmation notification
                $this->notificationService->sendPaymentConfirmation($booking);
            }
        }

        return response()->json(['status' => 'received']);
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
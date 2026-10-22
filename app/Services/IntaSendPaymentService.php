<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class IntaSendPaymentService
{
    public function initiate(Booking $booking, string $phoneNumber, string $provider): array
    {
        $secret = config('services.intasend.secret_key');
        if (!config('services.intasend.enabled', false) || !$secret) {
            return ['success' => false, 'error' => 'Mobile money is not configured. No payment was initiated.'];
        }

        $currency = strtoupper((string) ($booking->pricing['currency'] ?? config('rental.currency', 'UGX')));
        if ($currency !== 'UGX') {
            return ['success' => false, 'error' => 'MTN/Airtel mobile money currently requires UGX for this integration.'];
        }
        if (in_array($booking->status, ['canceled', 'cancelled', 'expired', 'completed', 'returned'], true)
            || in_array($booking->payment_status, ['completed', 'refunded'], true)) {
            return ['success' => false, 'error' => 'This booking is not eligible for a new payment.'];
        }

        $amount = (int) round($this->total($booking));
        if ($amount < 500 || $amount > 5000000) {
            return ['success' => false, 'error' => 'Mobile money payments must be between UGX 500 and UGX 5,000,000.'];
        }

        $phone = preg_replace('/\D+/', '', $phoneNumber);
        if (strpos($phone, '0') === 0) {
            $phone = '256' . substr($phone, 1);
        }
        if (strpos($phone, '256') !== 0) {
            return ['success' => false, 'error' => 'Enter a Ugandan mobile number with country code 256.'];
        }

        $prefix = substr($phone, 0, 5);
        $mtnPrefixes = ['25677', '25678', '25676', '25639'];
        $airtelPrefixes = ['25670', '25675', '25674'];
        if (($provider === 'mtn' && !in_array($prefix, $mtnPrefixes, true))
            || ($provider === 'airtel' && !in_array($prefix, $airtelPrefixes, true))) {
            return ['success' => false, 'error' => 'The phone number prefix does not match the selected mobile money network.'];
        }

        $apiReference = 'mam-booking-' . $booking->id . '-' . Str::uuid()->toString();
        try {
            $response = Http::withToken($secret)
                ->acceptJson()
                ->timeout(25)
                ->post(rtrim(config('services.intasend.base_url', 'https://api.intasend.com'), '/') . '/api/v1/payment/intasend-xb-push/', [
                    'amount' => $amount,
                    'currency' => 'UGX',
                    'country' => 'UG',
                    'phone_number' => $phone,
                    'api_ref' => $apiReference,
                    'first_name' => trim(explode(' ', $booking->customerName)[0] ?? 'Customer'),
                    'email' => $booking->customerEmail,
                ]);

            if (!$response->successful()) {
                Log::warning('IntaSend mobile money request rejected', [
                    'booking_id' => $booking->id,
                    'http_status' => $response->status(),
                ]);
                return ['success' => false, 'error' => 'The mobile money provider could not initiate this payment. No payment was confirmed.'];
            }

            $body = $response->json() ?: [];
            $invoice = $body['invoice'] ?? $body;
            $invoiceId = $invoice['invoice_id'] ?? null;
            if (!$invoiceId) {
                Log::warning('IntaSend response missing invoice reference', ['booking_id' => $booking->id]);
                return ['success' => false, 'error' => 'The payment provider returned an invalid response. Contact support before retrying.'];
            }

            $booking->update([
                'payment_method' => 'mobile_money',
                'payment_status' => 'pending',
                'mobile_money_number' => $phone,
                'payment' => array_merge($booking->payment ?? [], [
                    'provider' => 'intasend',
                    'provider_invoice_id' => $invoiceId,
                    'provider_api_reference' => $apiReference,
                    'provider_state' => strtoupper((string) ($invoice['state'] ?? 'PENDING')),
                    'amount' => $amount,
                    'currency' => 'UGX',
                    'phone_number' => $phone,
                    'initiated_at' => now()->toIso8601String(),
                ]),
            ]);

            return [
                'success' => true,
                'transaction_id' => $invoiceId,
                'message' => 'Payment prompt requested. Complete it on your phone; the booking will be marked paid only after provider confirmation.',
            ];
        } catch (\Throwable $e) {
            Log::error('IntaSend mobile money request failed', [
                'booking_id' => $booking->id,
                'exception_class' => get_class($e),
            ]);
            return ['success' => false, 'error' => 'The mobile money provider is temporarily unavailable. No payment was confirmed.'];
        }
    }

    private function total(Booking $booking): float
    {
        $pricing = $booking->pricing ?? [];
        return (float) ($pricing['total'] ?? $booking->totalPrice ?? 0);
    }
}

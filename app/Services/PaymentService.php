<?php

namespace App\Services;

use App\Models\Booking;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Exception\ApiErrorException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\FinancialTransaction;

class PaymentService
{
    public function __construct()
    {
        if (config('services.stripe.secret')) {
            Stripe::setApiKey(config('services.stripe.secret'));
        }
    }

    /**
     * Create Stripe payment intent
     */
    public function createStripePayment(Booking $booking): array
    {
        try {
            if (!config('services.stripe.secret') || !config('services.stripe.enabled', false)) {
                return ['success' => false, 'error' => 'Card payments are not enabled. Please contact MAM Tours.'];
            }
            if (in_array($booking->status, ['canceled', 'cancelled', 'expired', 'completed', 'returned'], true)
                || in_array($booking->payment_status, ['completed', 'refunded'], true)) {
                return ['success' => false, 'error' => 'This booking is not eligible for a new payment.'];
            }
            $amount = $this->calculateTotalAmount($booking);
            $currency = strtolower((string) ($booking->pricing['currency'] ?? config('rental.currency', 'UGX')));
            $providerCurrency = strtolower((string) config('services.stripe.currency', ''));
            if (!in_array($currency, ['ugx', 'usd'], true)) {
                return ['success' => false, 'error' => 'This currency is not supported by the configured payment adapter. No payment was initiated.'];
            }
            if (!$providerCurrency || $currency !== $providerCurrency) {
                return ['success' => false, 'error' => 'The configured payment currency does not match this booking. No payment was initiated.'];
            }
            $amountMinor = $this->toStripeAmount($amount, $currency);
            if ($amountMinor <= 0) {
                return ['success' => false, 'error' => 'Invalid payment amount.'];
            }
            $paymentIntent = PaymentIntent::create([
                'amount' => $amountMinor,
                'currency' => $currency,
                'metadata' => [
                    'booking_id' => $booking->id,
                    'user_id' => $booking->user_id,
                    'car_id' => $booking->car_id,
                ],
                'description' => "Car rental booking #{$booking->id}",
            ], [
                'idempotency_key' => 'mam-booking-' . $booking->id . '-payment-v1',
            ]);

            return [
                'success' => true,
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe payment creation failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Payment provider request failed. Please try again later.',
            ];
        }
    }

    /**
     * Process mobile money payment (MTN/Airtel)
     */
    public function processMobileMoneyPayment(Booking $booking, string $phoneNumber): array
    {
        try {
            // Do not generate fake transaction references or imply a prompt was sent.
            // Enable only after a real MTN/Airtel adapter and signed webhook are configured.
            if (!config('services.mobile_money.enabled', false)) {
                return ['success' => false, 'error' => 'Mobile money is not configured yet. No payment was initiated.'];
            }
            return ['success' => false, 'error' => 'No supported mobile-money provider adapter is configured. No payment was initiated.'];
        } catch (\Exception $e) {
            Log::error('Mobile money payment failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Mobile money payment failed. Please try again.',
            ];
        }
    }

    /**
     * Confirm Stripe payment
     */
    public function confirmStripePayment(string $paymentIntentId, Booking $booking): bool
    {
        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
            
            $expectedCurrency = strtolower((string) ($booking->pricing['currency'] ?? config('rental.currency', 'UGX')));
            if (!in_array($expectedCurrency, ['ugx', 'usd'], true)) {
                return false;
            }
            $expectedAmount = $this->toStripeAmount($this->calculateTotalAmount($booking), $expectedCurrency);
            if ($paymentIntent->status !== 'succeeded'
                || (string) ($paymentIntent->metadata->booking_id ?? '') !== (string) $booking->id
                || strtolower((string) $paymentIntent->currency) !== $expectedCurrency
                || (int) $paymentIntent->amount !== $expectedAmount) {
                Log::warning('Stripe payment did not match expected booking details', ['booking_id' => $booking->id]);
                return false;
            }

            DB::transaction(function () use ($booking, $paymentIntentId, $paymentIntent, $expectedCurrency) {
                $locked = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
                if ($locked->payment_status === 'completed') {
                    return;
                }
                $locked->update([
                    'payment_method' => 'stripe',
                    'payment_status' => 'completed',
                    'payment' => array_merge($locked->payment ?? [], [
                        'stripe_payment_intent' => $paymentIntentId,
                        'amount' => $this->calculateTotalAmount($locked),
                        'currency' => strtoupper($expectedCurrency),
                        'completed_at' => now(),
                    ]),
                ]);
                FinancialTransaction::firstOrCreate(
                    ['provider_reference' => $paymentIntentId],
                    [
                        'booking_id' => $locked->id,
                        'user_id' => $locked->user_id,
                        'type' => 'payment',
                        'status' => 'completed',
                        'amount_minor' => $this->toStripeAmount($this->calculateTotalAmount($locked), $expectedCurrency),
                        'currency' => strtoupper($expectedCurrency),
                        'provider' => 'stripe',
                        'idempotency_key' => 'stripe-payment-' . $paymentIntentId,
                        'occurred_at' => now(),
                    ]
                );
            });
            return true;
        } catch (ApiErrorException $e) {
            Log::error('Stripe payment confirmation failed', [
                'payment_intent_id' => $paymentIntentId,
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Calculate total booking amount
     */
    private function calculateTotalAmount(Booking $booking): float
    {
        $pricing = $booking->pricing ?? [];
        // Existing quotations use total; legacy keys are supported only as a fallback.
        return (float) ($pricing['total'] ?? (
            ($pricing['basePrice'] ?? $pricing['base'] ?? 0)
            + ($pricing['addOnsTotal'] ?? $pricing['addOnTotal'] ?? 0)
            + ($pricing['tax'] ?? $pricing['taxes'] ?? 0)
        ));
    }

    private function toStripeAmount(float $amount, string $currency): int
    {
        // Stripe represents UGX in two-decimal API units for backwards compatibility.
        // Other zero-decimal currencies must be explicitly added after provider verification.
        return (int) round($amount * 100);
    }

    /**
     * Process refund for cancelled bookings
     */
    public function processRefund(Booking $booking): array
    {
        if ($booking->payment_method === 'stripe' && isset($booking->payment['stripe_payment_intent'])) {
            try {
                $refund = \Stripe\Refund::create([
                    'payment_intent' => $booking->payment['stripe_payment_intent'],
                    'reason' => 'requested_by_customer',
                ], [
                    'idempotency_key' => 'mam-booking-' . $booking->id . '-refund-v1',
                ]);

                DB::transaction(function () use ($booking, $refund) {
                    $locked = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
                    $payment = $locked->payment ?? [];
                    $payment['refund_id'] = $refund->id;
                    $payment['refunded_at'] = now();
                    $locked->update(['payment_status' => 'refunded', 'payment' => $payment]);
                    $currency = strtoupper((string) ($locked->pricing['currency'] ?? config('rental.currency', 'UGX')));
                    FinancialTransaction::firstOrCreate(
                        ['provider_reference' => $refund->id],
                        [
                            'booking_id' => $locked->id,
                            'user_id' => $locked->user_id,
                            'type' => 'refund',
                            'status' => 'completed',
                            'amount_minor' => $this->toStripeAmount($this->calculateTotalAmount($locked), strtolower($currency)),
                            'currency' => $currency,
                            'provider' => 'stripe',
                            'idempotency_key' => 'stripe-refund-' . $refund->id,
                            'occurred_at' => now(),
                        ]
                    );
                });
                return ['success' => true, 'refund_id' => $refund->id];
            } catch (ApiErrorException $e) {
                Log::error('Stripe refund failed', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
                
                return ['success' => false, 'error' => 'Refund provider request failed. Please review the payment dashboard.'];
            }
        }

        // Handle mobile money refunds (would need specific API integration)
        return ['success' => false, 'error' => 'Refund not supported for this payment method'];
    }
}
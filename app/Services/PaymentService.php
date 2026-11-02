<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\FinancialTransaction;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Exception\ApiErrorException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    /**
     * Stripe zero-decimal currencies: the API amount is the whole-unit amount.
     * https://stripe.com/docs/currencies#zero-decimal
     */
    private const ZERO_DECIMAL_CURRENCIES = [
        'bif', 'clp', 'djf', 'gnf', 'jpy', 'kmf', 'krw', 'mga', 'pyg',
        'rwf', 'vnd', 'vuv', 'xaf', 'xof', 'xpf',
    ];

    /**
     * Currencies without a subunit in daily use that Stripe still expects as
     * two-decimal values with trailing "00" (UGX 5 is sent as amount 500).
     */
    private const TWO_DECIMAL_STRIPE_CURRENCIES = ['isk', 'huf', 'twd', 'ugx'];

    public function __construct()
    {
        if (config('services.stripe.secret')) {
            Stripe::setApiKey(config('services.stripe.secret'));
        }
    }

    public function currency(): string
    {
        return strtolower((string) config('services.stripe.currency', 'ugx'));
    }

    public function bookingCurrency(Booking $booking): string
    {
        return strtolower((string) ($booking->pricing['currency'] ?? config('rental.currency', 'UGX')));
    }

    public function toMinorUnits(float $amount, ?string $currency = null): int
    {
        $currency = strtolower($currency ?? $this->currency());

        if (in_array($currency, self::TWO_DECIMAL_STRIPE_CURRENCIES, true)) {
            return (int) round($amount * 100);
        }

        return in_array($currency, self::ZERO_DECIMAL_CURRENCIES, true)
            ? (int) round($amount)
            : (int) round($amount * 100);
    }

    public function fromMinorUnits(int $amount, ?string $currency = null): float
    {
        $currency = strtolower($currency ?? $this->currency());

        return in_array($currency, self::ZERO_DECIMAL_CURRENCIES, true) ? (float) $amount : $amount / 100;
    }

    public function createStripePayment(Booking $booking): array
    {
        try {
            if (!config('services.stripe.secret') || !config('services.stripe.enabled', false)) {
                return ['success' => false, 'error' => 'Card payments are not enabled. Please contact MAM Tours.'];
            }
            if (($booking->payment_method ?? $booking->paymentMethod) !== 'stripe') {
                return ['success' => false, 'error' => 'This booking was not created for card payment.'];
            }
            if (in_array($booking->status, ['canceled', 'cancelled', 'expired', 'completed', 'returned'], true)
                || in_array($booking->payment_status, ['completed', 'refunded'], true)) {
                return ['success' => false, 'error' => 'This booking is not eligible for a new payment.'];
            }

            $currency = $this->bookingCurrency($booking);
            $providerCurrency = $this->currency();

            if (!in_array($currency, ['ugx', 'usd'], true)) {
                return ['success' => false, 'error' => 'This currency is not supported by the configured payment provider. No payment was initiated.'];
            }
            if ($providerCurrency === '' || $currency !== $providerCurrency) {
                return ['success' => false, 'error' => 'The configured payment currency does not match this booking. No payment was initiated.'];
            }

            $amountMinor = $this->toMinorUnits($this->calculateTotalAmount($booking), $currency);

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

            return ['success' => false, 'error' => 'Payment provider request failed. Please try again later.'];
        }
    }

    public function processMobileMoneyPayment(Booking $booking, string $phoneNumber, string $provider = 'mtn'): array
    {
        return app(IntaSendPaymentService::class)->initiate($booking, $phoneNumber, $provider);
    }

    public function confirmStripePayment(string $paymentIntentId, Booking $booking): bool
    {
        try {
            if (($booking->payment_method ?? $booking->paymentMethod) !== 'stripe') {
                return false;
            }

            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
        } catch (ApiErrorException $e) {
            Log::error('Stripe payment confirmation failed', [
                'payment_intent_id' => $paymentIntentId,
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        return $this->applyStripeIntent($paymentIntent, $booking);
    }

    /**
     * Marks the booking paid only if the intent belongs to it, succeeded, and
     * charged the amount expected for the booking's currency.
     */
    public function applyStripeIntent(object $paymentIntent, Booking $booking): bool
    {
        if (!$this->intentMatchesBooking($paymentIntent, $booking)) {
            Log::warning('Stripe intent rejected for booking', [
                'payment_intent_id' => $paymentIntent->id ?? null,
                'booking_id' => $booking->id,
            ]);

            return false;
        }

        $currency = strtolower((string) $paymentIntent->currency);
        $amountMinor = (int) ($paymentIntent->amount_received ?? $paymentIntent->amount ?? 0);
        $amount = $this->fromMinorUnits($amountMinor, $currency);

        if (in_array($booking->status, ['canceled', 'cancelled', 'expired'], true)) {
            // The capture may land after cancellation: record the funds first so a
            // failed refund stays visible to finance, then refund automatically.
            $this->recordPayment($booking, $paymentIntent->id, $amountMinor, $currency, $amount);

            try {
                $refund = \Stripe\Refund::create([
                    'payment_intent' => $paymentIntent->id,
                    'reason' => 'requested_by_customer',
                ], ['idempotency_key' => 'mam-booking-' . $booking->id . '-canceled-auto-refund-v1']);

                $this->recordRefund($booking, $refund->id, $amountMinor, $currency);
            } catch (ApiErrorException $refundError) {
                Log::error('Automatic refund failed for canceled booking', [
                    'booking_id' => $booking->id,
                    'error' => $refundError->getMessage(),
                ]);
            }

            return false;
        }

        return DB::transaction(function () use ($paymentIntent, $booking, $amountMinor, $currency, $amount) {
            $locked = Booking::whereKey($booking->id)->lockForUpdate()->first();

            if (!$locked) {
                return false;
            }

            if ($locked->payment_status === 'completed') {
                return ($locked->payment['stripe_payment_intent'] ?? null) === $paymentIntent->id;
            }

            $locked->update([
                'payment_method' => 'stripe',
                'payment_status' => 'completed',
                'payment' => array_merge($locked->payment ?? [], [
                    'stripe_payment_intent' => $paymentIntent->id,
                    'amount' => $amount,
                    'currency' => $currency,
                    'completed_at' => now(),
                ]),
            ]);

            $this->recordTransaction($locked, $paymentIntent->id, $amountMinor, $currency, 'payment', 'stripe-payment-');

            $booking->setRawAttributes($locked->getAttributes(), true);

            return true;
        });
    }

    public function intentMatchesBooking(object $paymentIntent, Booking $booking): bool
    {
        $metadataBookingId = $paymentIntent->metadata['booking_id'] ?? null;
        $currency = strtolower((string) ($paymentIntent->currency ?? ''));
        $received = (int) ($paymentIntent->amount_received ?? $paymentIntent->amount ?? 0);

        return ($paymentIntent->status ?? null) === 'succeeded'
            && $metadataBookingId !== null
            && (string) $metadataBookingId === (string) $booking->id
            && $currency === $this->bookingCurrency($booking)
            && $currency === $this->currency()
            && $received === $this->toMinorUnits($this->calculateTotalAmount($booking), $currency);
    }

    public function calculateTotalAmount(Booking $booking): float
    {
        $pricing = $booking->pricing;

        if (is_string($pricing)) {
            $pricing = json_decode($pricing, true);
        }

        $pricing = is_array($pricing) ? $pricing : [];

        if (($pricing['total'] ?? null) !== null) {
            return (float) $pricing['total'];
        }

        if ($booking->totalPrice !== null) {
            return (float) $booking->totalPrice;
        }

        return (float) (
            ($pricing['basePrice'] ?? $pricing['base'] ?? 0)
            + ($pricing['addOnsTotal'] ?? $pricing['addOnTotal'] ?? 0)
            + ($pricing['tax'] ?? $pricing['taxes'] ?? 0)
        );
    }

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

                    $this->recordTransaction(
                        $locked,
                        $refund->id,
                        $this->toMinorUnits($this->calculateTotalAmount($locked), $this->bookingCurrency($locked)),
                        $this->bookingCurrency($locked),
                        'refund',
                        'stripe-refund-'
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

        return ['success' => false, 'error' => 'Refund not supported for this payment method'];
    }

    private function recordPayment(Booking $booking, string $paymentIntentId, int $amountMinor, string $currency, float $amount): void
    {
        DB::transaction(function () use ($booking, $paymentIntentId, $amountMinor, $currency, $amount) {
            $locked = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            $locked->update([
                'payment_method' => 'stripe',
                'payment_status' => 'completed',
                'payment' => array_merge($locked->payment ?? [], [
                    'stripe_payment_intent' => $paymentIntentId,
                    'amount' => $amount,
                    'currency' => $currency,
                    'completed_at' => now(),
                ]),
            ]);

            $this->recordTransaction($locked, $paymentIntentId, $amountMinor, $currency, 'payment', 'stripe-payment-');
        });
    }

    private function recordRefund(Booking $booking, string $refundId, int $amountMinor, string $currency): void
    {
        DB::transaction(function () use ($booking, $refundId, $amountMinor, $currency) {
            $locked = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            $locked->update([
                'payment_status' => 'refunded',
                'payment' => array_merge($locked->payment ?? [], [
                    'refund_id' => $refundId,
                    'refunded_at' => now(),
                ]),
            ]);

            $this->recordTransaction($locked, $refundId, $amountMinor, $currency, 'refund', 'stripe-refund-');
        });
    }

    private function recordTransaction(Booking $booking, string $reference, int $amountMinor, string $currency, string $type, string $keyPrefix): void
    {
        FinancialTransaction::firstOrCreate(
            ['provider_reference' => $reference],
            [
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'type' => $type,
                'status' => 'completed',
                'amount_minor' => $amountMinor,
                'currency' => strtoupper($currency),
                'provider' => 'stripe',
                'idempotency_key' => $keyPrefix . $reference,
                'occurred_at' => now(),
            ]
        );
    }
}

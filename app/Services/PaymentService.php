<?php

namespace App\Services;

use App\Models\Booking;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Exception\ApiErrorException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    // https://stripe.com/docs/currencies#zero-decimal
    private const ZERO_DECIMAL_CURRENCIES = [
        'bif', 'clp', 'djf', 'gnf', 'jpy', 'kmf', 'krw', 'mga', 'pyg',
        'rwf', 'ugx', 'vnd', 'vuv', 'xaf', 'xof', 'xpf',
    ];

    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function currency(): string
    {
        return strtolower(config('services.stripe.currency', 'ugx'));
    }

    public function toMinorUnits(float $amount, ?string $currency = null): int
    {
        $currency = strtolower($currency ?? $this->currency());

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
            $amount = $this->calculateTotalAmount($booking);

            if ($amount <= 0) {
                return ['success' => false, 'error' => 'Booking has no payable amount'];
            }

            $paymentIntent = PaymentIntent::create([
                'amount' => $this->toMinorUnits($amount),
                'currency' => $this->currency(),
                'metadata' => [
                    'booking_id' => $booking->id,
                    'user_id' => $booking->user_id,
                    'car_id' => $booking->car_id,
                ],
                'description' => "Car rental booking #{$booking->id}",
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

            return ['success' => false, 'error' => 'Unable to start card payment. Please try again.'];
        }
    }

    /**
     * Process mobile money payment (MTN/Airtel)
     */
    public function processMobileMoneyPayment(Booking $booking, string $phoneNumber): array
    {
        try {
            $amount = $this->calculateTotalAmount($booking);
            
            // Simulate mobile money API call
            // In production, integrate with actual mobile money APIs
            $transactionId = 'MM_' . time() . '_' . $booking->id;
            
            // Update booking with mobile money details
            $booking->update([
                'payment_method' => 'mobile_money',
                'payment_status' => 'pending',
                'mobile_money_number' => $phoneNumber,
                'payment' => array_merge($booking->payment ?? [], [
                    'transaction_id' => $transactionId,
                    'amount' => $amount,
                    'phone_number' => $phoneNumber,
                    'initiated_at' => now(),
                ]),
            ]);

            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'message' => 'Mobile money payment initiated. Please complete on your phone.',
            ];
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

    public function confirmStripePayment(string $paymentIntentId, Booking $booking): bool
    {
        try {
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
     * Marks the booking paid only if the intent belongs to it, succeeded, and charged the expected amount.
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

        return DB::transaction(function () use ($paymentIntent, $booking) {
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
                    'amount' => $this->fromMinorUnits((int) $paymentIntent->amount_received, $paymentIntent->currency),
                    'currency' => $paymentIntent->currency,
                    'completed_at' => now(),
                ]),
            ]);

            $booking->setRawAttributes($locked->getAttributes(), true);

            return true;
        });
    }

    public function intentMatchesBooking(object $paymentIntent, Booking $booking): bool
    {
        $metadataBookingId = $paymentIntent->metadata['booking_id'] ?? null;
        $currency = strtolower((string) ($paymentIntent->currency ?? ''));

        return ($paymentIntent->status ?? null) === 'succeeded'
            && $metadataBookingId !== null
            && (string) $metadataBookingId === (string) $booking->id
            && $currency === $this->currency()
            && (int) ($paymentIntent->amount_received ?? 0) === $this->toMinorUnits($this->calculateTotalAmount($booking), $currency);
    }

    public function calculateTotalAmount(Booking $booking): float
    {
        $pricing = $booking->pricing;

        if (is_string($pricing)) {
            $pricing = json_decode($pricing, true);
        }

        return (float) (($pricing['total'] ?? null) ?? $booking->totalPrice ?? 0);
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
                ]);

                $booking->update([
                    'payment_status' => 'refunded',
                    'payment' => array_merge($booking->payment ?? [], [
                        'refund_id' => $refund->id,
                        'refunded_at' => now(),
                    ]),
                ]);

                return ['success' => true, 'refund_id' => $refund->id];
            } catch (ApiErrorException $e) {
                Log::error('Stripe refund failed', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
                
                return ['success' => false, 'error' => 'Refund could not be processed. Please contact support.'];
            }
        }

        // Handle mobile money refunds (would need specific API integration)
        return ['success' => false, 'error' => 'Refund not supported for this payment method'];
    }
}

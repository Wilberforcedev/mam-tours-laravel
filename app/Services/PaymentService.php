<?php

namespace App\Services;

use App\Models\Booking;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Exception\ApiErrorException;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create Stripe payment intent
     */
    public function createStripePayment(Booking $booking): array
    {
        try {
            $amount = $this->calculateTotalAmount($booking);
            
            $paymentIntent = PaymentIntent::create([
                'amount' => $amount * 100, // Stripe expects cents
                'currency' => 'usd',
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

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
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

    /**
     * Confirm Stripe payment
     */
    public function confirmStripePayment(string $paymentIntentId, Booking $booking): bool
    {
        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
            
            if ($paymentIntent->status === 'succeeded') {
                $booking->update([
                    'payment_method' => 'stripe',
                    'payment_status' => 'completed',
                    'payment' => array_merge($booking->payment ?? [], [
                        'stripe_payment_intent' => $paymentIntentId,
                        'amount' => $paymentIntent->amount / 100,
                        'completed_at' => now(),
                    ]),
                ]);
                
                return true;
            }
            
            return false;
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
        $basePrice = $pricing['basePrice'] ?? 0;
        $addOnsTotal = $pricing['addOnsTotal'] ?? 0;
        $tax = $pricing['tax'] ?? 0;
        
        return $basePrice + $addOnsTotal + $tax;
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
                
                return ['success' => false, 'error' => $e->getMessage()];
            }
        }

        // Handle mobile money refunds (would need specific API integration)
        return ['success' => false, 'error' => 'Refund not supported for this payment method'];
    }
}
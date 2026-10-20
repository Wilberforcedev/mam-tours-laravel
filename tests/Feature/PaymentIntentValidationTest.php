<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Services\PaymentService;
use Tests\TestCase;

class PaymentIntentValidationTest extends TestCase
{
    private function serviceWithIntent($intent)
    {
        return new class($intent) extends PaymentService {
            private $paymentIntent;

            public function __construct($paymentIntent)
            {
                $this->paymentIntent = $paymentIntent;
            }

            protected function retrievePaymentIntent(string $paymentIntentId)
            {
                return $this->paymentIntent;
            }
        };
    }

    public function test_succeeded_intent_for_another_booking_is_not_accepted()
    {
        $booking = Booking::factory()->create([
            'pricing' => ['total' => 1000],
            'totalPrice' => 1000,
            'payment_status' => 'pending',
        ]);
        $service = $this->serviceWithIntent((object) [
            'status' => 'succeeded',
            'amount' => 100000,
            'currency' => 'ugx',
            'metadata' => (object) ['booking_id' => $booking->id + 1],
        ]);

        $this->assertFalse($service->confirmStripePayment('pi_wrong_booking', $booking));
        $this->assertNotSame('completed', $booking->fresh()->payment_status);
    }

    public function test_succeeded_intent_with_wrong_amount_is_not_accepted()
    {
        $booking = Booking::factory()->create([
            'pricing' => ['total' => 1000],
            'totalPrice' => 1000,
            'payment_status' => 'pending',
        ]);
        $service = $this->serviceWithIntent((object) [
            'status' => 'succeeded',
            'amount' => 99999,
            'currency' => 'ugx',
            'metadata' => (object) ['booking_id' => $booking->id],
        ]);

        $this->assertFalse($service->confirmStripePayment('pi_wrong_amount', $booking));
        $this->assertNotSame('completed', $booking->fresh()->payment_status);
    }
}

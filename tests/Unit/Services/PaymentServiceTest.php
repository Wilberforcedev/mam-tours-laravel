<?php

namespace Tests\Unit\Services;

use App\Models\Booking;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): PaymentService
    {
        return new PaymentService();
    }

    private function intent(array $overrides = []): object
    {
        return (object) array_merge([
            'id' => 'pi_test_123',
            'status' => 'succeeded',
            'currency' => 'ugx',
            'amount_received' => 320000,
            'metadata' => ['booking_id' => null],
        ], $overrides);
    }

    /** @test */
    public function zero_decimal_currencies_are_not_multiplied()
    {
        $service = $this->service();

        $this->assertSame(100000, $service->toMinorUnits(100000.0, 'ugx'));
        $this->assertSame(320000, $service->toMinorUnits(320000.0, 'UGX'));
        $this->assertSame(1050, $service->toMinorUnits(1049.6, 'ugx'));
    }

    /** @test */
    public function standard_currencies_use_minor_units()
    {
        $service = $this->service();

        $this->assertSame(10050, $service->toMinorUnits(100.50, 'usd'));
        $this->assertSame(123456, $service->toMinorUnits(1234.56, 'eur'));
    }

    /** @test */
    public function default_currency_comes_from_config()
    {
        config(['services.stripe.currency' => 'ugx']);

        $this->assertSame('ugx', $this->service()->currency());
        $this->assertSame(50000, $this->service()->toMinorUnits(50000.0));
    }

    /** @test */
    public function minor_units_round_trip()
    {
        $service = $this->service();

        $this->assertSame(320000.0, $service->fromMinorUnits(320000, 'ugx'));
        $this->assertSame(100.5, $service->fromMinorUnits(10050, 'usd'));
    }

    /** @test */
    public function total_prefers_pricing_total_over_total_price()
    {
        $booking = new Booking([
            'pricing' => ['total' => 470000],
            'totalPrice' => 999999,
        ]);

        $this->assertSame(470000.0, $this->service()->calculateTotalAmount($booking));
    }

    /** @test */
    public function total_falls_back_to_total_price()
    {
        $booking = new Booking(['pricing' => null, 'totalPrice' => 150000]);

        $this->assertSame(150000.0, $this->service()->calculateTotalAmount($booking));
    }

    /** @test */
    public function total_decodes_json_string_pricing()
    {
        $booking = new Booking();
        $booking->setRawAttributes(['pricing' => json_encode(['total' => 84600])]);

        $this->assertSame(84600.0, $this->service()->calculateTotalAmount($booking));
    }

    /** @test */
    public function intent_matches_matching_booking()
    {
        $booking = Booking::factory()->create(['pricing' => ['total' => 320000]]);
        $intent = $this->intent(['metadata' => ['booking_id' => $booking->id]]);

        $this->assertTrue($this->service()->intentMatchesBooking($intent, $booking));
    }

    /** @test */
    public function intent_is_rejected_when_booking_id_differs()
    {
        $booking = Booking::factory()->create(['pricing' => ['total' => 320000]]);
        $intent = $this->intent(['metadata' => ['booking_id' => $booking->id + 1]]);

        $this->assertFalse($this->service()->intentMatchesBooking($intent, $booking));
    }

    /** @test */
    public function intent_is_rejected_without_booking_metadata()
    {
        $booking = Booking::factory()->create(['pricing' => ['total' => 320000]]);

        $this->assertFalse($this->service()->intentMatchesBooking($this->intent(), $booking));
    }

    /** @test */
    public function intent_is_rejected_on_currency_mismatch()
    {
        $booking = Booking::factory()->create(['pricing' => ['total' => 320000]]);
        $intent = $this->intent([
            'currency' => 'usd',
            'amount_received' => 32000000,
            'metadata' => ['booking_id' => $booking->id],
        ]);

        $this->assertFalse($this->service()->intentMatchesBooking($intent, $booking));
    }

    /** @test */
    public function intent_is_rejected_on_amount_mismatch()
    {
        $booking = Booking::factory()->create(['pricing' => ['total' => 320000]]);
        $intent = $this->intent([
            'amount_received' => 100,
            'metadata' => ['booking_id' => $booking->id],
        ]);

        $this->assertFalse($this->service()->intentMatchesBooking($intent, $booking));
    }

    /** @test */
    public function intent_is_rejected_when_not_succeeded()
    {
        $booking = Booking::factory()->create(['pricing' => ['total' => 320000]]);
        $intent = $this->intent([
            'status' => 'requires_payment_method',
            'metadata' => ['booking_id' => $booking->id],
        ]);

        $this->assertFalse($this->service()->intentMatchesBooking($intent, $booking));
    }

    /** @test */
    public function apply_marks_booking_paid()
    {
        $booking = Booking::factory()->create([
            'pricing' => ['total' => 320000],
            'payment' => ['status' => 'pending'],
        ]);
        $intent = $this->intent(['metadata' => ['booking_id' => $booking->id]]);

        $this->assertTrue($this->service()->applyStripeIntent($intent, $booking));

        $booking->refresh();
        $this->assertSame('stripe', $booking->payment_method);
        $this->assertSame('completed', $booking->payment_status);
        $this->assertSame('pi_test_123', $booking->payment['stripe_payment_intent']);
        $this->assertSame(320000.0, (float) $booking->payment['amount']);
        $this->assertSame('ugx', $booking->payment['currency']);
    }

    /** @test */
    public function apply_is_idempotent_for_same_intent()
    {
        $booking = Booking::factory()->create(['pricing' => ['total' => 320000]]);
        $intent = $this->intent(['metadata' => ['booking_id' => $booking->id]]);
        $service = $this->service();

        $this->assertTrue($service->applyStripeIntent($intent, $booking));
        $this->assertTrue($service->applyStripeIntent($intent, $booking));
        $this->assertSame('pi_test_123', $booking->fresh()->payment['stripe_payment_intent']);
    }

    /** @test */
    public function apply_rejects_a_second_different_intent()
    {
        $booking = Booking::factory()->create(['pricing' => ['total' => 320000]]);
        $service = $this->service();

        $this->assertTrue($service->applyStripeIntent(
            $this->intent(['id' => 'pi_first', 'metadata' => ['booking_id' => $booking->id]]),
            $booking
        ));

        $this->assertFalse($service->applyStripeIntent(
            $this->intent(['id' => 'pi_second', 'metadata' => ['booking_id' => $booking->id]]),
            $booking
        ));
    }

    /** @test */
    public function apply_does_not_persist_mismatched_intent()
    {
        $booking = Booking::factory()->create([
            'pricing' => ['total' => 320000],
            'payment_status' => 'pending',
        ]);

        $intent = $this->intent([
            'amount_received' => 1,
            'metadata' => ['booking_id' => $booking->id],
        ]);

        $this->assertFalse($this->service()->applyStripeIntent($intent, $booking));
        $this->assertSame('pending', $booking->fresh()->payment_status);
    }
}

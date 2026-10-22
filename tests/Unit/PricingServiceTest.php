<?php

namespace Tests\Unit;

use App\Models\Car;
use App\Services\PricingService;
use Tests\TestCase;

class PricingServiceTest extends TestCase
{
    public function test_quote_uses_consistent_total_tax_deposit_and_currency()
    {
        config([
            'rental.currency' => 'UGX',
            'rental.minimum_days' => 1,
            'rental.maximum_days' => 30,
            'rental.weekend_surcharge_rate' => 0,
            'rental.weekly_discount_rate' => 0,
            'rental.additional_driver_daily_fee' => 50000,
            'rental.child_seat_daily_fee' => 10000,
            'rental.delivery_fee' => 20000,
            'rental.tax_rate' => 0.18,
            'rental.deposit_rate' => 0.20,
        ]);

        $car = new Car(['dailyRate' => 100000]);
        $quote = app(PricingService::class)->quote($car, '2026-10-26', '2026-10-28', [
            'driver' => true,
            'childSeat' => true,
            'delivery' => true,
        ]);

        $this->assertSame('UGX', $quote['currency']);
        $this->assertSame(2, $quote['days']);
        $this->assertSame(200000, $quote['base']);
        $this->assertSame(120000, $quote['addOnTotal']);
        $this->assertSame(20000, $quote['deliveryFee']);
        $this->assertSame(340000, $quote['subtotal']);
        $this->assertSame(61200, $quote['taxes']);
        $this->assertSame(68000, $quote['deposit']);
        $this->assertSame(401200, $quote['total']);
    }

    public function test_quote_rejects_return_date_before_or_equal_to_pickup_date()
    {
        $car = new Car(['dailyRate' => 100000]);
        $this->expectException(\InvalidArgumentException::class);
        app(PricingService::class)->quote($car, '2026-10-26', '2026-10-26');
    }

    public function test_quote_enforces_configured_maximum_rental_duration()
    {
        config(['rental.minimum_days' => 1, 'rental.maximum_days' => 3]);
        $car = new Car(['dailyRate' => 100000]);
        $this->expectException(\InvalidArgumentException::class);
        app(PricingService::class)->quote($car, '2026-10-26', '2026-10-30');
    }
}

<?php

namespace Tests\Unit\Models;

use App\Models\Car;
use Tests\TestCase;

class CarTest extends TestCase
{
    /** @test */
    public function it_has_required_attributes()
    {
        $car = Car::factory()->make();

        $this->assertNotNull($car->brand);
        $this->assertNotNull($car->model);
        $this->assertNotNull($car->numberPlate);
        $this->assertNotNull($car->dailyRate);
        $this->assertNotNull($car->seats);
        $this->assertIsInt($car->dailyRate);
        $this->assertIsInt($car->seats);
        $this->assertIsBool($car->isAvailable);
    }

    /** @test */
    public function it_can_be_available_or_unavailable()
    {
        $availableCar = Car::factory()->make(['isAvailable' => true]);
        $unavailableCar = Car::factory()->unavailable()->make();

        $this->assertTrue($availableCar->isAvailable);
        $this->assertFalse($unavailableCar->isAvailable);
    }

    /** @test */
    public function it_has_valid_number_plate_format()
    {
        $car = Car::factory()->create();

        // Should be uppercase and follow pattern
        $this->assertMatchesRegularExpression('/^[A-Z0-9]+$/', $car->numberPlate);
        $this->assertGreaterThanOrEqual(5, strlen($car->numberPlate));
    }

    /** @test */
    public function it_has_reasonable_daily_rate()
    {
        $car = Car::factory()->create();

        $this->assertGreaterThan(0, $car->dailyRate);
        $this->assertLessThanOrEqual(1000000, $car->dailyRate); // Max 1M UGX
    }

    /** @test */
    public function it_has_valid_seat_count()
    {
        $car = Car::factory()->create();

        $this->assertGreaterThanOrEqual(2, $car->seats);
        $this->assertLessThanOrEqual(15, $car->seats);
    }

    /** @test */
    public function luxury_cars_have_higher_rates()
    {
        $luxuryCar = Car::factory()->luxury()->make();
        $economyCar = Car::factory()->economy()->make();

        $this->assertGreaterThan($economyCar->dailyRate, $luxuryCar->dailyRate);
        $this->assertEquals('luxury', $luxuryCar->category);
        $this->assertEquals('economy', $economyCar->category);
    }
}
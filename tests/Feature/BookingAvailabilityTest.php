<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Car;
use App\Services\BookingAvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_overlapping_booking_blocks_the_same_vehicle()
    {
        $car = Car::factory()->create(['isAvailable' => true]);
        Booking::factory()->create([
            'car_id' => $car->id,
            'status' => 'confirmed',
            'startDate' => '2026-10-26 09:00:00',
            'endDate' => '2026-10-28 09:00:00',
        ]);

        $service = app(BookingAvailabilityService::class);
        $this->assertFalse($service->isAvailable($car->id, '2026-10-27 09:00:00', '2026-10-29 09:00:00'));
    }

    public function test_non_overlapping_turnaround_booking_is_allowed()
    {
        $car = Car::factory()->create(['isAvailable' => true]);
        Booking::factory()->create([
            'car_id' => $car->id,
            'status' => 'confirmed',
            'startDate' => '2026-10-26 09:00:00',
            'endDate' => '2026-10-28 09:00:00',
        ]);

        $service = app(BookingAvailabilityService::class);
        $this->assertTrue($service->isAvailable($car->id, '2026-10-28 09:00:00', '2026-10-30 09:00:00'));
    }

    public function test_expired_reservation_does_not_block_availability()
    {
        $car = Car::factory()->create(['isAvailable' => true]);
        Booking::factory()->create([
            'car_id' => $car->id,
            'status' => 'reserved',
            'startDate' => '2026-10-26 09:00:00',
            'endDate' => '2026-10-28 09:00:00',
            'expiresAt' => now()->subMinute(),
        ]);

        $service = app(BookingAvailabilityService::class);
        $this->assertTrue($service->isAvailable($car->id, '2026-10-27 09:00:00', '2026-10-29 09:00:00'));
    }
}

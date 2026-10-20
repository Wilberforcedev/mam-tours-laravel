<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Car;
use Tests\TestCase;

class BookingAvailabilityTest extends TestCase
{
    public function test_reservation_rejects_overlapping_active_booking_dates()
    {
        $customer = $this->createCustomer();
        $car = Car::factory()->create(['isAvailable' => true]);
        Booking::factory()->create([
            'car_id' => $car->id,
            'status' => 'confirmed',
            'startDate' => '2026-11-10',
            'endDate' => '2026-11-15',
        ]);

        $response = $this->actingAs($customer)->postJson('/api/bookings/reserve', [
            'carId' => $car->id,
            'customerName' => 'Another Customer',
            'startDate' => '2026-11-14',
            'endDate' => '2026-11-17',
        ]);

        $response->assertStatus(409);
        $this->assertSame(1, Booking::where('car_id', $car->id)->count());
    }

    public function test_reservation_allows_non_overlapping_dates()
    {
        $customer = $this->createCustomer();
        $car = Car::factory()->create(['isAvailable' => true]);
        Booking::factory()->create([
            'car_id' => $car->id,
            'status' => 'confirmed',
            'startDate' => '2026-11-10',
            'endDate' => '2026-11-15',
        ]);

        $response = $this->actingAs($customer)->postJson('/api/bookings/reserve', [
            'carId' => $car->id,
            'customerName' => 'Another Customer',
            'startDate' => '2026-11-15',
            'endDate' => '2026-11-17',
        ]);

        $response->assertCreated();
        $this->assertSame(2, Booking::where('car_id', $car->id)->count());
    }
}

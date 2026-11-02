<?php

namespace Tests\Integration;

use App\Models\Car;
use App\Models\User;
use App\Models\Booking;
use Tests\TestCase;

class BookingWorkflowTest extends TestCase
{
    /** @test */
    public function complete_booking_workflow_works_correctly()
    {
        $customer = User::factory()->customer()->create();
        $admin = User::factory()->admin()->create();
        $car = Car::factory()->create(['isAvailable' => true]);

        $response = $this->postJson('/api/bookings/reserve', [
            'carId' => $car->id,
            'customerName' => $customer->name,
            'startDate' => now()->addDay()->toDateString(),
            'endDate' => now()->addDays(3)->toDateString(),
        ], $this->getApiHeaders($customer));

        $response->assertStatus(201);
        $bookingId = $response->json('booking.id');

        // The car stays operational; overlapping dates are rejected separately.
        $this->assertDatabaseHas('cars', [
            'id' => $car->id,
            'isAvailable' => true,
        ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $bookingId,
            'status' => 'reserved',
            'user_id' => $customer->id,
        ]);

        // Customers cannot confirm their own booking.
        $response = $this->postJson("/api/bookings/{$bookingId}/confirm", [], $this->getApiHeaders($customer));
        $response->assertStatus(403);

        $response = $this->postJson("/api/bookings/{$bookingId}/confirm", [], $this->getApiHeaders($admin));
        $response->assertStatus(200);

        $this->assertDatabaseHas('bookings', [
            'id' => $bookingId,
            'status' => 'confirmed',
        ]);

        $response = $this->postJson("/api/bookings/{$bookingId}/check-out", [
            'conditionReport' => [
                'exterior' => 'good',
                'interior' => 'excellent',
                'fuel_level' => 'full',
            ],
        ], $this->getApiHeaders($admin));

        $response->assertStatus(200);

        $this->assertDatabaseHas('bookings', [
            'id' => $bookingId,
            'status' => 'in_use',
        ]);

        $response = $this->putJson("/api/bookings/{$bookingId}/return", [
            'conditionReport' => [
                'exterior' => 'good',
                'interior' => 'good',
                'fuel_level' => 'half',
            ],
        ], $this->getApiHeaders($admin));

        $response->assertStatus(200);

        $this->assertDatabaseHas('bookings', [
            'id' => $bookingId,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('cars', [
            'id' => $car->id,
            'isAvailable' => true,
        ]);
    }

    /** @test */
    public function booking_cancellation_workflow_works_correctly()
    {
        $customer = User::factory()->customer()->create();
        $car = Car::factory()->create(['isAvailable' => true]);

        $response = $this->postJson('/api/bookings/reserve', [
            'carId' => $car->id,
            'customerName' => $customer->name,
            'startDate' => now()->addDay()->toDateString(),
            'endDate' => now()->addDays(3)->toDateString(),
        ], $this->getApiHeaders($customer));

        $bookingId = $response->json('booking.id');

        $response = $this->postJson("/api/bookings/{$bookingId}/cancel", [], $this->getApiHeaders($customer));
        $response->assertStatus(200);

        $this->assertDatabaseHas('bookings', [
            'id' => $bookingId,
            'status' => 'canceled',
        ]);

        $this->assertDatabaseHas('cars', [
            'id' => $car->id,
            'isAvailable' => true,
        ]);
    }

    /** @test */
    public function users_cannot_access_another_customers_booking()
    {
        $owner = User::factory()->customer()->create();
        $stranger = User::factory()->customer()->create();
        $booking = Booking::factory()->create(['user_id' => $owner->id]);

        $this->getJson("/api/bookings/{$booking->id}", $this->getApiHeaders($stranger))
            ->assertStatus(403);

        $this->postJson("/api/bookings/{$booking->id}/cancel", [], $this->getApiHeaders($stranger))
            ->assertStatus(403);
    }

    /** @test */
    public function expired_reservations_are_handled_correctly()
    {
        $admin = User::factory()->admin()->create();
        $car = Car::factory()->create(['isAvailable' => false]);

        $booking = Booking::factory()->reserved()->create([
            'car_id' => $car->id,
            'expiresAt' => now()->subMinutes(5),
        ]);

        $response = $this->postJson("/api/bookings/{$booking->id}/confirm", [], $this->getApiHeaders($admin));

        $response->assertStatus(400);
        $response->assertJson(['message' => 'Booking has expired']);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'reserved',
        ]);
    }

    /** @test */
    public function expire_holds_command_marks_stale_holds_expired()
    {
        $car = Car::factory()->create(['isAvailable' => true]);

        $booking = Booking::factory()->reserved()->create([
            'car_id' => $car->id,
            'expiresAt' => now()->subMinute(),
        ]);

        $this->artisan('bookings:expire-holds')->assertExitCode(0);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'expired',
        ]);

        $this->assertDatabaseHas('cars', [
            'id' => $car->id,
            'isAvailable' => true,
        ]);
    }

    /** @test */
    public function double_booking_prevention_works()
    {
        $customer1 = User::factory()->customer()->create();
        $customer2 = User::factory()->customer()->create();
        $car = Car::factory()->create(['isAvailable' => true]);

        $response1 = $this->postJson('/api/bookings/reserve', [
            'carId' => $car->id,
            'customerName' => $customer1->name,
            'startDate' => now()->addDay()->toDateString(),
            'endDate' => now()->addDays(3)->toDateString(),
        ], $this->getApiHeaders($customer1));

        $response1->assertStatus(201);

        // Overlaps the first reservation.
        $response2 = $this->postJson('/api/bookings/reserve', [
            'carId' => $car->id,
            'customerName' => $customer2->name,
            'startDate' => now()->addDays(2)->toDateString(),
            'endDate' => now()->addDays(4)->toDateString(),
        ], $this->getApiHeaders($customer2));

        $response2->assertStatus(409);
        $response2->assertJson(['message' => 'Car is not available for the selected dates']);
    }

    /** @test */
    public function pricing_calculation_is_accurate()
    {
        $customer = User::factory()->customer()->create();
        $car = Car::factory()->create([
            'dailyRate' => 100000,
            'isAvailable' => true,
        ]);

        // Friday to Monday: 3 rental days, 2 of them weekend days.
        $startDate = now()->next('Friday')->startOfDay();
        $endDate = $startDate->copy()->addDays(3);

        $response = $this->postJson('/api/bookings/reserve', [
            'carId' => $car->id,
            'customerName' => $customer->name,
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
            'addOns' => [
                'driver' => true,
            ],
        ], $this->getApiHeaders($customer));

        $response->assertStatus(201);

        $pricing = $response->json('booking.pricing');

        $this->assertEquals(3, $pricing['days']);
        $this->assertEquals(320000, $pricing['base']); // 300,000 + 2 weekend days at 10%
        $this->assertEquals(150000, $pricing['addOnTotal']);
        $this->assertEquals(470000, $pricing['subtotal']);
        $this->assertEquals(84600, $pricing['taxes']); // 18%
        $this->assertEquals(554600, $pricing['total']);
    }
}

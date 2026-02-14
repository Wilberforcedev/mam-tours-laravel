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
        // Setup
        $customer = User::factory()->customer()->create();
        $admin = User::factory()->admin()->create();
        $car = Car::factory()->create(['isAvailable' => true]);

        // Step 1: Customer reserves a car
        $response = $this->postJson('/api/bookings/reserve', [
            'car_id' => $car->id,
            'customerName' => $customer->name,
            'startDate' => now()->addDay()->toISOString(),
            'endDate' => now()->addDays(3)->toISOString(),
        ], $this->getApiHeaders($customer));

        $response->assertStatus(201);
        $bookingId = $response->json('id');

        // Verify car is temporarily unavailable
        $this->assertDatabaseHas('cars', [
            'id' => $car->id,
            'isAvailable' => false,
        ]);

        // Verify booking is reserved
        $this->assertDatabaseHas('bookings', [
            'id' => $bookingId,
            'status' => 'reserved',
        ]);

        // Step 2: Customer confirms the booking
        $response = $this->postJson("/api/bookings/{$bookingId}/confirm", [], $this->getApiHeaders($customer));

        $response->assertStatus(200);

        // Verify booking is confirmed
        $this->assertDatabaseHas('bookings', [
            'id' => $bookingId,
            'status' => 'confirmed',
        ]);

        // Step 3: Admin checks out the vehicle
        $response = $this->postJson("/api/bookings/{$bookingId}/check-out", [
            'conditionReport' => [
                'exterior' => 'good',
                'interior' => 'excellent',
                'fuel_level' => 'full',
            ],
        ], $this->getApiHeaders($admin));

        $response->assertStatus(200);

        // Verify booking is in use
        $this->assertDatabaseHas('bookings', [
            'id' => $bookingId,
            'status' => 'in_use',
        ]);

        // Step 4: Admin marks vehicle as returned
        $response = $this->putJson("/api/bookings/{$bookingId}/return", [
            'conditionReport' => [
                'exterior' => 'good',
                'interior' => 'good',
                'fuel_level' => 'half',
            ],
        ], $this->getApiHeaders($admin));

        $response->assertStatus(200);

        // Verify booking is completed and car is available again
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

        // Reserve a car
        $response = $this->postJson('/api/bookings/reserve', [
            'car_id' => $car->id,
            'customerName' => $customer->name,
            'startDate' => now()->addDay()->toISOString(),
            'endDate' => now()->addDays(3)->toISOString(),
        ], $this->getApiHeaders($customer));

        $bookingId = $response->json('id');

        // Cancel the booking
        $response = $this->postJson("/api/bookings/{$bookingId}/cancel", [], $this->getApiHeaders($customer));

        $response->assertStatus(200);

        // Verify booking is cancelled and car is available again
        $this->assertDatabaseHas('bookings', [
            'id' => $bookingId,
            'status' => 'cancelled',
        ]);

        $this->assertDatabaseHas('cars', [
            'id' => $car->id,
            'isAvailable' => true,
        ]);
    }

    /** @test */
    public function expired_reservations_are_handled_correctly()
    {
        $customer = User::factory()->customer()->create();
        $car = Car::factory()->create(['isAvailable' => true]);

        // Create an expired reservation
        $booking = Booking::factory()->reserved()->create([
            'car_id' => $car->id,
            'user_id' => $customer->id,
            'expiresAt' => now()->subMinutes(5), // Expired 5 minutes ago
        ]);

        // Update car availability (simulate expired reservation cleanup)
        $car->update(['isAvailable' => false]);

        // Try to confirm expired booking
        $response = $this->postJson("/api/bookings/{$booking->id}/confirm", [], $this->getApiHeaders($customer));

        $response->assertStatus(400);
        $response->assertJson(['message' => 'Booking has expired']);

        // Verify booking remains reserved but expired
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'reserved',
        ]);
    }

    /** @test */
    public function double_booking_prevention_works()
    {
        $customer1 = User::factory()->customer()->create();
        $customer2 = User::factory()->customer()->create();
        $car = Car::factory()->create(['isAvailable' => true]);

        // Customer 1 reserves the car
        $response1 = $this->postJson('/api/bookings/reserve', [
            'car_id' => $car->id,
            'customerName' => $customer1->name,
            'startDate' => now()->addDay()->toISOString(),
            'endDate' => now()->addDays(3)->toISOString(),
        ], $this->getApiHeaders($customer1));

        $response1->assertStatus(201);

        // Customer 2 tries to reserve the same car
        $response2 = $this->postJson('/api/bookings/reserve', [
            'car_id' => $car->id,
            'customerName' => $customer2->name,
            'startDate' => now()->addDays(2)->toISOString(),
            'endDate' => now()->addDays(4)->toISOString(),
        ], $this->getApiHeaders($customer2));

        $response2->assertStatus(400);
        $response2->assertJson(['message' => 'Car is not available for the selected dates']);
    }

    /** @test */
    public function pricing_calculation_is_accurate()
    {
        $customer = User::factory()->customer()->create();
        $car = Car::factory()->create([
            'dailyRate' => 100000, // 100,000 UGX per day
            'isAvailable' => true,
        ]);

        // Book for 3 days (including weekend)
        $startDate = now()->next('Friday');
        $endDate = $startDate->copy()->addDays(3); // Friday to Monday

        $response = $this->postJson('/api/bookings/reserve', [
            'car_id' => $car->id,
            'customerName' => $customer->name,
            'startDate' => $startDate->toISOString(),
            'endDate' => $endDate->toISOString(),
            'addOns' => [
                'driver' => true, // 50,000 per day
            ],
        ], $this->getApiHeaders($customer));

        $response->assertStatus(201);

        $pricing = $response->json('pricing');

        // Verify pricing calculation
        $this->assertEquals(3, $pricing['days']);
        $this->assertEquals(300000, $pricing['base']); // 3 days * 100,000
        $this->assertEquals(150000, $pricing['addOnTotal']); // 3 days * 50,000 (driver)
        $this->assertGreaterThan(0, $pricing['taxes']); // 18% tax
        $this->assertGreaterThan(450000, $pricing['total']); // Base + addons + tax
    }
}
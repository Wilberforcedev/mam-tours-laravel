<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Car;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MawejjeJohnBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_mawejje_john_can_book_toyota_noah()
    {
        // Create Mawejje John as a user
        $user = User::factory()->create([
            'name' => 'Mawejje John',
            'email' => 'mawejje.john@example.com',
            'phone' => '+256700123456',
            'role' => 'customer',
        ]);

        // Create Toyota Noah
        $toyotaNoah = Car::create([
            'brand' => 'Toyota',
            'model' => 'Noah',
            'category' => 'Van',
            'seats' => 8,
            'dailyRate' => 100000,
            'numberPlate' => 'UBB 123A',
            'carPicture' => 'Noah.jpeg',
            'isAvailable' => true,
        ]);

        // Login as Mawejje John
        $this->actingAs($user);

        // Prepare booking data
        $bookingData = [
            'carId' => $toyotaNoah->id,
            'customerName' => 'Mawejje John',
            'customerEmail' => 'mawejje.john@example.com',
            'customerPhone' => '+256700123456',
            'startDate' => now()->addDays(2)->toDateString(),
            'endDate' => now()->addDays(7)->toDateString(),
            'paymentMethod' => 'cash',
        ];

        // Make booking request
        $response = $this->postJson('/api/bookings', $bookingData);

        // Debug: Print response if failed
        if ($response->status() !== 201) {
            echo "\n❌ Booking failed with status: " . $response->status() . "\n";
            echo "Response: " . $response->getContent() . "\n";
        }

        // Assert booking was successful
        $response->assertStatus(201)
                 ->assertJson([
                     'message' => 'Booking submitted successfully! Admin will confirm shortly.',
                 ]);

        // Verify booking exists in database
        $this->assertDatabaseHas('bookings', [
            'customerName' => 'Mawejje John',
            'customerEmail' => 'mawejje.john@example.com',
            'car_id' => $toyotaNoah->id,
            'status' => 'pending',
            'payment_method' => 'cash',
        ]);

        // Verify car is marked as unavailable
        $this->assertDatabaseHas('cars', [
            'id' => $toyotaNoah->id,
            'isAvailable' => false,
        ]);

        // Verify booking details
        $booking = Booking::where('customerEmail', 'mawejje.john@example.com')->first();
        $this->assertNotNull($booking);
        $this->assertEquals('Toyota', $booking->car->brand);
        $this->assertEquals('Noah', $booking->car->model);
        $this->assertGreaterThan(0, $booking->totalPrice);
        
        echo "\n✓ Mawejje John successfully booked Toyota Noah!\n";
        echo "  Booking ID: {$booking->id}\n";
        echo "  Vehicle: {$booking->car->brand} {$booking->car->model}\n";
        echo "  Dates: {$booking->startDate} to {$booking->endDate}\n";
        echo "  Total: UGX " . number_format($booking->totalPrice) . "\n";
        echo "  Payment: {$booking->payment_method}\n";
        echo "  Status: {$booking->status}\n";
    }
}

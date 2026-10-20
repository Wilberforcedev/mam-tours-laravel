<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Car;
use Tests\TestCase;

class BookingOwnershipTest extends TestCase
{
    public function test_customers_only_receive_their_own_bookings()
    {
        $customer = $this->createCustomer();
        $otherCustomer = $this->createCustomer();
        $car = Car::factory()->create();
        $ownBooking = Booking::factory()->create([
            'user_id' => $customer->id,
            'car_id' => $car->id,
            'status' => 'pending',
        ]);
        Booking::factory()->create([
            'user_id' => $otherCustomer->id,
            'car_id' => $car->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($customer)->getJson('/api/bookings');

        $response->assertOk();
        $this->assertSame([$ownBooking->id], collect($response->json())->pluck('id')->all());
    }

    public function test_customer_cannot_view_confirm_or_cancel_another_customers_booking()
    {
        $customer = $this->createCustomer();
        $owner = $this->createCustomer();
        $booking = Booking::factory()->create([
            'user_id' => $owner->id,
            'status' => 'pending',
        ]);

        $this->actingAs($customer)->getJson('/api/bookings/' . $booking->id)->assertNotFound();
        $this->actingAs($customer)->postJson('/api/bookings/' . $booking->id . '/confirm')->assertNotFound();
        $this->actingAs($customer)->postJson('/api/bookings/' . $booking->id . '/cancel')->assertNotFound();

        $this->assertSame('pending', $booking->fresh()->status);
    }

    public function test_admin_can_list_all_bookings()
    {
        $admin = $this->createAdmin();
        $firstCustomer = $this->createCustomer();
        $secondCustomer = $this->createCustomer();
        Booking::factory()->create(['user_id' => $firstCustomer->id]);
        Booking::factory()->create(['user_id' => $secondCustomer->id]);

        $response = $this->actingAs($admin)->getJson('/api/bookings');

        $response->assertOk();
        $this->assertCount(2, $response->json());
    }
}

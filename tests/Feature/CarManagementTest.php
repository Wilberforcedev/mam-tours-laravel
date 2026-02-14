<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\User;
use Tests\TestCase;

class CarManagementTest extends TestCase
{
    /** @test */
    public function guests_can_view_cars_list()
    {
        Car::factory()->count(3)->create();

        $response = $this->getJson('/api/cars');

        $response->assertStatus(200);
        $response->assertJsonCount(3);
    }

    /** @test */
    public function guests_can_view_single_car()
    {
        $car = Car::factory()->create();

        $response = $this->getJson("/api/cars/{$car->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $car->id,
            'brand' => $car->brand,
            'model' => $car->model,
        ]);
    }

    /** @test */
    public function only_admins_can_create_cars()
    {
        $customer = User::factory()->customer()->create();
        $admin = User::factory()->admin()->create();

        // Customer cannot create cars
        $response = $this->postJson('/api/cars', [
            'brand' => 'Toyota',
            'model' => 'Camry',
            'numberPlate' => 'ABC123',
            'dailyRate' => 150000,
            'seats' => 5,
        ], $this->getApiHeaders($customer));

        $response->assertStatus(403);

        // Admin can create cars
        $response = $this->postJson('/api/cars', [
            'brand' => 'Toyota',
            'model' => 'Camry',
            'numberPlate' => 'ABC123',
            'dailyRate' => 150000,
            'seats' => 5,
        ], $this->getApiHeaders($admin));

        $response->assertStatus(201);
        $this->assertDatabaseHas('cars', [
            'brand' => 'Toyota',
            'model' => 'Camry',
            'numberPlate' => 'ABC123',
        ]);
    }

    /** @test */
    public function only_admins_can_update_cars()
    {
        $car = Car::factory()->create();
        $customer = User::factory()->customer()->create();
        $admin = User::factory()->admin()->create();

        // Customer cannot update cars
        $response = $this->putJson("/api/cars/{$car->id}", [
            'brand' => 'Updated Brand',
        ], $this->getApiHeaders($customer));

        $response->assertStatus(403);

        // Admin can update cars
        $response = $this->putJson("/api/cars/{$car->id}", [
            'brand' => 'Updated Brand',
        ], $this->getApiHeaders($admin));

        $response->assertStatus(200);
        $this->assertDatabaseHas('cars', [
            'id' => $car->id,
            'brand' => 'Updated Brand',
        ]);
    }

    /** @test */
    public function only_admins_can_delete_cars()
    {
        $car = Car::factory()->create();
        $customer = User::factory()->customer()->create();
        $admin = User::factory()->admin()->create();

        // Customer cannot delete cars
        $response = $this->deleteJson("/api/cars/{$car->id}", [], $this->getApiHeaders($customer));

        $response->assertStatus(403);

        // Admin can delete cars
        $response = $this->deleteJson("/api/cars/{$car->id}", [], $this->getApiHeaders($admin));

        $response->assertStatus(200);
        $this->assertDatabaseMissing('cars', ['id' => $car->id]);
    }

    /** @test */
    public function car_creation_requires_valid_data()
    {
        $admin = User::factory()->admin()->create();

        $response = $this->postJson('/api/cars', [
            'brand' => '', // Invalid: empty
            'model' => 'Camry',
            'numberPlate' => 'ABC123',
            'dailyRate' => -100, // Invalid: negative
            'seats' => 0, // Invalid: zero
        ], $this->getApiHeaders($admin));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['brand', 'dailyRate', 'seats']);
    }

    /** @test */
    public function number_plate_must_be_unique()
    {
        $existingCar = Car::factory()->create(['numberPlate' => 'ABC123']);
        $admin = User::factory()->admin()->create();

        $response = $this->postJson('/api/cars', [
            'brand' => 'Toyota',
            'model' => 'Camry',
            'numberPlate' => 'ABC123', // Duplicate
            'dailyRate' => 150000,
            'seats' => 5,
        ], $this->getApiHeaders($admin));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['numberPlate']);
    }

    /** @test */
    public function cars_can_be_filtered_by_availability()
    {
        Car::factory()->count(2)->create(['isAvailable' => true]);
        Car::factory()->count(1)->unavailable()->create();

        $response = $this->getJson('/api/cars?available=true');

        $response->assertStatus(200);
        $response->assertJsonCount(2);
    }
}
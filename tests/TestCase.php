<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run migrations for testing
        Artisan::call('migrate');
        
        // Seed essential data
        $this->seed();
    }

    /**
     * Create a test user
     */
    protected function createUser($attributes = [])
    {
        return \App\Models\User::factory()->create($attributes);
    }

    /**
     * Create an admin user
     */
    protected function createAdmin($attributes = [])
    {
        return $this->createUser(array_merge(['role' => 'admin'], $attributes));
    }

    /**
     * Create a customer user
     */
    protected function createCustomer($attributes = [])
    {
        return $this->createUser(array_merge(['role' => 'customer'], $attributes));
    }

    /**
     * Create a test car
     */
    protected function createCar($attributes = [])
    {
        return \App\Models\Car::factory()->create($attributes);
    }

    /**
     * Create a test booking
     */
    protected function createBooking($attributes = [])
    {
        return \App\Models\Booking::factory()->create($attributes);
    }

    /**
     * Act as authenticated user
     */
    protected function actingAsUser($user = null)
    {
        $user = $user ?: $this->createUser();
        return $this->actingAs($user);
    }

    /**
     * Act as admin user
     */
    protected function actingAsAdmin($admin = null)
    {
        $admin = $admin ?: $this->createAdmin();
        return $this->actingAs($admin);
    }

    /**
     * Act as customer user
     */
    protected function actingAsCustomer($customer = null)
    {
        $customer = $customer ?: $this->createCustomer();
        return $this->actingAs($customer);
    }

    /**
     * Get API headers with authentication
     */
    protected function getApiHeaders($user = null)
    {
        if ($user) {
            $token = $user->createApiToken();
            return [
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ];
        }

        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }
}

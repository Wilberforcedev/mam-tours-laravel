<?php

namespace Database\Factories;

use App\Models\Car;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition()
    {
        $startDate = $this->faker->dateTimeBetween('now', '+30 days');
        $endDate = $this->faker->dateTimeBetween($startDate, $startDate->format('Y-m-d H:i:s') . ' +7 days');
        
        $pricing = [
            'days' => $this->faker->numberBetween(1, 7),
            'base' => $this->faker->numberBetween(100000, 500000),
            'addOnTotal' => 0,
            'subtotal' => $this->faker->numberBetween(100000, 500000),
            'taxes' => $this->faker->numberBetween(18000, 90000),
            'deposit' => $this->faker->numberBetween(20000, 100000),
            'total' => $this->faker->numberBetween(138000, 690000),
        ];

        return [
            'car_id' => Car::factory(),
            'user_id' => User::factory(),
            'customerName' => $this->faker->name(),
            'startDate' => $startDate,
            'endDate' => $endDate,
            'status' => 'confirmed',
            'pricing' => $pricing,
            'addOns' => [],
            'payment' => [
                'status' => 'pending',
                'amount' => $pricing['total'],
            ],
            'conditionReports' => [],
        ];
    }

    /**
     * Create a reserved booking.
     */
    public function reserved()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'reserved',
                'expiresAt' => now()->addMinutes(30),
            ];
        });
    }

    /**
     * Create a confirmed booking.
     */
    public function confirmed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'confirmed',
                'confirmedAt' => now(),
            ];
        });
    }

    /**
     * Create an in-use booking.
     */
    public function inUse()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'in_use',
                'confirmedAt' => now()->subDays(1),
                'checkedOutAt' => now()->subHours(2),
            ];
        });
    }

    /**
     * Create a completed booking.
     */
    public function completed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'completed',
                'confirmedAt' => now()->subDays(3),
                'checkedOutAt' => now()->subDays(2),
                'returnedAt' => now()->subDay(),
            ];
        });
    }

    /**
     * Create a cancelled booking.
     */
    public function cancelled()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'cancelled',
                'canceledAt' => now()->subHours(1),
            ];
        });
    }
}
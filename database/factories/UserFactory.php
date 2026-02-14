<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'phone' => $this->faker->phoneNumber(),
            'role' => 'customer',
            'email_notifications' => true,
            'sms_notifications' => false,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified()
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
            ];
        });
    }

    /**
     * Create an admin user.
     */
    public function admin()
    {
        return $this->state(function (array $attributes) {
            return [
                'role' => 'admin',
            ];
        });
    }

    /**
     * Create a customer user.
     */
    public function customer()
    {
        return $this->state(function (array $attributes) {
            return [
                'role' => 'customer',
            ];
        });
    }

    /**
     * Create a locked user account.
     */
    public function locked()
    {
        return $this->state(function (array $attributes) {
            return [
                'failed_login_attempts' => 5,
                'locked_until' => now()->addMinutes(30),
            ];
        });
    }
}

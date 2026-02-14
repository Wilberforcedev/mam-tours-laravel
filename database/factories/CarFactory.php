<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CarFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition()
    {
        $brands = ['Toyota', 'Mercedes-Benz', 'BMW', 'Audi', 'Honda', 'Nissan', 'Jeep'];
        $models = ['Camry', 'Corolla', 'GLE', 'X5', 'Civic', 'Altima', 'Wrangler'];
        $categories = ['sedan', 'suv', 'hatchback', 'luxury', 'economy'];

        return [
            'carPicture' => $this->faker->imageUrl(640, 480, 'cars'),
            'brand' => $this->faker->randomElement($brands),
            'model' => $this->faker->randomElement($models),
            'numberPlate' => strtoupper($this->faker->bothify('??###??')),
            'dailyRate' => $this->faker->numberBetween(50000, 300000),
            'seats' => $this->faker->randomElement([4, 5, 7, 8]),
            'category' => $this->faker->randomElement($categories),
            'isAvailable' => true,
        ];
    }

    /**
     * Create an unavailable car.
     */
    public function unavailable()
    {
        return $this->state(function (array $attributes) {
            return [
                'isAvailable' => false,
            ];
        });
    }

    /**
     * Create a luxury car.
     */
    public function luxury()
    {
        return $this->state(function (array $attributes) {
            return [
                'brand' => $this->faker->randomElement(['Mercedes-Benz', 'BMW', 'Audi', 'Jaguar']),
                'category' => 'luxury',
                'dailyRate' => $this->faker->numberBetween(200000, 500000),
            ];
        });
    }

    /**
     * Create an economy car.
     */
    public function economy()
    {
        return $this->state(function (array $attributes) {
            return [
                'brand' => $this->faker->randomElement(['Toyota', 'Honda', 'Nissan']),
                'category' => 'economy',
                'dailyRate' => $this->faker->numberBetween(50000, 150000),
            ];
        });
    }
}
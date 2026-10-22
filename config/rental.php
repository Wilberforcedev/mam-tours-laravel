<?php

return [
    // MAM Tours is configured for UGX quotations by default. Configure a provider-supported
    // currency separately before enabling card payments; never silently convert amounts.
    'currency' => env('RENTAL_CURRENCY', 'UGX'),
    'minimum_days' => (int) env('RENTAL_MINIMUM_DAYS', 1),
    'maximum_days' => (int) env('RENTAL_MAXIMUM_DAYS', 30),
    'default_pickup_time' => env('RENTAL_DEFAULT_PICKUP_TIME', '09:00'),
    'default_return_time' => env('RENTAL_DEFAULT_RETURN_TIME', '09:00'),
    'weekend_surcharge_rate' => (float) env('RENTAL_WEEKEND_SURCHARGE_RATE', 0.10),
    'weekly_discount_rate' => (float) env('RENTAL_WEEKLY_DISCOUNT_RATE', 0.10),
    'additional_driver_daily_fee' => (int) env('RENTAL_ADDITIONAL_DRIVER_DAILY_FEE', 50000),
    'delivery_fee' => (int) env('RENTAL_DELIVERY_FEE', 0),
    'child_seat_daily_fee' => (int) env('RENTAL_CHILD_SEAT_DAILY_FEE', 10000),
    'tax_rate' => (float) env('RENTAL_TAX_RATE', 0.18),
    'deposit_rate' => (float) env('RENTAL_DEPOSIT_RATE', 0.20),
];

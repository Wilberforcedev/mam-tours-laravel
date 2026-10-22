<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Car;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingAvailabilityService
{
    /**
     * Must be called inside DB::transaction(). Locking the car row serializes
     * concurrent booking attempts for the same vehicle across supported SQL databases.
     */
    public function lockAndAssertAvailable(int $carId, string $startDate, string $endDate, ?int $ignoreBookingId = null): Car
    {
        $car = Car::whereKey($carId)->lockForUpdate()->firstOrFail();

        $query = Booking::where('car_id', $carId)
            ->whereIn('status', ['pending', 'reserved', 'confirmed', 'in_use'])
            ->where('startDate', '<', $endDate)
            ->where('endDate', '>', $startDate);

        if ($ignoreBookingId) {
            $query->where('id', '<>', $ignoreBookingId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'startDate' => ['This vehicle is already booked for some or all of the selected dates. Please choose different dates.'],
            ]);
        }

        return $car;
    }

    public function isAvailable(int $carId, string $startDate, string $endDate): bool
    {
        return !Booking::where('car_id', $carId)
            ->whereIn('status', ['pending', 'reserved', 'confirmed', 'in_use'])
            ->where('startDate', '<', $endDate)
            ->where('endDate', '>', $startDate)
            ->exists();
    }
}

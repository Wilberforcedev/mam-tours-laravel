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

        $maintenanceQuery = \\App\\Models\\VehicleMaintenanceRecord::where('car_id', $carId)
            ->where('blocks_rental', true)
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->where(function ($q) use ($endDate) {
                $q->whereNull('scheduled_for')->orWhere('scheduled_for', '<', $endDate);
            });
        if ($query->exists() || $maintenanceQuery->exists()) {
            throw ValidationException::withMessages([
                'startDate' => ['This vehicle is already booked for some or all of the selected dates. Please choose different dates.'],
            ]);
        }

        return $car;
    }

    public function isAvailable(int $carId, string $startDate, string $endDate): bool
    {
        $bookingOverlap = Booking::where('car_id', $carId)
            ->whereIn('status', ['pending', 'reserved', 'confirmed', 'in_use'])
            ->where('startDate', '<', $endDate)
            ->where('endDate', '>', $startDate)->exists();
        $maintenanceBlock = \\App\\Models\\VehicleMaintenanceRecord::where('car_id', $carId)
            ->where('blocks_rental', true)
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->where(function ($q) use ($endDate) {
                $q->whereNull('scheduled_for')->orWhere('scheduled_for', '<', $endDate);
            })->exists();
        return !$bookingOverlap && !$maintenanceBlock;
    }
}

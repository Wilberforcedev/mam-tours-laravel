<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Car;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireBookingHolds extends Command
{
    protected $signature = 'bookings:expire-holds';

    protected $description = 'Expire stale booking holds and release their vehicles';

    public function handle(): int
    {
        $expired = 0;

        Booking::query()
            ->whereIn('status', Booking::HOLD_STATUSES)
            ->whereNotNull('expiresAt')
            ->where('expiresAt', '<', now())
            ->select('id')
            ->chunkById(200, function ($bookings) use (&$expired) {
                foreach ($bookings as $booking) {
                    if ($this->expire($booking->id)) {
                        $expired++;
                    }
                }
            });

        $this->info("Expired {$expired} booking hold(s).");

        return self::SUCCESS;
    }

    private function expire(int $bookingId): bool
    {
        return DB::transaction(function () use ($bookingId) {
            $booking = Booking::whereKey($bookingId)->lockForUpdate()->first();

            if (!$booking
                || !in_array($booking->status, Booking::HOLD_STATUSES, true)
                || !$booking->expiresAt
                || !$booking->expiresAt->isPast()) {
                return false;
            }

            $booking->update(['status' => 'expired']);

            if ($booking->car_id) {
                $stillBooked = Booking::active()->where('car_id', $booking->car_id)->exists();

                if (!$stillBooked) {
                    Car::whereKey($booking->car_id)->update(['isAvailable' => true]);
                }
            }

            return true;
        });
    }
}

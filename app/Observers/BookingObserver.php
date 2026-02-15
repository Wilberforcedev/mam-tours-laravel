<?php

namespace App\Observers;

use App\Models\Booking;
use App\Services\CacheManager;

class BookingObserver
{
    private CacheManager $cacheManager;

    public function __construct(CacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    /**
     * Handle the Booking "created" event.
     */
    public function created(Booking $booking): void
    {
        // Invalidate user's booking cache
        if ($booking->user_id) {
            $this->cacheManager->invalidateBookingCache($booking->user_id);
        }
        
        // Invalidate car cache as availability might have changed
        if ($booking->car_id) {
            $this->cacheManager->invalidateCarCache($booking->car_id);
        }
    }

    /**
     * Handle the Booking "updated" event.
     */
    public function updated(Booking $booking): void
    {
        // Invalidate user's booking cache
        if ($booking->user_id) {
            $this->cacheManager->invalidateBookingCache($booking->user_id);
        }
        
        // Invalidate car cache
        if ($booking->car_id) {
            $this->cacheManager->invalidateCarCache($booking->car_id);
        }
    }

    /**
     * Handle the Booking "deleted" event.
     */
    public function deleted(Booking $booking): void
    {
        // Invalidate user's booking cache
        if ($booking->user_id) {
            $this->cacheManager->invalidateBookingCache($booking->user_id);
        }
        
        // Invalidate car cache
        if ($booking->car_id) {
            $this->cacheManager->invalidateCarCache($booking->car_id);
        }
    }
}

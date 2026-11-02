<?php

namespace App\Observers;

use App\Models\Booking;
use App\Services\CacheManager;
use App\Services\WebhookService;

class BookingObserver
{
    private CacheManager $cacheManager;
    private WebhookService $webhookService;

    public function __construct(CacheManager $cacheManager, WebhookService $webhookService)
    {
        $this->cacheManager = $cacheManager;
        $this->webhookService = $webhookService;
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

        // Dispatch webhook
        $this->webhookService->dispatch('booking.created', [
            'booking_id' => $booking->id,
            'car_id' => $booking->car_id,
            'customer_name' => $booking->customerName,
            'start_date' => $booking->startDate,
            'end_date' => $booking->endDate,
            'status' => $booking->status,
            'created_at' => $booking->created_at->toIso8601String(),
        ]);
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

        // Dispatch webhook for status changes
        if ($booking->isDirty('status')) {
            $eventType = 'booking.updated';
            
            if ($booking->status === 'confirmed') {
                $eventType = 'booking.confirmed';
            } elseif (in_array($booking->status, ['canceled', 'cancelled'], true)) {
                $eventType = 'booking.cancelled';
            }

            $this->webhookService->dispatch($eventType, [
                'booking_id' => $booking->id,
                'car_id' => $booking->car_id,
                'status' => $booking->status,
                'updated_at' => $booking->updated_at->toIso8601String(),
            ]);
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

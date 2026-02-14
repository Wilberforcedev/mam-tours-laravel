<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class SendBookingReminders extends Command
{
    protected $signature = 'bookings:send-reminders';
    protected $description = 'Send automated booking reminders and notifications';

    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    public function handle()
    {
        $this->info('Starting booking reminder process...');

        $this->sendPaymentReminders();
        $this->sendPickupReminders();
        $this->sendReturnReminders();
        $this->sendOverdueNotifications();

        $this->info('Booking reminder process completed.');
    }

    private function sendPaymentReminders()
    {
        $this->info('Sending payment reminders...');

        // Find bookings that need payment reminders (created 23 hours ago, still pending payment)
        $bookings = Booking::where('payment_status', 'pending')
            ->where('created_at', '<=', Carbon::now()->subHours(23))
            ->where('created_at', '>=', Carbon::now()->subHours(24))
            ->with(['user', 'car'])
            ->get();

        foreach ($bookings as $booking) {
            $this->notificationService->sendPaymentReminder($booking);
            $this->line("Payment reminder sent for booking #{$booking->id}");
        }

        $this->info("Sent {$bookings->count()} payment reminders.");
    }

    private function sendPickupReminders()
    {
        $this->info('Sending pickup reminders...');

        // Find bookings starting tomorrow
        $tomorrow = Carbon::tomorrow();
        $bookings = Booking::where('payment_status', 'completed')
            ->whereDate('startDate', $tomorrow)
            ->with(['user', 'car'])
            ->get();

        foreach ($bookings as $booking) {
            $this->notificationService->sendPickupReminder($booking);
            $this->line("Pickup reminder sent for booking #{$booking->id}");
        }

        $this->info("Sent {$bookings->count()} pickup reminders.");
    }

    private function sendReturnReminders()
    {
        $this->info('Sending return reminders...');

        // Find bookings ending tomorrow
        $tomorrow = Carbon::tomorrow();
        $bookings = Booking::where('status', 'in_use')
            ->whereDate('endDate', $tomorrow)
            ->with(['user', 'car'])
            ->get();

        foreach ($bookings as $booking) {
            $this->notificationService->sendReturnReminder($booking);
            $this->line("Return reminder sent for booking #{$booking->id}");
        }

        $this->info("Sent {$bookings->count()} return reminders.");
    }

    private function sendOverdueNotifications()
    {
        $this->info('Sending overdue notifications...');

        // Find bookings that are overdue (end date passed, still in use)
        $bookings = Booking::where('status', 'in_use')
            ->where('endDate', '<', Carbon::now())
            ->with(['user', 'car'])
            ->get();

        foreach ($bookings as $booking) {
            $this->notificationService->sendOverdueNotification($booking);
            $this->line("Overdue notification sent for booking #{$booking->id}");
        }

        $this->info("Sent {$bookings->count()} overdue notifications.");
    }
}
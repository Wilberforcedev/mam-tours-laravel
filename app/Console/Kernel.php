<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Send booking reminders every hour
        $schedule->command('bookings:send-reminders')->hourly();
        
        // Database backup daily at 2 AM with verification
        $schedule->command('db:backup --verify')
            ->dailyAt('02:00')
            ->onFailure(function () {
                \Log::error('Scheduled database backup failed');
            });
        
        // Notify fleet/admin staff of service and vehicle-document deadlines daily.
        $schedule->command('fleet:send-maintenance-reminders')->dailyAt('07:00')->withoutOverlapping();

        // Clean up expired bookings daily at 3 AM
        $schedule->call(function () {
            \App\Models\Booking::where('status', 'pending')
                ->where('expiresAt', '<', now())
                ->update(['status' => 'expired']);
        })->dailyAt('03:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

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
        
        // Clean up expired bookings daily at 2 AM
        $schedule->call(function () {
            \App\Models\Booking::where('status', 'pending')
                ->where('expiresAt', '<', now())
                ->update(['status' => 'expired']);
        })->dailyAt('02:00');
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

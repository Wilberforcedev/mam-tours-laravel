<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\User;
use App\Models\VehicleMaintenanceRecord;
use Illuminate\Console\Command;

class SendFleetMaintenanceReminders extends Command
{
    protected $signature = 'fleet:send-maintenance-reminders';
    protected $description = 'Notify fleet/admin staff about upcoming service and expiring documents';

    public function handle()
    {
        $today = now()->toDateString();
        $horizon = now()->addDays(30)->toDateString();
        $records = VehicleMaintenanceRecord::with('car')->where(function ($q) use ($today, $horizon) {
            $q->whereBetween('scheduled_for', [$today, $horizon])
              ->orWhereBetween('insurance_expiry', [$today, $horizon])
              ->orWhereBetween('licence_expiry', [$today, $horizon]);
        })->get();
        $recipients = User::whereIn('role', ['admin', 'fleet'])->get();
        $sent = 0;

        foreach ($records as $record) {
            $subject = 'Fleet reminder: ' . $record->car->brand . ' ' . $record->car->model . ' (record #' . $record->id . ')';
            $parts = [];
            if ($record->scheduled_for && $record->scheduled_for->gte(now()->startOfDay()) && $record->scheduled_for->lte(now()->addDays(30)->endOfDay())) {
                $parts[] = 'Service/maintenance scheduled for ' . $record->scheduled_for->toDateString();
            }
            if ($record->insurance_expiry && $record->insurance_expiry->gte(now()->startOfDay()) && $record->insurance_expiry->lte(now()->addDays(30)->endOfDay())) {
                $parts[] = 'Insurance expires on ' . $record->insurance_expiry->toDateString();
            }
            if ($record->licence_expiry && $record->licence_expiry->gte(now()->startOfDay()) && $record->licence_expiry->lte(now()->addDays(30)->endOfDay())) {
                $parts[] = 'Vehicle licence expires on ' . $record->licence_expiry->toDateString();
            }
            $message = implode("\n", $parts);
            foreach ($recipients as $recipient) {
                $alreadySent = Notification::where('user_id', $recipient->id)
                    ->where('type', 'fleet_maintenance_reminder')
                    ->where('subject', $subject)
                    ->whereDate('created_at', $today)->exists();
                if (!$alreadySent) {
                    Notification::create([
                        'user_id' => $recipient->id,
                        'booking_id' => null,
                        'type' => 'fleet_maintenance_reminder',
                        'subject' => $subject,
                        'message' => $message,
                        'status' => 'pending',
                        'channel' => 'in_app',
                    ]);
                    $sent++;
                }
            }
        }

        $this->info("Fleet reminders sent: {$sent}");
        return self::SUCCESS;
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReconcileLegacyVehicleAvailabilityFlags extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('cars') || !Schema::hasTable('bookings')) return;

        DB::table('cars')->where('isAvailable', false)->orderBy('id')->chunk(100, function ($cars) {
            foreach ($cars as $car) {
                $hasActiveBooking = DB::table('bookings')->where('car_id', $car->id)
                    ->whereIn('status', ['pending', 'reserved', 'confirmed', 'in_use'])
                    ->where(function ($q) {
                        $q->whereNotIn('status', ['pending', 'reserved'])
                          ->orWhereNull('expiresAt')
                          ->orWhere('expiresAt', '>', now());
                    })->exists();

                $hasActiveMaintenance = Schema::hasTable('vehicle_maintenance_records')
                    && DB::table('vehicle_maintenance_records')->where('car_id', $car->id)
                        ->where('blocks_rental', true)
                        ->where(function ($q) {
                            $q->where('status', 'in_progress')
                              ->orWhere(function ($scheduled) {
                                  $scheduled->where('status', 'scheduled')
                                      ->where(function ($dates) {
                                          $dates->whereNull('scheduled_for')->orWhere('scheduled_for', '<=', now()->toDateString());
                                      });
                              });
                        })->exists();

                // Older booking flows marked a car globally unavailable for any reservation.
                // Re-enable only when an active booking explains that flag and no maintenance does.
                if ($hasActiveBooking && !$hasActiveMaintenance) {
                    DB::table('cars')->where('id', $car->id)->update(['isAvailable' => true]);
                }
            }
        });
    }

    public function down()
    {
        // Do not reapply stale global booking flags during rollback.
    }
}

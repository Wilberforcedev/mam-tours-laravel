<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVehicleHandoverReadingsToBookings extends Migration
{
    public function up()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedInteger('pickup_odometer_km')->nullable();
            $table->unsignedInteger('return_odometer_km')->nullable();
            $table->unsignedTinyInteger('pickup_fuel_percent')->nullable();
            $table->unsignedTinyInteger('return_fuel_percent')->nullable();
            $table->text('pickup_condition_notes')->nullable();
            $table->text('return_condition_notes')->nullable();
        });
    }

    public function down()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'pickup_odometer_km', 'return_odometer_km',
                'pickup_fuel_percent', 'return_fuel_percent',
                'pickup_condition_notes', 'return_condition_notes',
            ]);
        });
    }
}

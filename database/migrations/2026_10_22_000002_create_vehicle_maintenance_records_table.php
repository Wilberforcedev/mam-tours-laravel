<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVehicleMaintenanceRecordsTable extends Migration
{
    public function up()
    {
        Schema::create('vehicle_maintenance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id')->constrained('cars')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 32); // scheduled_service, repair, accident, inspection
            $table->string('status', 24)->default('scheduled');
            $table->text('description');
            $table->unsignedInteger('odometer_km')->nullable();
            $table->unsignedBigInteger('cost_minor')->default(0);
            $table->char('currency', 3)->default('UGX');
            $table->date('scheduled_for')->nullable();
            $table->date('completed_on')->nullable();
            $table->date('insurance_expiry')->nullable();
            $table->date('licence_expiry')->nullable();
            $table->boolean('blocks_rental')->default(true);
            $table->timestamps();
            $table->index(['car_id', 'status', 'scheduled_for']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('vehicle_maintenance_records');
    }
}

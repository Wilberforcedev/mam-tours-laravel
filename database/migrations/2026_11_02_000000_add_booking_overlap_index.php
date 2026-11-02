<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBookingOverlapIndex extends Migration
{
    public function up()
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Serves the availability query: car_id + startDate < end AND endDate > start.
            $table->index(['car_id', 'startDate', 'endDate'], 'idx_bookings_overlap');
            $table->index('expiresAt', 'idx_bookings_expires');
        });
    }

    public function down()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('idx_bookings_overlap');
            $table->dropIndex('idx_bookings_expires');
        });
    }
}

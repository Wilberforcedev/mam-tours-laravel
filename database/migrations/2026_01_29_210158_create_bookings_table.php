<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id')->constrained('cars');
            $table->string('customerName', 200);
            $table->dateTime('startDate');
            $table->dateTime('endDate');
            $table->string('status', 50);
            $table->json('pricing')->nullable();
            $table->json('addOns')->nullable();
            $table->json('payment')->nullable();
            $table->json('conditionReports')->nullable();
            $table->dateTime('expiresAt')->nullable();
            $table->dateTime('confirmedAt')->nullable();
            $table->dateTime('checkedOutAt')->nullable();
            $table->dateTime('returnedAt')->nullable();
            $table->dateTime('canceledAt')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bookings');
    }
}

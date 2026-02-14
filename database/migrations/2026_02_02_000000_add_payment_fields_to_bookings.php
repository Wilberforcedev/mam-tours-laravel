<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentFieldsToBookings extends Migration
{
    public function up()
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'payment_method')) {
                $table->string('payment_method')->nullable()->after('status');
            }
            if (!Schema::hasColumn('bookings', 'payment_status')) {
                $table->string('payment_status')->default('pending')->after('payment_method');
            }
            if (!Schema::hasColumn('bookings', 'phone_number')) {
                $table->string('phone_number')->nullable()->after('payment_status');
            }
        });
    }

    public function down()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'payment_status', 'phone_number']);
        });
    }
}

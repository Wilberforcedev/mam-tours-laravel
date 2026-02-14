<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserAndKycToBookings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            }
            
            if (!Schema::hasColumn('bookings', 'kyc_id')) {
                $table->unsignedBigInteger('kyc_id')->nullable()->after('user_id');
                $table->foreign('kyc_id')->references('id')->on('kyc_verifications')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'kyc_id')) {
                $table->dropForeign(['kyc_id']);
                $table->dropColumn('kyc_id');
            }
            
            if (Schema::hasColumn('bookings', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });
    }
}

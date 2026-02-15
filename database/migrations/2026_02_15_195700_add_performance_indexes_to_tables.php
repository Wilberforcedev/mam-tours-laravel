<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPerformanceIndexesToTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add indexes to cars table
        Schema::table('cars', function (Blueprint $table) {
            $table->index('isAvailable', 'idx_cars_available');
            $table->index('category', 'idx_cars_category');
            $table->index(['isAvailable', 'category'], 'idx_cars_available_category');
        });

        // Add indexes to bookings table
        Schema::table('bookings', function (Blueprint $table) {
            $table->index('user_id', 'idx_bookings_user');
            $table->index('car_id', 'idx_bookings_car');
            $table->index('status', 'idx_bookings_status');
            $table->index(['startDate', 'endDate'], 'idx_bookings_dates');
            $table->index(['status', 'startDate'], 'idx_bookings_status_date');
            $table->index('payment_status', 'idx_bookings_payment_status');
        });

        // Add indexes to users table
        Schema::table('users', function (Blueprint $table) {
            // Email already has unique index, but add role index
            $table->index('role', 'idx_users_role');
            $table->index('created_at', 'idx_users_created');
        });

        // Add indexes to kyc_verifications table
        Schema::table('kyc_verifications', function (Blueprint $table) {
            $table->index('user_id', 'idx_kyc_user');
            $table->index('status', 'idx_kyc_status');
        });

        // Add indexes to reviews table
        Schema::table('reviews', function (Blueprint $table) {
            $table->index('user_id', 'idx_reviews_user');
            $table->index('is_approved', 'idx_reviews_approved');
            $table->index(['is_approved', 'created_at'], 'idx_reviews_approved_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropIndex('idx_cars_available');
            $table->dropIndex('idx_cars_category');
            $table->dropIndex('idx_cars_available_category');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('idx_bookings_user');
            $table->dropIndex('idx_bookings_car');
            $table->dropIndex('idx_bookings_status');
            $table->dropIndex('idx_bookings_dates');
            $table->dropIndex('idx_bookings_status_date');
            $table->dropIndex('idx_bookings_payment_status');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_role');
            $table->dropIndex('idx_users_created');
        });

        Schema::table('kyc_verifications', function (Blueprint $table) {
            $table->dropIndex('idx_kyc_user');
            $table->dropIndex('idx_kyc_status');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex('idx_reviews_user');
            $table->dropIndex('idx_reviews_approved');
            $table->dropIndex('idx_reviews_approved_date');
        });
    }
}

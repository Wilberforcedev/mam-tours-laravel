<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFinancialTransactionsTable extends Migration
{
    public function up()
    {
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 32); // payment, refund, deposit_received, deposit_returned, adjustment
            $table->string('status', 24)->default('pending');
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3)->default('UGX');
            $table->string('provider', 40)->nullable();
            $table->string('provider_reference')->nullable()->unique();
            $table->string('idempotency_key')->nullable()->unique();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();
            $table->index(['booking_id', 'status', 'type']);
            $table->index(['currency', 'occurred_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('financial_transactions');
    }
}

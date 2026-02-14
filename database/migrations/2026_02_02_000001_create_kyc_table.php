<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKycTable extends Migration
{
    public function up()
    {
        Schema::create('kyc_verifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('id_type')->nullable(); // NIN, Passport
            $table->string('id_number')->nullable();
            $table->string('permit_number')->nullable();
            $table->string('id_document_path')->nullable();
            $table->string('permit_document_path')->nullable();
            $table->string('status')->default('pending'); // pending, verified, rejected
            $table->text('rejection_reason')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('kyc_verifications');
    }
}

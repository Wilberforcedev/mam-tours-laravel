<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('kyc_verifications', function (Blueprint $table) {
            $table->string('id_original_document_path')->nullable()->after('id_document_path');
            $table->string('permit_original_document_path')->nullable()->after('permit_document_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kyc_verifications', function (Blueprint $table) {
            $table->dropColumn(['id_original_document_path', 'permit_original_document_path']);
        });
    }
};

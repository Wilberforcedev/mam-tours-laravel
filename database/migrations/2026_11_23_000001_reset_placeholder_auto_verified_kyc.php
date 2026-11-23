<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetPlaceholderAutoVerifiedKyc extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('kyc_verifications')) {
            return;
        }

        DB::table('kyc_verifications')
            ->where('status', 'auto_verified')
            ->update([
                'status' => 'pending',
                'verified_at' => null,
            ]);
    }

    public function down()
    {
        // Auto-verified records are intentionally left pending during rollback.
    }
}

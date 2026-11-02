<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MakeCarPictureNullable extends Migration
{
    /**
     * Cars can be listed without a photo, but the original column was NOT NULL.
     * doctrine/dbal is not installed, so this uses driver-specific SQL instead
     * of Blueprint::change(). SQLite is skipped: it is only used by tests and
     * fresh databases already create the column as nullable.
     */
    public function up()
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE cars MODIFY carPicture VARCHAR(100) NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE cars ALTER COLUMN "carPicture" DROP NOT NULL');
        }
    }

    public function down()
    {
        // Reverting would fail on rows that legitimately have no picture.
    }
}

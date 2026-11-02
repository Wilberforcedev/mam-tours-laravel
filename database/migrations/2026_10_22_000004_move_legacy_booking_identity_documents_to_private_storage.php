<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MoveLegacyBookingIdentityDocumentsToPrivateStorage extends Migration
{
    public function up()
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('bookings')
            || !\Illuminate\Support\Facades\Schema::hasColumn('bookings', 'idDocument')) {
            return;
        }

        $public = Storage::disk('public');
        $private = Storage::disk('private');
        DB::table('bookings')->whereNotNull('idDocument')->orderBy('id')->chunk(100, function ($rows) use ($public, $private) {
            foreach ($rows as $row) {
                $path = $row->idDocument;
                if (!$path || !$public->exists($path)) continue;
                if (!$private->exists($path)) {
                    $stream = $public->readStream($path);
                    if ($stream === false) continue;
                    $private->put($path, $stream);
                    if (is_resource($stream)) fclose($stream);
                }
                if ($private->exists($path)) $public->delete($path);
            }
        });
    }

    public function down()
    {
        // Identity documents must not be copied back to public storage.
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MoveLegacyKycDocumentsToPrivateStorage extends Migration
{
    public function up()
    {
        $diskPublic = Storage::disk('public');
        $diskPrivate = Storage::disk('private');
        $columns = ['id_document_path', 'permit_document_path', 'id_original_document_path', 'permit_original_document_path'];

        DB::table('kyc_verifications')->orderBy('id')->chunk(100, function ($records) use ($diskPublic, $diskPrivate, $columns) {
            foreach ($records as $record) {
                $changes = [];
                foreach ($columns as $column) {
                    $path = $record->{$column} ?? null;
                    if (!$path || !$diskPublic->exists($path)) continue;
                    if (!$diskPrivate->exists($path)) {
                        $stream = $diskPublic->readStream($path);
                        if ($stream === false) continue;
                        $diskPrivate->put($path, $stream);
                        if (is_resource($stream)) fclose($stream);
                    }
                    if ($diskPrivate->exists($path)) {
                        $changes[$column] = $path;
                        $diskPublic->delete($path);
                    }
                }
                if ($changes) {
                    DB::table('kyc_verifications')->where('id', $record->id)->update($changes);
                }
            }
        });
    }

    public function down()
    {
        // Deliberately do not copy identity documents back to public storage on rollback.
    }
}

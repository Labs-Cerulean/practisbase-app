<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Clinic labels, actions, and the reference library live in manual SQL.
 * Staging creates the tables on first use. Live waits for that SQL file.
 */
class ClinicDeskSchema
{
    public static function ready(): bool
    {
        return Schema::hasTable('clinical_labels')
            && Schema::hasTable('patient_actions')
            && Schema::hasTable('clinical_library_documents');
    }

    public static function ensure(): bool
    {
        if (self::ready()) {
            return true;
        }

        if (! Deployment::isStaging()) {
            return false;
        }

        $path = database_path('manual/clinic_desk_postgresql.sql');
        $sql = is_file($path) ? (string) file_get_contents($path) : '';
        if ($sql === '') {
            return false;
        }

        DB::unprepared($sql);

        return true;
    }
}

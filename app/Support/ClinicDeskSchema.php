<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Clinic labels, actions, and the reference library live in manual SQL.
 * Staging creates the tables on first use. Live waits for that SQL file.
 */
class ClinicDeskSchema
{
    public static function ready(): bool
    {
        CatalogColumns::load([
            'clinical_labels',
            'patient_actions',
            'clinical_library_documents',
        ]);

        return CatalogColumns::hasTable('clinical_labels')
            && CatalogColumns::hasTable('patient_actions')
            && CatalogColumns::hasTable('clinical_library_documents');
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
        CatalogColumns::forget([
            'clinical_labels',
            'patient_actions',
            'clinical_library_documents',
        ]);

        return self::ready();
    }
}

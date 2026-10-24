<?php

namespace App\Support;

/**
 * Order for applying database/manual SQL onto an empty staging database.
 * Migrations run first. company_books creates the company tables before the
 * later company files alter them.
 */
class StagingSchemaPlan
{
    /** @return list<string> */
    public static function files(?string $directory = null): array
    {
        $directory ??= dirname(__DIR__, 2).'/database/manual';
        $present = array_map('basename', glob($directory.'/*.sql') ?: []);

        $phase = array_values(array_filter([
            'phase0_postgresql.sql',
            'phase3_postgresql.sql',
            'phase4_postgresql.sql',
            'phase4_certificates_shared_postgresql.sql',
            'phase4_engineer_projects_postgresql.sql',
            'phase5_clinical_branding_postgresql.sql',
            'phase5_fiscal_year_snapshots_postgresql.sql',
            'phase5_issue_codes_postgresql.sql',
            'phase5_postgresql.sql',
            'phase5_stamp_lock_postgresql.sql',
            'phase5_user_postnominals_postgresql.sql',
            'phase5_vault_code_saved_ack_postgresql.sql',
            'phase5_vault_trusted_devices_postgresql.sql',
            'phase6_capital_wfh_expenses_postgresql.sql',
            'phase6_user_regime_segments_postgresql.sql',
        ], fn (string $file) => in_array($file, $present, true)));

        $companyBooks = in_array('company_books_postgresql.sql', $present, true)
            ? ['company_books_postgresql.sql']
            : [];

        $rest = array_values(array_diff($present, $phase, $companyBooks));
        sort($rest);

        return array_values(array_merge($phase, $companyBooks, $rest));
    }

    public static function fiscalYearsTableSql(): string
    {
        return <<<'SQL'
CREATE TABLE IF NOT EXISTS fiscal_years (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    year INTEGER NOT NULL,
    closed_at TIMESTAMP NULL,
    snapshot_json JSONB NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE (user_id, year)
);
SQL;
    }
}

<?php

namespace App\Console\Commands;

use App\Support\Deployment;
use App\Support\StagingSchemaPlan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class StagingSchema extends Command
{
    protected $signature = 'staging:schema';

    protected $description = 'Apply migrations and the manual SQL onto the empty staging database';

    public function handle(): int
    {
        if (! Deployment::isStaging()) {
            $this->error('Refused. This command runs only when APP_ENV is staging.');

            return self::FAILURE;
        }

        try {
            Deployment::guardLiveAddress();
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->call('migrate', ['--force' => true]);

        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS staging_schema_log (
    filename VARCHAR(255) PRIMARY KEY,
    applied_at TIMESTAMP NOT NULL DEFAULT NOW()
)
SQL);

        $applied = 0;
        foreach (StagingSchemaPlan::files(database_path('manual')) as $file) {
            if ($file === 'phase5_fiscal_year_snapshots_postgresql.sql') {
                $applied += $this->apply('staging:fiscal_years', StagingSchemaPlan::fiscalYearsTableSql());
            }

            $path = database_path('manual/'.$file);
            $applied += $this->apply($file, (string) file_get_contents($path));
        }

        $this->info($applied === 0 ? 'Schema already applied.' : 'Applied '.$applied.' staging schema steps.');

        return self::SUCCESS;
    }

    private function apply(string $name, string $sql): int
    {
        $done = DB::table('staging_schema_log')->where('filename', $name)->exists();
        if ($done) {
            return 0;
        }

        DB::unprepared($sql);
        DB::table('staging_schema_log')->insert([
            'filename' => $name,
            'applied_at' => now(),
        ]);
        $this->line($name);

        return 1;
    }
}

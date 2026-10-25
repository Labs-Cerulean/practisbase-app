<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Email-verification and Stripe columns live in manual SQL.
 * Staging creates them on first use. Live waits for that SQL file.
 */
class RegisterBillingSchema
{
    private static ?bool $ready = null;

    public static function ready(): bool
    {
        if (self::$ready !== null) {
            return self::$ready;
        }

        try {
            self::$ready = self::userReady() && self::companyReady();
        } catch (\Throwable) {
            self::$ready = false;
        }

        return self::$ready;
    }

    public static function ensure(): bool
    {
        if (self::ready()) {
            return true;
        }

        if (! Deployment::isStaging()) {
            return false;
        }

        try {
            if (! self::userReady()) {
                self::runFile('register_billing_postgresql.sql');
                self::runFile('stripe_billing_profile_postgresql.sql');
            }
            if (Schema::hasTable('company_invoices') && ! self::companyReady()) {
                self::runFile('stripe_company_billing_postgresql.sql');
            }
        } catch (\Throwable $e) {
            report($e);

            return false;
        }

        self::$ready = true;

        return true;
    }

    private static function userReady(): bool
    {
        return Schema::hasColumns('users', [
            'must_verify_email',
            'stripe_customer_id',
            'stripe_subscription_id',
            'subscription_status',
            'include_billing_vat',
            'billing_vat_number',
        ]);
    }

    private static function companyReady(): bool
    {
        if (! Schema::hasTable('company_invoices')) {
            return true;
        }

        return Schema::hasColumn('company_invoices', 'stripe_invoice_id')
            && Schema::hasColumn('company_payments', 'stripe_invoice_id');
    }

    private static function runFile(string $name): void
    {
        $path = database_path('manual/'.$name);
        $sql = is_file($path) ? (string) file_get_contents($path) : '';
        if ($sql === '') {
            throw new \RuntimeException('Missing '.$name);
        }

        DB::unprepared($sql);
    }
}

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
            self::$ready = Schema::hasColumns('users', [
                'must_verify_email',
                'stripe_customer_id',
                'stripe_subscription_id',
                'subscription_status',
            ]);
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

        $path = database_path('manual/register_billing_postgresql.sql');
        $sql = is_file($path) ? (string) file_get_contents($path) : '';
        if ($sql === '') {
            return false;
        }

        try {
            DB::unprepared($sql);
        } catch (\Throwable $e) {
            report($e);

            return false;
        }

        self::$ready = true;

        return true;
    }
}

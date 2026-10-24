<?php

namespace App\Support;

use RuntimeException;

/**
 * Tells the live site apart from a staging replica.
 * Staging is APP_ENV=staging, or a Railway environment named staging.
 */
class Deployment
{
    public const LIVE_HOSTS = ['practisbase.com', 'www.practisbase.com'];

    public static function isStaging(?string $appEnv = null, ?string $railwayEnvironment = null): bool
    {
        if (func_num_args() === 0) {
            $appEnv = (string) config('app.env');
            $railwayEnvironment = (string) config('deployment.railway_environment');
        }

        return strtolower((string) $appEnv) === 'staging'
            || strtolower((string) $railwayEnvironment) === 'staging';
    }

    public static function isLiveHost(?string $url): bool
    {
        $host = strtolower((string) parse_url((string) $url, PHP_URL_HOST));

        return in_array($host, self::LIVE_HOSTS, true);
    }

    public static function guardLiveAddress(): void
    {
        if (! self::isStaging()) {
            return;
        }

        $url = (string) config('app.url');
        if (self::isLiveHost($url)) {
            throw new RuntimeException('Staging is pointed at the live PractisBase address. Set APP_URL to the staging address.');
        }
    }
}

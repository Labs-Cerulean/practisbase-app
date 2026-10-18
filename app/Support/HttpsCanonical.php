<?php

namespace App\Support;

/**
 * Decides when a public PractisBase request must move to https://www.practisbase.com.
 * Railway terminates TLS and forwards plain http to the container, so the
 * forwarded protocol is what the visitor actually used.
 */
class HttpsCanonical
{
    public const CANONICAL_HOST = 'www.practisbase.com';

    public const APEX_HOST = 'practisbase.com';

    /**
     * Absolute https URL to send the visitor to, or null when the request can stay.
     */
    public static function redirectTarget(
        ?string $forwardedProto,
        bool $secure,
        string $host,
        string $uri,
        bool $local,
    ): ?string {
        $host = strtolower(trim($host));
        $proto = strtolower(trim(explode(',', (string) $forwardedProto)[0]));
        $uri = self::pathAndQuery($uri);

        if ($local || self::isLoopback($host)) {
            return null;
        }

        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        if ($path === '/up' && $proto !== 'http') {
            return null;
        }

        $onHttps = $secure || $proto === 'https';

        if ($host === self::APEX_HOST) {
            return 'https://'.self::CANONICAL_HOST.$uri;
        }

        if ($host === self::CANONICAL_HOST && ! $onHttps) {
            return 'https://'.self::CANONICAL_HOST.$uri;
        }

        if ($proto === 'http' && $host !== '') {
            return 'https://'.$host.$uri;
        }

        return null;
    }

    private static function pathAndQuery(string $uri): string
    {
        if ($uri === '') {
            return '/';
        }

        return str_starts_with($uri, '/') ? $uri : '/'.$uri;
    }

    private static function isLoopback(string $host): bool
    {
        return $host === 'localhost' || $host === '127.0.0.1' || $host === '[::1]' || $host === '::1';
    }
}

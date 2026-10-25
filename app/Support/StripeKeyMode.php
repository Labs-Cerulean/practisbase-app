<?php

namespace App\Support;

/**
 * Staging talks only to the Stripe sandbox. The live site talks only to live Stripe.
 */
class StripeKeyMode
{
    public static function mode(?string $secret): string
    {
        $secret = trim((string) $secret);
        if ($secret === '') {
            return 'missing';
        }
        if (str_starts_with($secret, 'sk_test_') || str_starts_with($secret, 'rk_test_')) {
            return 'test';
        }
        if (str_starts_with($secret, 'sk_live_') || str_starts_with($secret, 'rk_live_')) {
            return 'live';
        }

        return 'unknown';
    }

    public static function sandboxOnly(string $appEnv, bool $staging): bool
    {
        return $staging || strtolower($appEnv) !== 'production';
    }

    public static function allowed(string $appEnv, bool $staging, ?string $secret): bool
    {
        $mode = self::mode($secret);

        return self::sandboxOnly($appEnv, $staging) ? $mode === 'test' : $mode === 'live';
    }

    public static function acceptsLivemode(string $appEnv, bool $staging, bool $livemode): bool
    {
        return self::sandboxOnly($appEnv, $staging) ? ! $livemode : $livemode;
    }

    public static function refusal(string $appEnv, bool $staging, ?string $secret): string
    {
        $mode = self::mode($secret);
        if (self::sandboxOnly($appEnv, $staging)) {
            if ($mode === 'live') {
                return $staging
                    ? 'Staging uses the Stripe sandbox. Paste the secret key that starts with sk_test_.'
                    : 'This server uses the Stripe sandbox. Paste the secret key that starts with sk_test_.';
            }
            if ($mode === 'missing') {
                return $staging
                    ? 'This staging site is waiting for the Stripe sandbox secret key.'
                    : 'Card billing is not configured on this server.';
            }

            return 'The Stripe secret key needs to be the full sandbox key from the Stripe Dashboard.';
        }

        if ($mode === 'test') {
            return 'The live site uses the live Stripe secret key, the one that starts with sk_live_.';
        }
        if ($mode === 'missing') {
            return 'Card billing is not configured on this server.';
        }

        return 'The Stripe secret key needs to be the full live key from the Stripe Dashboard.';
    }

    public static function ignoreMessage(string $appEnv, bool $staging): string
    {
        if (self::sandboxOnly($appEnv, $staging)) {
            return 'Ignored. Staging is linked to the Stripe sandbox.';
        }

        return 'Ignored. This server is linked to live Stripe.';
    }
}

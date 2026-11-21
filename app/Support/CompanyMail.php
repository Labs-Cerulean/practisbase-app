<?php

namespace App\Support;

/**
 * Decides whether company billing mail can leave the server.
 * Live environment values win over a cached config that still says "log".
 */
class CompanyMail
{
    /**
     * @param  array<string, ?string>  $env
     * @param  array{default?: string, from?: string, host?: string}  $cached
     * @return array{mailer: string, transport: string, from: string, delivers: bool, hint: ?string, overrides: array<string, mixed>}
     */
    public static function resolve(array $env, array $cached = []): array
    {
        $explicitMailer = self::clean($env['MAIL_MAILER'] ?? null);
        $mailer = $explicitMailer !== '' ? $explicitMailer : self::clean($cached['default'] ?? null);
        if ($mailer === '') {
            $mailer = 'log';
        }

        $from = self::clean($env['MAIL_FROM_ADDRESS'] ?? null);
        if ($from === '') {
            $from = self::clean($cached['from'] ?? null);
        }

        $host = self::clean($env['MAIL_HOST'] ?? null);
        $resendKey = self::clean($env['RESEND_API_KEY'] ?? null);
        if ($resendKey === '') {
            $resendKey = self::clean($env['RESEND_KEY'] ?? null);
        }

        if ($explicitMailer === '' && in_array($mailer, ['log', 'array'], true)) {
            if ($resendKey !== '') {
                $mailer = 'resend';
            } elseif (self::isRelayHost($host) && self::isRealFrom($from)) {
                $mailer = 'smtp';
            }
        }

        $transport = $mailer;

        $overrides = self::overrides($env, $mailer, $from, $host, $resendKey);

        $delivers = true;
        $hint = null;
        if (in_array($mailer, ['log', 'array'], true)) {
            $delivers = false;
            $hint = 'MAIL_MAILER is "'.$mailer.'" ('.$transport.') — Laravel only writes to the app log. On Railway set MAIL_MAILER=smtp or resend, plus host/credentials and MAIL_FROM_ADDRESS.';
        } elseif ($mailer === 'resend' && $resendKey === '') {
            $delivers = false;
            $hint = 'MAIL_MAILER is resend, but RESEND_API_KEY is missing. Set that key on Railway (RESEND_KEY is also accepted).';
        } elseif ($mailer === 'smtp' && ! self::isRelayHost($host) && ! self::isRelayHost(self::clean($cached['host'] ?? null))) {
            $delivers = false;
            $hint = 'MAIL_MAILER is smtp, but MAIL_HOST is still the local default. Set MAIL_HOST, MAIL_PORT, MAIL_USERNAME, and MAIL_PASSWORD on Railway.';
        } elseif (! self::isRealFrom($from)) {
            $delivers = false;
            $hint = 'MAIL_FROM_ADDRESS is missing or still the Laravel default. Set a real from address on Railway (e.g. accounts@labscerulean.com).';
        }

        return [
            'mailer' => $mailer,
            'transport' => $transport,
            'from' => $from,
            'delivers' => $delivers,
            'hint' => $hint,
            'overrides' => $overrides,
        ];
    }

    /**
     * @param  array<string, ?string>  $env
     * @return array<string, mixed>
     */
    private static function overrides(array $env, string $mailer, string $from, string $host, string $resendKey): array
    {
        $overrides = [];

        if ($mailer === 'smtp') {
            $overrides['mail.default'] = 'smtp';
            $overrides['mail.mailers.smtp.transport'] = 'smtp';
            if (self::isRelayHost($host)) {
                $overrides['mail.mailers.smtp.host'] = $host;
            }
            $port = self::clean($env['MAIL_PORT'] ?? null);
            if ($port !== '') {
                $overrides['mail.mailers.smtp.port'] = (int) $port;
            }
            $scheme = self::scheme($env, $port);
            if ($scheme !== '') {
                $overrides['mail.mailers.smtp.scheme'] = $scheme;
            }
            $username = self::clean($env['MAIL_USERNAME'] ?? null);
            if ($username !== '') {
                $overrides['mail.mailers.smtp.username'] = $username;
            }
            $password = self::clean($env['MAIL_PASSWORD'] ?? null);
            if ($password !== '') {
                $overrides['mail.mailers.smtp.password'] = $password;
            }
        }

        if ($mailer === 'resend') {
            $overrides['mail.default'] = 'resend';
            $overrides['mail.mailers.resend.transport'] = 'resend';
            if ($resendKey !== '') {
                $overrides['services.resend.key'] = $resendKey;
            }
        }

        if (self::isRealFrom($from)) {
            $overrides['mail.from.address'] = $from;
            $name = self::clean($env['MAIL_FROM_NAME'] ?? null);
            if ($name !== '') {
                $overrides['mail.from.name'] = $name;
            }
        }

        return $overrides;
    }

    /**
     * @param  array<string, ?string>  $env
     */
    private static function scheme(array $env, string $port): string
    {
        $scheme = strtolower(self::clean($env['MAIL_SCHEME'] ?? null));
        if ($scheme !== '') {
            return $scheme;
        }

        $encryption = strtolower(self::clean($env['MAIL_ENCRYPTION'] ?? null));
        if (in_array($encryption, ['ssl', 'smtps'], true) || (int) $port === 465) {
            return 'smtps';
        }
        if (in_array($encryption, ['tls', 'starttls'], true)) {
            return 'smtp';
        }

        return '';
    }

    private static function isRelayHost(string $host): bool
    {
        return $host !== '' && ! in_array(strtolower($host), ['127.0.0.1', 'localhost', 'mailpit'], true);
    }

    private static function isRealFrom(string $from): bool
    {
        return $from !== '' && strcasecmp($from, 'hello@example.com') !== 0;
    }

    private static function clean(?string $value): string
    {
        $value = trim((string) $value);
        if (strlen($value) >= 2) {
            $quote = $value[0];
            if (($quote === '"' || $quote === "'") && str_ends_with($value, $quote)) {
                $value = trim(substr($value, 1, -1));
            }
        }

        if ($value === '' || strcasecmp($value, 'null') === 0) {
            return '';
        }

        return $value;
    }
}

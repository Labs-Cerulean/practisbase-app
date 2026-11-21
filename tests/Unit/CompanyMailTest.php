<?php

namespace Tests\Unit;

use App\Support\CompanyMail;
use PHPUnit\Framework\TestCase;

class CompanyMailTest extends TestCase
{
    public function test_log_mailer_does_not_pretend_a_reminder_was_sent(): void
    {
        $status = CompanyMail::resolve([], [
            'default' => 'log',
            'from' => 'hello@example.com',
            'host' => '127.0.0.1',
        ]);

        $this->assertFalse($status['delivers']);
        $this->assertSame('log', $status['mailer']);
        $this->assertStringContainsString('MAIL_MAILER is "log"', (string) $status['hint']);
    }

    public function test_live_smtp_settings_win_over_a_cached_log_mailer(): void
    {
        $status = CompanyMail::resolve([
            'MAIL_MAILER' => 'smtp',
            'MAIL_HOST' => 'smtp-relay.brevo.com',
            'MAIL_PORT' => '587',
            'MAIL_ENCRYPTION' => 'tls',
            'MAIL_USERNAME' => 'relay-user',
            'MAIL_PASSWORD' => 'secret',
            'MAIL_FROM_ADDRESS' => 'accounts@labscerulean.com',
            'MAIL_FROM_NAME' => 'Cerulean Labs',
        ], [
            'default' => 'log',
            'from' => 'hello@example.com',
            'host' => '127.0.0.1',
        ]);

        $this->assertTrue($status['delivers']);
        $this->assertSame('smtp', $status['mailer']);
        $this->assertSame('smtp', $status['overrides']['mail.default']);
        $this->assertSame('smtp-relay.brevo.com', $status['overrides']['mail.mailers.smtp.host']);
        $this->assertSame('smtp', $status['overrides']['mail.mailers.smtp.scheme']);
        $this->assertSame('accounts@labscerulean.com', $status['overrides']['mail.from.address']);
    }

    public function test_relay_host_is_used_when_mailer_was_left_on_the_default(): void
    {
        $status = CompanyMail::resolve([
            'MAIL_HOST' => 'smtp-relay.brevo.com',
            'MAIL_FROM_ADDRESS' => 'accounts@labscerulean.com',
        ], [
            'default' => 'log',
            'from' => 'hello@example.com',
        ]);

        $this->assertTrue($status['delivers']);
        $this->assertSame('smtp', $status['mailer']);
    }

    public function test_explicit_log_mailer_is_not_upgraded(): void
    {
        $status = CompanyMail::resolve([
            'MAIL_MAILER' => 'log',
            'MAIL_HOST' => 'smtp-relay.brevo.com',
            'MAIL_FROM_ADDRESS' => 'accounts@labscerulean.com',
        ], []);

        $this->assertFalse($status['delivers']);
        $this->assertSame('log', $status['mailer']);
    }

    public function test_resend_key_is_accepted_under_either_name(): void
    {
        $status = CompanyMail::resolve([
            'RESEND_KEY' => 're_test',
            'MAIL_FROM_ADDRESS' => 'accounts@labscerulean.com',
        ], [
            'default' => 'log',
        ]);

        $this->assertTrue($status['delivers']);
        $this->assertSame('resend', $status['mailer']);
        $this->assertSame('re_test', $status['overrides']['services.resend.key']);
    }
}

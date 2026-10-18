<?php

namespace Tests\Unit;

use App\Support\HttpsCanonical;
use PHPUnit\Framework\TestCase;

class HttpsCanonicalTest extends TestCase
{
    public function test_plain_http_on_www_moves_to_https_and_keeps_the_query(): void
    {
        $this->assertSame(
            'https://www.practisbase.com/dashboard?tab=app',
            HttpsCanonical::redirectTarget('http', false, 'www.practisbase.com', '/dashboard?tab=app', false)
        );
    }

    public function test_the_apex_moves_to_https_www_even_when_the_apex_request_was_already_secure(): void
    {
        $this->assertSame(
            'https://www.practisbase.com/login',
            HttpsCanonical::redirectTarget('https', true, 'practisbase.com', '/login', false)
        );
    }

    public function test_https_www_behind_the_railway_proxy_stays_put(): void
    {
        $this->assertNull(
            HttpsCanonical::redirectTarget('https', true, 'www.practisbase.com', '/dashboard', false)
        );
    }

    public function test_a_forwarded_https_protocol_is_not_redirected_again(): void
    {
        $this->assertNull(
            HttpsCanonical::redirectTarget('HTTPS', false, 'WWW.PRACTISBASE.COM', '/', false)
        );
    }

    public function test_local_and_health_checks_are_left_on_the_container_port(): void
    {
        $this->assertNull(
            HttpsCanonical::redirectTarget('http', false, 'www.practisbase.com', '/dashboard', true)
        );
        $this->assertNull(
            HttpsCanonical::redirectTarget(null, false, '127.0.0.1', '/up', false)
        );
        $this->assertNull(
            HttpsCanonical::redirectTarget(null, false, 'www.practisbase.com', '/up', false)
        );
    }

    public function test_an_explicit_http_health_check_on_www_still_upgrades(): void
    {
        $this->assertSame(
            'https://www.practisbase.com/up',
            HttpsCanonical::redirectTarget('http', false, 'www.practisbase.com', '/up', false)
        );
    }
}

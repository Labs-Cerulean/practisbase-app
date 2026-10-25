<?php

namespace Tests\Unit;

use App\Support\StripeKeyMode;
use PHPUnit\Framework\TestCase;

class StripeKeyModeTest extends TestCase
{
    public function test_staging_and_local_accept_only_the_sandbox_secret(): void
    {
        $this->assertTrue(StripeKeyMode::allowed('staging', true, 'sk_test_sandbox'));
        $this->assertTrue(StripeKeyMode::allowed('local', false, 'rk_test_sandbox'));
        $this->assertFalse(StripeKeyMode::allowed('staging', true, 'sk_live_real'));
        $this->assertFalse(StripeKeyMode::allowed('local', false, 'sk_live_real'));
        $this->assertFalse(StripeKeyMode::allowed('staging', true, ''));
        $this->assertFalse(StripeKeyMode::allowed('staging', true, 'pk_test_publishable'));

        $this->assertTrue(StripeKeyMode::acceptsLivemode('staging', true, false));
        $this->assertFalse(StripeKeyMode::acceptsLivemode('staging', true, true));
        $this->assertSame(
            'Staging uses the Stripe sandbox. Paste the secret key that starts with sk_test_.',
            StripeKeyMode::refusal('staging', true, 'sk_live_real')
        );
    }

    public function test_the_live_site_accepts_only_the_live_secret(): void
    {
        $this->assertTrue(StripeKeyMode::allowed('production', false, 'sk_live_real'));
        $this->assertTrue(StripeKeyMode::allowed('production', false, 'rk_live_real'));
        $this->assertFalse(StripeKeyMode::allowed('production', false, 'sk_test_sandbox'));
        $this->assertTrue(StripeKeyMode::allowed('production', true, 'sk_test_sandbox'));
        $this->assertFalse(StripeKeyMode::allowed('production', true, 'sk_live_real'));

        $this->assertTrue(StripeKeyMode::acceptsLivemode('production', false, true));
        $this->assertFalse(StripeKeyMode::acceptsLivemode('production', false, false));
        $this->assertSame(
            'The live site uses the live Stripe secret key, the one that starts with sk_live_.',
            StripeKeyMode::refusal('production', false, 'sk_test_sandbox')
        );
    }
}

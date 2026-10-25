<?php

namespace Tests\Unit;

use App\Support\EmailVerification;
use App\Support\StripeCheckoutPlan;
use App\Support\TierPolicy;
use PHPUnit\Framework\TestCase;

class RegistrationBillingTest extends TestCase
{
    public function test_standard_gross_cents_include_maltese_vat(): void
    {
        $this->assertSame(1887, StripeCheckoutPlan::grossCents(TierPolicy::PRICE_STANDARD));
        $this->assertSame(2949, StripeCheckoutPlan::grossCents(TierPolicy::PRICE_PRACTICE));
        $this->assertSame(4129, StripeCheckoutPlan::grossCents(TierPolicy::PRICE_PRO));
    }

    public function test_trial_is_fourteen_days_unless_a_longer_promo_or_a_previous_subscription(): void
    {
        $now = 1_700_000_000;

        $this->assertSame(14, StripeCheckoutPlan::trialPeriodDays(false, null, $now));
        $this->assertSame(0, StripeCheckoutPlan::trialPeriodDays(true, null, $now));
        $this->assertSame(40, StripeCheckoutPlan::trialPeriodDays(false, $now + (40 * 86400), $now));
        $this->assertSame(10, StripeCheckoutPlan::trialPeriodDays(true, $now + (10 * 86400), $now));
        $this->assertSame(730, StripeCheckoutPlan::trialPeriodDays(false, $now + (800 * 86400), $now));
    }

    public function test_checkout_collects_a_card_and_starts_a_trial(): void
    {
        $payload = StripeCheckoutPlan::sessionPayload([
            'userId' => 42,
            'email' => 'ada@example.com',
            'tier' => 'standard',
            'label' => 'Standard',
            'trialDays' => 14,
            'successUrl' => 'https://example.test/ok?session_id={CHECKOUT_SESSION_ID}',
            'cancelUrl' => 'https://example.test/plans',
            'returnTo' => 'onboarding',
        ]);

        $this->assertSame('subscription', $payload['mode']);
        $this->assertSame('always', $payload['payment_method_collection']);
        $this->assertSame(14, $payload['subscription_data']['trial_period_days']);
        $this->assertSame(1887, $payload['line_items'][0]['price_data']['unit_amount']);
        $this->assertSame('eur', $payload['line_items'][0]['price_data']['currency']);
        $this->assertSame('month', $payload['line_items'][0]['price_data']['recurring']['interval']);
        $this->assertSame('ada@example.com', $payload['customer_email']);
        $this->assertArrayNotHasKey('customer', $payload);
    }

    public function test_repeat_checkout_omits_the_trial_and_reuses_the_customer(): void
    {
        $payload = StripeCheckoutPlan::sessionPayload([
            'userId' => 7,
            'email' => 'ada@example.com',
            'tier' => 'pro-med',
            'label' => 'Pro Medical',
            'trialDays' => 0,
            'successUrl' => 'https://example.test/ok',
            'cancelUrl' => 'https://example.test/settings',
            'priceId' => 'price_pro',
            'customerId' => 'cus_123',
            'returnTo' => 'settings',
        ]);

        $this->assertSame('price_pro', $payload['line_items'][0]['price']);
        $this->assertArrayNotHasKey('price_data', $payload['line_items'][0]);
        $this->assertArrayNotHasKey('trial_period_days', $payload['subscription_data']);
        $this->assertSame('cus_123', $payload['customer']);
        $this->assertArrayNotHasKey('customer_email', $payload);
        $this->assertSame('settings', $payload['metadata']['billing_return']);
    }

    public function test_access_codes_and_existing_accounts_are_not_forced_through_billing_states(): void
    {
        $this->assertSame('pro-med', StripeCheckoutPlan::tierAfterBillingEvent('canceled', 'free', true, 'pro-med'));
        $this->assertSame('standard', StripeCheckoutPlan::tierAfterBillingEvent('trialing', 'standard', false, 'pending'));
        $this->assertSame('standard', StripeCheckoutPlan::tierAfterBillingEvent('past_due', 'standard', false, 'standard'));
        $this->assertSame('free', StripeCheckoutPlan::tierAfterBillingEvent('canceled', 'standard', false, 'standard'));
        $this->assertSame('free', StripeCheckoutPlan::tierAfterBillingEvent('unpaid', 'standard', false, 'standard'));
        $this->assertSame('free', StripeCheckoutPlan::tierAfterBillingEvent('incomplete_expired', 'standard', false, 'standard'));
        $this->assertSame('practice-med', StripeCheckoutPlan::tierAfterBillingEvent('active', 'nope', false, 'practice-med'));

        $this->assertFalse(EmailVerification::required(false, true, false));
        $this->assertFalse(EmailVerification::required(true, false, false));
        $this->assertFalse(EmailVerification::required(true, true, true));
        $this->assertTrue(EmailVerification::required(true, true, false));
    }
}

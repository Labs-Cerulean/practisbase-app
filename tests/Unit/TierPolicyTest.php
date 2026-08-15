<?php

namespace Tests\Unit;

use App\Models\User;
use App\Support\TierPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TierPolicyTest extends TestCase
{
    public function test_prices_and_bundle_math(): void
    {
        $this->assertSame('15.99', TierPolicy::PRICE_STANDARD);
        $this->assertSame('24.99', TierPolicy::PRICE_PRACTICE);
        $this->assertSame('34.99', TierPolicy::PRICE_PRO);
        $this->assertSame('5.99', TierPolicy::bundleSavingsEuro());
        $this->assertSame('+ 18% VAT', TierPolicy::priceVatSuffix());
        $this->assertStringContainsString('exclude 18%', TierPolicy::pricingVatDisclaimer());
        $this->assertSame('18.87', TierPolicy::priceIncludingVat('15.99'));
        $this->assertSame('€15.99 + VAT', TierPolicy::priceLabel('standard'));
        $this->assertSame('€0', TierPolicy::priceLabel('free'));
    }

    public function test_practice_keeps_free_financial_without_tax_vat(): void
    {
        $user = new User([
            'tier' => 'practice-med',
            'profession' => 'Medical Professional',
            'clients_created_count' => 0,
        ]);

        $this->assertTrue(TierPolicy::hasPracticeTools($user));
        $this->assertTrue(TierPolicy::canAccessProPackage($user, 'med'));
        $this->assertFalse(TierPolicy::hasStandardFinancial($user));
        $this->assertFalse(TierPolicy::canAccessReports($user));
        $this->assertFalse(TierPolicy::canAccessStandardTools($user));
        $this->assertFalse(TierPolicy::hasUnlimitedClients($user));
        $this->assertTrue(TierPolicy::canAddClient($user));
        $this->assertFalse(TierPolicy::meetsMinimumTier($user, ['standard']));
        $this->assertTrue(TierPolicy::isPaid($user));
        $this->assertTrue(TierPolicy::isPracticeOnly($user));
        $this->assertFalse(TierPolicy::isPro($user));
    }

    public function test_full_pro_includes_standard_and_practice(): void
    {
        $user = new User([
            'tier' => 'pro-arch',
            'profession' => 'Architect / Perit',
        ]);

        $this->assertTrue(TierPolicy::hasPracticeTools($user));
        $this->assertTrue(TierPolicy::hasStandardFinancial($user));
        $this->assertTrue(TierPolicy::canAccessReports($user));
        $this->assertTrue(TierPolicy::canAccessProPackage($user, 'arch'));
        $this->assertFalse(TierPolicy::canAccessProPackage($user, 'med'));
        $this->assertTrue(TierPolicy::meetsMinimumTier($user, ['standard']));
        $this->assertTrue(TierPolicy::hasUnlimitedClients($user));
        $this->assertTrue(TierPolicy::isPro($user));
    }

    public function test_standard_has_accounts_without_practice(): void
    {
        $user = new User([
            'tier' => 'standard',
            'profession' => 'Medical Professional',
        ]);

        $this->assertTrue(TierPolicy::hasStandardFinancial($user));
        $this->assertFalse(TierPolicy::hasPracticeTools($user));
        $this->assertFalse(TierPolicy::canAccessProPackage($user, 'med'));
        $this->assertTrue(TierPolicy::meetsMinimumTier($user, ['standard']));
    }

    public function test_client_cap_blocks_sixth_on_practice(): void
    {
        $user = new User([
            'tier' => 'practice-eng',
            'profession' => 'Engineer',
            'clients_created_count' => TierPolicy::FREE_CLIENT_LIFETIME_CAP,
        ]);

        $this->assertFalse(TierPolicy::canAddClient($user));
    }

    public function test_profession_mismatch_blocks_package(): void
    {
        $user = new User([
            'tier' => 'practice-med',
            'profession' => 'Engineer',
        ]);

        $this->assertFalse(TierPolicy::canAccessProPackage($user, 'med'));
    }

    #[DataProvider('transitionProvider')]
    public function test_transition_flags(string $from, string $to, bool $expectDowngrade, bool $gainFinancial, bool $gainPractice, bool $loseFinancial, bool $losePractice): void
    {
        $this->assertSame($expectDowngrade, TierPolicy::isDowngrade($from, $to), "{$from} → {$to} downgrade flag");

        $this->assertSame($gainFinancial, ! TierPolicy::tierHasStandardFinancial($from) && TierPolicy::tierHasStandardFinancial($to));
        $this->assertSame($gainPractice, ! TierPolicy::tierHasPracticeTools($from) && TierPolicy::tierHasPracticeTools($to));
        $this->assertSame($loseFinancial, TierPolicy::tierHasStandardFinancial($from) && ! TierPolicy::tierHasStandardFinancial($to));
        $this->assertSame($losePractice, TierPolicy::tierHasPracticeTools($from) && ! TierPolicy::tierHasPracticeTools($to));

        $notes = TierPolicy::changeConsequences($from, $to);
        $this->assertNotEmpty($notes, "{$from} → {$to} should explain the change");
    }

    public static function transitionProvider(): array
    {
        return [
            // from, to, downgrade?, gainFin, gainPrac, loseFin, losePrac
            'free to standard' => ['free', 'standard', false, true, false, false, false],
            'free to practice' => ['free', 'practice-med', false, false, true, false, false],
            'free to pro' => ['free', 'pro-med', false, true, true, false, false],
            'practice to pro' => ['practice-med', 'pro-med', false, true, false, false, false],
            'standard to pro' => ['standard', 'pro-med', false, false, true, false, false],
            'pro to practice' => ['pro-med', 'practice-med', true, false, false, true, false],
            'pro to standard' => ['pro-med', 'standard', true, false, false, false, true],
            'pro to free' => ['pro-med', 'free', true, false, false, true, true],
            'practice to free' => ['practice-med', 'free', true, false, false, false, true],
            'standard to free' => ['standard', 'free', true, false, false, true, false],
            'practice to standard' => ['practice-med', 'standard', true, true, false, false, true],
            'standard to practice' => ['standard', 'practice-med', true, false, true, true, false],
        ];
    }

    public function test_allowed_tiers_include_practice_and_pro_for_profession(): void
    {
        $med = TierPolicy::allowedTiersForProfession('Medical Professional');
        $this->assertContains('practice-med', $med);
        $this->assertContains('pro-med', $med);
        $this->assertNotContains('practice-arch', $med);

        $other = TierPolicy::allowedTiersForProfession('Tutor');
        $this->assertSame(['free', 'standard'], $other);
    }

    public function test_normalize_unknown_falls_back_to_free(): void
    {
        $this->assertSame('free', TierPolicy::normalize('enterprise'));
        $this->assertSame('practice-med', TierPolicy::normalize('practice-med'));
    }
}

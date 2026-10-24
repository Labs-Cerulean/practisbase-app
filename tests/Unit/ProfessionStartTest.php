<?php

namespace Tests\Unit;

use App\Support\ProfessionStart;
use PHPUnit\Framework\TestCase;

class ProfessionStartTest extends TestCase
{
    public function test_doctors_start_exempt_and_other_warrants_start_on_article_11(): void
    {
        $this->assertSame('exempt', ProfessionStart::vatStatusFor('Medical Professional'));
        $this->assertSame('article_11', ProfessionStart::vatStatusFor('Architect / Perit'));
        $this->assertSame('article_11', ProfessionStart::vatStatusFor('Engineer'));
        $this->assertSame('article_11', ProfessionStart::vatStatusFor('Tutor / Lecturer'));
        $this->assertSame('article_11', ProfessionStart::vatStatusFor('Lawyer'));
    }

    public function test_onboarding_is_profession_then_a_chosen_plan(): void
    {
        $this->assertFalse(ProfessionStart::onboardingComplete(null, ProfessionStart::PLAN_PENDING));
        $this->assertFalse(ProfessionStart::onboardingComplete('Engineer', ProfessionStart::PLAN_PENDING));
        $this->assertFalse(ProfessionStart::onboardingComplete('Engineer', ''));
        $this->assertTrue(ProfessionStart::onboardingComplete('Engineer', 'free'));
        $this->assertTrue(ProfessionStart::onboardingComplete('Medical Professional', 'pro-med'));

        $this->assertSame('/onboarding/profession', ProfessionStart::redirectPath(null, ProfessionStart::PLAN_PENDING));
        $this->assertSame('/onboarding/plans', ProfessionStart::redirectPath('Architect / Perit', ProfessionStart::PLAN_PENDING));
        $this->assertSame('/dashboard', ProfessionStart::redirectPath('Architect / Perit', 'practice-arch'));
    }
}

<?php

namespace Tests\Unit;

use App\Support\CompanyBooks;
use PHPUnit\Framework\TestCase;

class CompanyBooksTest extends TestCase
{
    public function test_default_registry_constants(): void
    {
        $this->assertSame('Cerulean Labs Limited', CompanyBooks::DEFAULT_LEGAL_NAME);
        $this->assertSame('C 116764', CompanyBooks::DEFAULT_REGISTRATION_NUMBER);
        $this->assertSame('2026-07-31', CompanyBooks::INCORPORATION_DATE);
        $this->assertSame('2026-12-31', CompanyBooks::FIRST_PERIOD_END);
        $this->assertSame(1200.0, CompanyBooks::SHARE_CAPITAL_EUR);
    }
}

<?php

namespace Tests\Unit;

use App\Support\BankMatch;
use PHPUnit\Framework\TestCase;

class BankMatchTest extends TestCase
{
    public function test_one_bank_movement_matches_several_ledger_lines(): void
    {
        $this->assertTrue(BankMatch::totalsMatch(700, BankMatch::signedTotal([350, 350])));
        $this->assertTrue(BankMatch::totalsMatch(-700, BankMatch::signedTotal([-350, -350])));
        $this->assertFalse(BankMatch::totalsMatch(700, BankMatch::signedTotal([350])));
        $this->assertSame(700.0, BankMatch::signedTotal([350, 350]));
    }
}

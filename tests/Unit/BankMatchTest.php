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

    public function test_two_bank_lines_match_one_ledger_line(): void
    {
        $bank = BankMatch::signedTotal([-42.83, -0.54]);

        $this->assertSame(-43.37, $bank);
        $this->assertTrue(BankMatch::totalsMatch($bank, -43.37));
        $this->assertFalse(BankMatch::totalsMatch(-42.83, -43.37));
    }

    public function test_releasing_one_bank_line_releases_the_whole_group(): void
    {
        $statements = [
            ['id' => 1, 'matched_journal_line_id' => 10],
            ['id' => 2, 'matched_journal_line_id' => 10],
            ['id' => 3, 'matched_journal_line_id' => 99],
        ];
        $journals = [
            ['id' => 10, 'bank_statement_line_id' => 1],
            ['id' => 99, 'bank_statement_line_id' => 3],
        ];

        $fromSibling = BankMatch::matchGroup(2, $statements, $journals);
        sort($fromSibling['statements']);

        $this->assertSame([1, 2], $fromSibling['statements']);
        $this->assertSame([10], $fromSibling['journals']);

        $split = BankMatch::matchGroup(1, [
            ['id' => 1, 'matched_journal_line_id' => 10],
        ], [
            ['id' => 10, 'bank_statement_line_id' => 1],
            ['id' => 11, 'bank_statement_line_id' => 1],
        ]);
        sort($split['journals']);

        $this->assertSame([1], $split['statements']);
        $this->assertSame([10, 11], $split['journals']);
    }
}

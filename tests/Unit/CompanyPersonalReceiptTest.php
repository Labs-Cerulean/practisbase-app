<?php

namespace Tests\Unit;

use App\Support\CompanyChartOfAccounts;
use App\Support\CompanyLedger;
use PHPUnit\Framework\TestCase;

class CompanyPersonalReceiptTest extends TestCase
{
    public function test_receipt_debits_the_bank_and_credits_the_director_loan(): void
    {
        $lines = CompanyLedger::personalFundsReceivedLines(185.00, 'Wife transfer');

        $this->assertSame(CompanyChartOfAccounts::BANK, $lines[0]['account_code']);
        $this->assertSame('debit', $lines[0]['side']);
        $this->assertSame(185.00, $lines[0]['amount']);
        $this->assertSame(CompanyChartOfAccounts::DIRECTOR_LOAN, $lines[1]['account_code']);
        $this->assertSame('credit', $lines[1]['side']);
        $this->assertSame(185.00, $lines[1]['amount']);
    }

    public function test_return_clears_the_receipt_and_leaves_profit_accounts_untouched(): void
    {
        $received = CompanyLedger::personalFundsReceivedLines(185.00, 'In');
        $returned = CompanyLedger::personalFundsReturnedLines(185.00, 'Out');
        $balances = [];

        foreach (array_merge($received, $returned) as $line) {
            $signed = ($line['side'] === 'debit' ? 1 : -1) * (float) $line['amount'];
            $balances[$line['account_code']] = ($balances[$line['account_code']] ?? 0) + $signed;
        }

        $this->assertSame([
            CompanyChartOfAccounts::BANK => 0.0,
            CompanyChartOfAccounts::DIRECTOR_LOAN => 0.0,
        ], $balances);
        $this->assertArrayNotHasKey(CompanyChartOfAccounts::REVENUE_SAAS, $balances);
    }
}

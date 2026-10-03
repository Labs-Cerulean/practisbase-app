<?php

namespace Tests\Unit;

use App\Models\CompanyExpense;
use App\Models\CompanyExpensePayment;
use App\Support\CompanyLedger;
use PHPUnit\Framework\TestCase;

class CompanyExpensePaymentTest extends TestCase
{
    public function test_cash_total_uses_net_only_on_reverse_charge(): void
    {
        $standard = new CompanyExpense([
            'amount' => 10,
            'vat_amount' => 1.80,
            'is_reverse_charge' => false,
        ]);
        $reverseCharge = new CompanyExpense([
            'amount' => 100,
            'vat_amount' => 18,
            'is_reverse_charge' => true,
        ]);

        $this->assertSame(111.80, CompanyExpensePayment::cashTotal([$standard, $reverseCharge]));
    }

    public function test_batch_refund_journal_debits_the_loan_and_credits_the_bank_once(): void
    {
        $lines = CompanyLedger::directorRefundJournalLines(261.71, 'BOV-100');

        $this->assertCount(2, $lines);
        $this->assertSame('debit', $lines[0]['side']);
        $this->assertSame('credit', $lines[1]['side']);
        $this->assertSame(261.71, $lines[0]['amount']);
        $this->assertSame(261.71, $lines[1]['amount']);
        $this->assertSame('BOV-100', $lines[0]['memo']);
    }
}

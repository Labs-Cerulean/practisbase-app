<?php

namespace Tests\Unit;

use App\Models\CompanyExpense;
use App\Models\CompanyExpensePayment;
use App\Support\CompanyChartOfAccounts;
use App\Support\CompanyLedger;
use InvalidArgumentException;
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

    public function test_company_card_expense_credits_the_bank_immediately(): void
    {
        $lines = CompanyLedger::expenseJournalLines(10.00, 1.80, false, 'company', 'software', 'Cursor');
        $bank = $this->line($lines, CompanyChartOfAccounts::BANK, 'credit');

        $this->assertSame(11.80, $bank['amount']);
    }

    public function test_unpaid_supplier_invoice_credits_trade_payables_and_leaves_the_bank_alone(): void
    {
        $lines = CompanyLedger::expenseJournalLines(100.00, 18.00, false, 'payable', 'professional', 'Lawyer');

        $this->assertSame(118.00, $this->line($lines, CompanyChartOfAccounts::TRADE_PAYABLES, 'credit')['amount']);
        $this->assertNull($this->findLine($lines, CompanyChartOfAccounts::BANK, 'credit'));
    }

    public function test_supplier_payment_clears_payables_and_credits_the_bank(): void
    {
        $invoice = CompanyLedger::expenseJournalLines(100.00, 18.00, false, 'payable', 'professional', 'Lawyer');
        $payment = CompanyLedger::supplierPaymentJournalLines(118.00, 'BOV-9');
        $together = array_merge($invoice, $payment);

        $this->assertSame(0.0, $this->net($together, CompanyChartOfAccounts::TRADE_PAYABLES));
        $this->assertSame(-118.00, $this->net($together, CompanyChartOfAccounts::BANK));
        $this->assertSame(100.00, $this->net($together, CompanyChartOfAccounts::expenseAccountCode('professional')));
    }

    public function test_reversing_a_paid_supplier_invoice_nets_every_account_to_zero(): void
    {
        $invoice = CompanyLedger::expenseJournalLines(50.00, 0.00, false, 'payable', 'professional', 'Lawyer');
        $payment = CompanyLedger::supplierPaymentJournalLines(50.00, 'BOV-9');
        $reversal = array_merge(
            CompanyLedger::swapSides($invoice),
            CompanyLedger::swapSides($payment)
        );

        $balances = [];
        foreach (array_merge($invoice, $payment, $reversal) as $line) {
            $signed = ($line['side'] === 'debit' ? 1 : -1) * (float) $line['amount'];
            $balances[$line['account_code']] = ($balances[$line['account_code']] ?? 0) + $signed;
        }

        foreach ($balances as $balance) {
            $this->assertEqualsWithDelta(0.0, $balance, 0.001);
        }
    }

    public function test_director_refund_may_mix_suppliers(): void
    {
        $google = new CompanyExpense([
            'funded_by' => 'director',
            'company_supplier_id' => 1,
            'description' => 'Google',
        ]);
        $railway = new CompanyExpense([
            'funded_by' => 'director',
            'company_supplier_id' => 2,
            'description' => 'Railway',
        ]);

        $this->assertSame(
            CompanyExpensePayment::KIND_DIRECTOR,
            CompanyExpensePayment::kindFor([$google, $railway])
        );
    }

    public function test_supplier_payment_must_be_one_supplier(): void
    {
        $one = new CompanyExpense([
            'funded_by' => 'payable',
            'company_supplier_id' => 1,
            'description' => 'Invoice A',
        ]);
        $two = new CompanyExpense([
            'funded_by' => 'payable',
            'company_supplier_id' => 2,
            'description' => 'Invoice B',
        ]);

        $this->expectException(InvalidArgumentException::class);
        CompanyExpensePayment::kindFor([$one, $two]);
    }

    /**
     * @param  list<array{account_code: string, side: string, amount: float}>  $lines
     * @return array{account_code: string, side: string, amount: float}
     */
    private function line(array $lines, string $code, string $side): array
    {
        $line = $this->findLine($lines, $code, $side);
        $this->assertNotNull($line);

        return $line;
    }

    /**
     * @param  list<array{account_code: string, side: string, amount: float}>  $lines
     * @return ?array{account_code: string, side: string, amount: float}
     */
    private function findLine(array $lines, string $code, string $side): ?array
    {
        foreach ($lines as $line) {
            if ($line['account_code'] === $code && $line['side'] === $side) {
                return $line;
            }
        }

        return null;
    }

    /**
     * @param  list<array{account_code: string, side: string, amount: float}>  $lines
     */
    private function net(array $lines, string $code): float
    {
        $net = 0.0;
        foreach ($lines as $line) {
            if ($line['account_code'] !== $code) {
                continue;
            }
            $net += ($line['side'] === 'debit' ? 1 : -1) * (float) $line['amount'];
        }

        return round($net, 2);
    }
}

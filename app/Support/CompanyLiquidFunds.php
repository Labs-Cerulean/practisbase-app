<?php

namespace App\Support;

/**
 * Cash the company can spend or invest.
 * Share capital is included: it can be used in the business, and it is not a sum to repay.
 * VAT still to pay, unpaid suppliers, and other amounts owed are set aside.
 * A VAT refund and unpaid customer invoices are not cash yet, so they are left out.
 */
class CompanyLiquidFunds
{
    /**
     * @param  array<string, float>  $natural  account code => natural balance (assets debit-positive, liabilities credit-positive)
     * @return array{available: float, lines: list<array{label: string, amount: float}>, share_capital: float}
     */
    public static function fromNaturalBalances(array $natural, float $shareCapital = 0.0): array
    {
        $cash = [
            CompanyChartOfAccounts::BANK => 'BOV current account',
            CompanyChartOfAccounts::STRIPE_CLEARING => 'Stripe clearing',
        ];
        $owed = [
            CompanyChartOfAccounts::TRADE_PAYABLES => 'Unpaid suppliers',
            '2110' => 'VAT settlement',
            CompanyChartOfAccounts::CUSTOMER_ADVANCES => 'Customer advances',
            '2400' => 'Accruals',
            CompanyChartOfAccounts::CORP_TAX_PAYABLE => 'Corporate tax to pay',
            CompanyChartOfAccounts::DIVIDEND_PAYABLE => 'Dividends declared',
        ];

        $lines = [];
        $available = 0.0;

        foreach ($cash as $code => $label) {
            $amount = round((float) ($natural[$code] ?? 0), 2);
            if (abs($amount) < 0.005) {
                continue;
            }
            $lines[] = ['label' => $label, 'amount' => $amount];
            $available += $amount;
        }

        $netVat = round(
            (float) ($natural[CompanyChartOfAccounts::OUTPUT_VAT] ?? 0)
            - (float) ($natural[CompanyChartOfAccounts::INPUT_VAT] ?? 0),
            2
        );
        if ($netVat > 0.009) {
            $lines[] = ['label' => 'VAT to set aside', 'amount' => -$netVat];
            $available -= $netVat;
        }

        foreach ($owed as $code => $label) {
            $amount = round((float) ($natural[$code] ?? 0), 2);
            if ($amount <= 0.009) {
                continue;
            }
            $lines[] = ['label' => $label, 'amount' => -$amount];
            $available -= $amount;
        }

        $director = round((float) ($natural[CompanyChartOfAccounts::DIRECTOR_LOAN] ?? 0), 2);
        if ($director > 0.009) {
            $lines[] = ['label' => 'Owed to director', 'amount' => -$director];
            $available -= $director;
        }

        return [
            'available' => round($available, 2),
            'lines' => $lines,
            'share_capital' => round(max(0, $shareCapital), 2),
        ];
    }
}

<?php

namespace App\Support;

/**
 * Cerulean Labs Ltd chart of accounts (GAPSME micro-entity oriented).
 * Internal operator books only — not a sold product feature.
 */
class CompanyChartOfAccounts
{
    public const BANK = '1000';

    public const STRIPE_CLEARING = '1010';

    public const AR = '1100';

    public const INPUT_VAT = '1200';

    public const CUSTOMER_ADVANCES = '2200';

    public const OUTPUT_VAT = '2100';

    public const DIRECTOR_LOAN = '2300';

    public const CORP_TAX_PAYABLE = '2500';

    public const DIVIDEND_PAYABLE = '2600';

    public const SHARE_CAPITAL = '3000';

    public const RETAINED_EARNINGS = '3100';

    public const REVENUE_SAAS = '4000';

    public const SALES_RETURNS = '4090';

    public const CORP_TAX_EXPENSE = '7000';

    /**
     * @return list<array{
     *   account_code: string,
     *   name: string,
     *   type: string,
     *   balance_sheet_category: ?string,
     *   pl_group: ?string
     * }>
     */
    public static function definitions(): array
    {
        return [
            ['account_code' => '1000', 'name' => 'BOV current account', 'type' => 'asset', 'balance_sheet_category' => 'current_assets', 'pl_group' => null],
            ['account_code' => '1010', 'name' => 'Stripe clearing', 'type' => 'asset', 'balance_sheet_category' => 'current_assets', 'pl_group' => null],
            ['account_code' => '1100', 'name' => 'Trade receivables', 'type' => 'asset', 'balance_sheet_category' => 'current_assets', 'pl_group' => null],
            ['account_code' => '1200', 'name' => 'Input VAT recoverable', 'type' => 'asset', 'balance_sheet_category' => 'current_assets', 'pl_group' => null],
            ['account_code' => '1300', 'name' => 'Prepayments', 'type' => 'asset', 'balance_sheet_category' => 'current_assets', 'pl_group' => null],
            ['account_code' => '1400', 'name' => 'Computer equipment', 'type' => 'asset', 'balance_sheet_category' => 'non_current_assets', 'pl_group' => null],
            ['account_code' => '2000', 'name' => 'Trade payables', 'type' => 'liability', 'balance_sheet_category' => 'current_liabilities', 'pl_group' => null],
            ['account_code' => '2100', 'name' => 'Output VAT', 'type' => 'liability', 'balance_sheet_category' => 'current_liabilities', 'pl_group' => null],
            ['account_code' => '2110', 'name' => 'VAT settlement control', 'type' => 'liability', 'balance_sheet_category' => 'current_liabilities', 'pl_group' => null],
            ['account_code' => '2200', 'name' => 'Customer advances', 'type' => 'liability', 'balance_sheet_category' => 'current_liabilities', 'pl_group' => null],
            ['account_code' => '2300', 'name' => 'Director current / loan account', 'type' => 'liability', 'balance_sheet_category' => 'non_current_liabilities', 'pl_group' => null],
            ['account_code' => '2400', 'name' => 'Accruals', 'type' => 'liability', 'balance_sheet_category' => 'current_liabilities', 'pl_group' => null],
            ['account_code' => '2500', 'name' => 'Corporate tax payable', 'type' => 'liability', 'balance_sheet_category' => 'current_liabilities', 'pl_group' => null],
            ['account_code' => '2600', 'name' => 'Dividends payable', 'type' => 'liability', 'balance_sheet_category' => 'current_liabilities', 'pl_group' => null],
            ['account_code' => '3000', 'name' => 'Issued share capital', 'type' => 'equity', 'balance_sheet_category' => 'capital_reserves', 'pl_group' => null],
            ['account_code' => '3100', 'name' => 'Retained earnings', 'type' => 'equity', 'balance_sheet_category' => 'capital_reserves', 'pl_group' => null],
            ['account_code' => '4000', 'name' => 'SaaS subscription revenue', 'type' => 'revenue', 'balance_sheet_category' => null, 'pl_group' => 'revenue'],
            ['account_code' => '4010', 'name' => 'Setup / implementation revenue', 'type' => 'revenue', 'balance_sheet_category' => null, 'pl_group' => 'revenue'],
            ['account_code' => '4090', 'name' => 'Sales returns and credit notes', 'type' => 'revenue', 'balance_sheet_category' => null, 'pl_group' => 'revenue'],
            ['account_code' => '5000', 'name' => 'Hosting and service delivery', 'type' => 'expense', 'balance_sheet_category' => null, 'pl_group' => 'cost_of_sales'],
            ['account_code' => '5010', 'name' => 'Payment processing fees', 'type' => 'expense', 'balance_sheet_category' => null, 'pl_group' => 'cost_of_sales'],
            ['account_code' => '6000', 'name' => 'Software and subscriptions', 'type' => 'expense', 'balance_sheet_category' => null, 'pl_group' => 'operating'],
            ['account_code' => '6010', 'name' => 'Professional fees', 'type' => 'expense', 'balance_sheet_category' => null, 'pl_group' => 'operating'],
            ['account_code' => '6020', 'name' => 'Marketing and websites', 'type' => 'expense', 'balance_sheet_category' => null, 'pl_group' => 'operating'],
            ['account_code' => '6030', 'name' => 'Office and administration', 'type' => 'expense', 'balance_sheet_category' => null, 'pl_group' => 'operating'],
            ['account_code' => '6040', 'name' => 'Bank charges', 'type' => 'expense', 'balance_sheet_category' => null, 'pl_group' => 'operating'],
            ['account_code' => '6050', 'name' => 'Formation and registration', 'type' => 'expense', 'balance_sheet_category' => null, 'pl_group' => 'operating'],
            ['account_code' => '6060', 'name' => 'Travel', 'type' => 'expense', 'balance_sheet_category' => null, 'pl_group' => 'operating'],
            ['account_code' => '6070', 'name' => 'Depreciation and amortisation', 'type' => 'expense', 'balance_sheet_category' => null, 'pl_group' => 'operating'],
            ['account_code' => '6990', 'name' => 'General expenses', 'type' => 'expense', 'balance_sheet_category' => null, 'pl_group' => 'operating'],
            ['account_code' => '7000', 'name' => 'Corporate tax expense', 'type' => 'expense', 'balance_sheet_category' => null, 'pl_group' => 'tax'],
        ];
    }

    public static function expenseAccountCode(string $category): string
    {
        return match ($category) {
            'hosting', 'infrastructure' => '5000',
            'stripe_fees', 'payment_fees' => '5010',
            'software' => '6000',
            'professional', 'legal', 'accountancy' => '6010',
            'marketing', 'website' => '6020',
            'office', 'admin' => '6030',
            'bank' => '6040',
            'formation', 'registration' => '6050',
            'travel' => '6060',
            'depreciation' => '6070',
            default => '6990',
        };
    }

    public static function cashAccountCode(string $paymentMethod): string
    {
        return match ($paymentMethod) {
            'stripe' => self::STRIPE_CLEARING,
            default => self::BANK,
        };
    }
}

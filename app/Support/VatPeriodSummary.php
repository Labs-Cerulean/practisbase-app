<?php

namespace App\Support;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\TaxPayment;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Calendar-year or quarterly VAT pack for Article 10 sole traders.
 * Uses the same regime + expense treatments as the annual fiscal engine.
 */
class VatPeriodSummary
{
    /**
     * @return array{from: string, to: string, label: string, key: string}
     */
    public static function resolveRange(int $year, ?int $quarter): array
    {
        if ($quarter === null || $quarter < 1 || $quarter > 4) {
            return [
                'from' => sprintf('%04d-01-01', $year),
                'to' => sprintf('%04d-12-31', $year),
                'label' => 'Full year '.$year,
                'key' => 'full',
            ];
        }

        $startMonth = (($quarter - 1) * 3) + 1;
        $from = Carbon::create($year, $startMonth, 1)->startOfDay();
        $to = (clone $from)->addMonths(2)->endOfMonth()->startOfDay();

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'label' => 'Q'.$quarter.' '.$year.' ('.$from->format('d M').' – '.$to->format('d M Y').')',
            'key' => 'q'.$quarter,
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function periodOptions(int $year): array
    {
        return [
            ['value' => 'full', 'label' => 'Full year '.$year],
            ['value' => '1', 'label' => 'Q1 (Jan – Mar)'],
            ['value' => '2', 'label' => 'Q2 (Apr – Jun)'],
            ['value' => '3', 'label' => 'Q3 (Jul – Sep)'],
            ['value' => '4', 'label' => 'Q4 (Oct – Dec)'],
        ];
    }

    public static function parsePeriodParam(?string $period): ?int
    {
        if ($period === null || $period === '' || $period === 'full') {
            return null;
        }

        $q = (int) $period;

        return ($q >= 1 && $q <= 4) ? $q : null;
    }

    /**
     * @return array<string, mixed>
     */
    public static function forUser(User $user, int $year, ?int $quarter = null): array
    {
        $range = self::resolveRange($year, $quarter);
        $from = $range['from'];
        $to = $range['to'];

        $invoiceCount = 0;
        $creditCount = 0;
        $expenseCount = 0;
        $salesGross = 0.0;
        $salesSubtotal = 0.0;
        $outputVat = 0.0;
        $art10SalesGross = 0.0;
        $art10SalesSubtotal = 0.0;
        $nonArt10SalesGross = 0.0;
        $expensesExVat = 0.0;
        $expensesVatLogged = 0.0;
        $inputVat = 0.0;
        $deductibleExpenses = 0.0;
        $hasArt10Docs = false;

        $invoices = Invoice::where('user_id', $user->id)
            ->where('type', 'invoice')
            ->whereDate('issue_date', '>=', $from)
            ->whereDate('issue_date', '<=', $to)
            ->get(['id', 'issue_date', 'total', 'subtotal', 'vat_total', 'invoice_number']);

        foreach ($invoices as $invoice) {
            $invoiceCount++;
            $day = Carbon::parse($invoice->issue_date)->toDateString();
            $regime = RegimeHistory::forDate($user, $day);
            $isArt10 = ($regime['vat_status'] ?? '') === 'article_10';
            $total = (float) $invoice->total;
            $subtotal = (float) $invoice->subtotal;
            $vat = (float) $invoice->vat_total;
            $salesGross += $total;
            $salesSubtotal += $subtotal;
            if ($isArt10) {
                $hasArt10Docs = true;
                $art10SalesGross += $total;
                $art10SalesSubtotal += $subtotal;
                $outputVat += $vat;
            } else {
                $nonArt10SalesGross += $total;
            }
        }

        $credits = Invoice::where('user_id', $user->id)
            ->where('type', 'credit_note')
            ->whereHas('parentDocument', function ($q) use ($user, $from, $to) {
                $q->where('user_id', $user->id)
                    ->where('type', 'invoice')
                    ->whereDate('issue_date', '>=', $from)
                    ->whereDate('issue_date', '<=', $to);
            })
            ->with(['parentDocument:id,issue_date,user_id,type'])
            ->get(['id', 'parent_document_id', 'total', 'subtotal', 'vat_total', 'invoice_number']);

        foreach ($credits as $credit) {
            $creditCount++;
            $parentDate = $credit->parentDocument?->issue_date;
            $day = $parentDate
                ? Carbon::parse($parentDate)->toDateString()
                : $to;
            $regime = RegimeHistory::forDate($user, $day);
            $isArt10 = ($regime['vat_status'] ?? '') === 'article_10';
            $total = (float) $credit->total;
            $subtotal = (float) $credit->subtotal;
            $vat = (float) $credit->vat_total;
            $salesGross -= $total;
            $salesSubtotal -= $subtotal;
            if ($isArt10) {
                $hasArt10Docs = true;
                $art10SalesGross -= $total;
                $art10SalesSubtotal -= $subtotal;
                $outputVat -= $vat;
            } else {
                $nonArt10SalesGross -= $total;
            }
        }

        $expenses = Expense::where('user_id', $user->id)
            ->whereDate('expense_date', '>=', $from)
            ->whereDate('expense_date', '<=', $to)
            ->get(['expense_date', 'amount', 'vat_amount', 'category', 'business_use_percent', 'description']);

        foreach ($expenses as $expense) {
            $expenseCount++;
            $day = Carbon::parse($expense->expense_date)->toDateString();
            $regime = RegimeHistory::forDate($user, $day);
            $isArt10 = ($regime['vat_status'] ?? '') === 'article_10';
            $ex = (float) $expense->amount;
            $vat = (float) $expense->vat_amount;
            $category = (string) $expense->category;
            $treatment = ExpenseTreatment::forCategory($category);
            $expensesExVat += $ex;
            $expensesVatLogged += $vat;

            if ($treatment === ExpenseTreatment::CAPITAL) {
                if ($isArt10) {
                    $hasArt10Docs = true;
                    $inputVat += $vat;
                }
                continue;
            }

            $share = 100.0;
            if ($treatment === ExpenseTreatment::BUSINESS_SHARE) {
                $share = max(0.0, min(100.0, (float) ($expense->business_use_percent ?? 0)));
            } elseif ($treatment === ExpenseTreatment::WFH_SHARE) {
                $share = max(0.0, min(100.0, (float) ($expense->business_use_percent ?? $user->home_office_percent ?? 0)));
            }

            $grossOrNet = $isArt10 ? $ex : ($ex + $vat);
            $deductibleExpenses += $grossOrNet * ($share / 100.0);
            if ($isArt10) {
                $hasArt10Docs = true;
                $inputVat += $vat * ($share / 100.0);
            }
        }

        // Wear & tear is an income-tax deduction (not input VAT). Show only when its
        // attribution date falls in this period, as a separate note for the user.
        $wearAndTear = 0.0;
        $assets = \App\Models\CapitalAsset::where('user_id', $user->id)
            ->where('purchase_date', '<=', $to)
            ->get();
        foreach ($assets as $asset) {
            $allowance = $asset->allowanceForYear($year);
            if ($allowance <= 0) {
                continue;
            }
            $purchaseYear = (int) $asset->purchase_date->format('Y');
            $attrDate = $purchaseYear === $year
                ? $asset->purchase_date->toDateString()
                : sprintf('%04d-01-01', $year);
            if ($attrDate >= $from && $attrDate <= $to) {
                $wearAndTear += $allowance;
            }
        }

        $vatPaid = (float) TaxPayment::where('user_id', $user->id)
            ->where('year', $year)
            ->where('payment_type', 'vat')
            ->whereDate('payment_date', '>=', $from)
            ->whereDate('payment_date', '<=', $to)
            ->sum('amount');

        $netVat = $outputVat - $inputVat;
        $vatBalance = $netVat - $vatPaid;

        $tip = RegimeHistory::tipFromUser($user);
        $showVatMath = $hasArt10Docs || ($tip['vat_status'] ?? '') === 'article_10';

        return [
            'year' => $year,
            'quarter' => $quarter,
            'period_key' => $range['key'],
            'period_label' => $range['label'],
            'from' => $from,
            'to' => $to,
            'invoice_count' => $invoiceCount,
            'credit_count' => $creditCount,
            'expense_count' => $expenseCount,
            'sales_gross' => round($salesGross, 2),
            'sales_subtotal' => round($salesSubtotal, 2),
            'art10_sales_gross' => round($art10SalesGross, 2),
            'art10_sales_subtotal' => round($art10SalesSubtotal, 2),
            'non_art10_sales_gross' => round($nonArt10SalesGross, 2),
            'output_vat' => round($outputVat, 2),
            'input_vat' => round($inputVat, 2),
            'expenses_ex_vat' => round($expensesExVat, 2),
            'expenses_vat_logged' => round($expensesVatLogged, 2),
            'deductible_expenses' => round($deductibleExpenses, 2),
            'wear_and_tear' => round($wearAndTear, 2),
            'net_vat' => round($netVat, 2),
            'vat_paid' => round($vatPaid, 2),
            'vat_balance' => round($vatBalance, 2),
            'show_vat_math' => $showVatMath,
            'has_art10_docs' => $hasArt10Docs,
            'vat_number' => $user->vat_number,
            'lines' => [
                'Invoices in period' => (string) $invoiceCount,
                'Credit notes (on period invoices)' => (string) $creditCount,
                'Expenses logged' => (string) $expenseCount,
                'Net sales (gross)' => '€'.number_format($salesGross, 2),
                'Article 10 sales (ex-VAT)' => '€'.number_format($art10SalesSubtotal, 2),
                'Output VAT' => '€'.number_format($outputVat, 2),
                'Input VAT (reclaimable)' => '€'.number_format($inputVat, 2),
                'Net VAT for period' => '€'.number_format($netVat, 2),
                'VAT payments logged' => '€'.number_format($vatPaid, 2),
                'VAT balance' => '€'.number_format($vatBalance, 2),
            ],
        ];
    }
}

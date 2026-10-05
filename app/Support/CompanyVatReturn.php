<?php

namespace App\Support;

use App\Models\CompanyExpense;
use App\Models\CompanyInvoice;
use App\Models\CompanyProfile;
use Illuminate\Support\Carbon;

/**
 * Article 10 company VAT worksheet for the next MTCA return.
 * Proformas are excluded. Reverse charge is output and input.
 */
class CompanyVatReturn
{
    /**
     * @param  list<array<string, mixed>>  $events
     * @return array<string, mixed>|null
     */
    public static function pickNext(array $events, Carbon $today): ?array
    {
        $filings = [];
        foreach ($events as $event) {
            if (($event['category'] ?? '') !== 'vat' || ($event['severity'] ?? 'filing') !== 'filing') {
                continue;
            }
            if (empty($event['period_from']) || empty($event['period_to'])) {
                continue;
            }
            $filings[] = $event;
        }

        usort($filings, fn ($a, $b) => strcmp((string) $a['due'], (string) $b['due']));

        $todayKey = $today->toDateString();
        foreach ($filings as $event) {
            if ((string) $event['due'] >= $todayKey) {
                return $event;
            }
        }

        return $filings === [] ? null : $filings[array_key_last($filings)];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function nextFor(CompanyProfile $profile, int $userId, ?Carbon $today = null): ?array
    {
        if (! $profile->isArticle10()) {
            return null;
        }

        $today = ($today ?: Carbon::today())->copy()->startOfDay();
        $year = (int) $today->format('Y');
        $events = array_merge(
            CompanyComplianceCalendar::events($profile, $year),
            CompanyComplianceCalendar::events($profile, $year + 1)
        );
        $filing = self::pickNext($events, $today);
        if (! $filing) {
            return null;
        }

        $periodFrom = Carbon::parse($filing['period_from'])->startOfDay();
        $periodTo = Carbon::parse($filing['period_to'])->startOfDay();
        $companyStart = $profile->first_period_start
            ? $profile->first_period_start->copy()->startOfDay()
            : null;
        $from = ($companyStart && $companyStart->gt($periodFrom)) ? $companyStart->copy() : $periodFrom->copy();
        $to = $periodTo->copy();
        $open = $today->lt($to);
        if ($open) {
            $to = $today->copy();
        }
        if ($from->gt($to)) {
            $from = $to->copy();
        }

        $first = $companyStart && $companyStart->gt($periodFrom) && $companyStart->lte($periodTo);

        return self::build($userId, $filing, $from, $to, $periodFrom, $periodTo, $open, $first);
    }

    /**
     * @param  array<string, mixed>  $filing
     * @return array<string, mixed>
     */
    public static function build(
        int $userId,
        array $filing,
        Carbon $from,
        Carbon $to,
        Carbon $periodFrom,
        Carbon $periodTo,
        bool $periodOpen,
        bool $firstReturn
    ): array {
        $sales = CompanyInvoice::query()
            ->where('user_id', $userId)
            ->where('type', 'invoice')
            ->whereDate('issue_date', '>=', $from->toDateString())
            ->whereDate('issue_date', '<=', $to->toDateString())
            ->get(['subtotal', 'vat_total']);
        $credits = CompanyInvoice::query()
            ->where('user_id', $userId)
            ->where('type', 'credit_note')
            ->whereDate('issue_date', '>=', $from->toDateString())
            ->whereDate('issue_date', '<=', $to->toDateString())
            ->get(['subtotal', 'vat_total']);
        $expenses = CompanyExpense::query()
            ->where('user_id', $userId)
            ->whereNull('reversed_at')
            ->where(function ($q) {
                $q->where('is_pre_incorporation', false)->orWhereNull('is_pre_incorporation');
            })
            ->whereDate('expense_date', '>=', $from->toDateString())
            ->whereDate('expense_date', '<=', $to->toDateString())
            ->get(['amount', 'vat_amount', 'is_reverse_charge']);
        $openRfps = CompanyInvoice::query()
            ->where('user_id', $userId)
            ->where('type', 'rfp')
            ->where('status', '!=', 'converted')
            ->whereDate('issue_date', '>=', $from->toDateString())
            ->whereDate('issue_date', '<=', $to->toDateString())
            ->get(['total']);

        $localInput = 0.0;
        $reverseNet = 0.0;
        $reverseVat = 0.0;
        foreach ($expenses as $expense) {
            if ($expense->is_reverse_charge) {
                $reverseNet = round($reverseNet + (float) $expense->amount, 2);
                $reverseVat = round($reverseVat + (float) $expense->vat_amount, 2);
            } else {
                $localInput = round($localInput + (float) $expense->vat_amount, 2);
            }
        }

        $math = self::fromTotals(
            (float) $sales->sum('subtotal'),
            (float) $sales->sum('vat_total'),
            (float) $credits->sum('subtotal'),
            (float) $credits->sum('vat_total'),
            $localInput,
            $reverseNet,
            $reverseVat,
            $openRfps->count(),
            (float) $openRfps->sum('total')
        );

        return array_merge($math, [
            'key' => (string) $filing['key'],
            'label' => (string) $filing['label'],
            'due' => (string) $filing['due'],
            'hint' => (string) ($filing['hint'] ?? ''),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'period_from' => $periodFrom->toDateString(),
            'period_to' => $periodTo->toDateString(),
            'period_open' => $periodOpen,
            'first_return' => $firstReturn,
            'sales_count' => $sales->count(),
            'credit_count' => $credits->count(),
            'expense_count' => $expenses->count(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function fromTotals(
        float $salesNet,
        float $salesVat,
        float $creditNet,
        float $creditVat,
        float $localInputVat,
        float $reverseChargeNet,
        float $reverseChargeVat,
        int $openRfpCount = 0,
        float $openRfpTotal = 0.0
    ): array {
        $taxableSupplies = round($salesNet - $creditNet, 2);
        $salesOutputVat = round($salesVat - $creditVat, 2);
        $outputVat = round($salesOutputVat + $reverseChargeVat, 2);
        $inputVat = round($localInputVat + $reverseChargeVat, 2);
        $net = round($outputVat - $inputVat, 2);

        $position = 'nil';
        if ($net > 0.009) {
            $position = 'pay';
        } elseif ($net < -0.009) {
            $position = 'reclaim';
        }

        return [
            'taxable_supplies' => $taxableSupplies,
            'sales_output_vat' => $salesOutputVat,
            'reverse_charge_net' => round($reverseChargeNet, 2),
            'reverse_charge_vat' => round($reverseChargeVat, 2),
            'local_input_vat' => round($localInputVat, 2),
            'output_vat' => $outputVat,
            'input_vat' => $inputVat,
            'net' => $net,
            'position' => $position,
            'open_rfp_count' => $openRfpCount,
            'open_rfp_total' => round($openRfpTotal, 2),
            'evidence' => self::evidence(),
        ];
    }

    /**
     * @return list<string>
     */
    public static function evidence(): array
    {
        return [
            'Tax invoices you issued in this period. Download each PDF from Invoices. These are the sales on the return.',
            'Credit notes issued in this period. They reduce the sales and the output VAT.',
            'Supplier invoices that show Maltese VAT, saved under Expenses, and the proof of payment when you have it.',
            'Foreign invoices where you self-charged VAT (reverse charge). The VAT is declared as output and claimed back as input.',
            'Proformas are not part of the return. They carry no VAT until they become tax invoices.',
            'You type the figures on the MTCA site with your e-ID. You do not normally upload every invoice with the return. Keep this pack. VAT records are kept for six years.',
        ];
    }
}

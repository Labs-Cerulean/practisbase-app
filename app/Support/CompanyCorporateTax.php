<?php

namespace App\Support;

use App\Models\CompanyProfile;
use App\Models\TaxRate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * Maltese corporate tax for the operator company desk.
 * Flat company rate only. Sole-trader TA22 and personal brackets are never applied.
 */
class CompanyCorporateTax
{
    public const STATUTORY_RATE = 0.35;

    /** @var list<string> */
    private const RATE_TYPES = ['company', 'corporate', 'income_company'];

    /**
     * Liability cannot go negative. A loss is €0, not a tax asset.
     */
    public static function liability(float $profitBeforeTax, float $rate): float
    {
        $profit = max(0, round($profitBeforeTax, 2));
        $rate = max(0, $rate);

        return max(0, round($profit * $rate, 2));
    }

    /**
     * The financial period that contains this day, measured up to that day
     * and never past the period end.
     *
     * @return array{from: string, to: string, measured_to: string, open: bool}|null
     */
    public static function periodContaining(CompanyProfile $profile, string $asOf): ?array
    {
        $start = self::day($profile->first_period_start);
        $firstEnd = self::day($profile->first_period_end);
        $asOf = substr($asOf, 0, 10);
        if ($start === null || $firstEnd === null || $asOf < $start) {
            return null;
        }

        $period = ['from' => $start, 'to' => $firstEnd];
        for ($i = 0; $i < 40; $i++) {
            if ($asOf <= $period['to']) {
                $measured = $asOf < $period['from'] ? $period['from'] : $asOf;

                return [
                    'from' => $period['from'],
                    'to' => $period['to'],
                    'measured_to' => $measured,
                    'open' => $asOf < $period['to'],
                ];
            }

            $nextStart = Carbon::parse($period['to'])->addDay()->toDateString();
            $period = [
                'from' => $nextStart,
                'to' => self::yearEndOnOrAfter($profile, $nextStart),
            ];
        }

        return null;
    }

    /**
     * Post the current period's provision and return its receipt.
     * A previous period that has already ended is brought up to its own end
     * date when that year is still open.
     *
     * @return array<string, mixed>
     */
    public static function sync(int $userId, ?string $asOf = null): array
    {
        $asOf = self::clampAsOf($asOf);
        $profile = CompanyProfile::where('user_id', $userId)->first();
        if (! $profile) {
            return self::emptyAssessment($asOf);
        }

        self::prepareLedger($userId);
        $current = self::periodContaining($profile, $asOf);
        if (! $current) {
            return self::emptyAssessment($asOf);
        }

        $previousDay = Carbon::parse($current['from'])->subDay()->toDateString();
        $firstStart = self::day($profile->first_period_start);
        if ($firstStart !== null && $previousDay >= $firstStart) {
            $previous = self::periodContaining($profile, $previousDay);
            if ($previous && $previous['to'] !== $current['to']) {
                self::postPeriod($userId, $previous);
            }
        }

        return self::postPeriod($userId, $current);
    }

    /**
     * Assessment for the financial period that ends in this calendar year.
     * Null when that period has not started. A locked year is calculated and not rewritten.
     *
     * @return array<string, mixed>|null
     */
    public static function forFinancialYear(int $userId, int $year, ?string $asOf = null): ?array
    {
        $asOf = self::clampAsOf($asOf);
        $profile = CompanyProfile::where('user_id', $userId)->first();
        if (! $profile) {
            return null;
        }

        $period = self::periodEndingInYear($profile, $year);
        if (! $period || $period['from'] > $asOf) {
            return null;
        }

        self::prepareLedger($userId);
        $period['measured_to'] = min($asOf, $period['to']);
        $period['open'] = $asOf < $period['to'];

        return self::postPeriod($userId, $period);
    }

    /**
     * @return array{from: string, to: string}|null
     */
    public static function periodEndingInYear(CompanyProfile $profile, int $year): ?array
    {
        $start = self::day($profile->first_period_start);
        $firstEnd = self::day($profile->first_period_end);
        if ($start === null || $firstEnd === null) {
            return null;
        }

        $period = ['from' => $start, 'to' => $firstEnd];
        for ($i = 0; $i < 40; $i++) {
            $endYear = (int) substr($period['to'], 0, 4);
            if ($endYear === $year) {
                return $period;
            }
            if ($endYear > $year) {
                return null;
            }
            $nextStart = Carbon::parse($period['to'])->addDay()->toDateString();
            $period = [
                'from' => $nextStart,
                'to' => self::yearEndOnOrAfter($profile, $nextStart),
            ];
        }

        return null;
    }

    /**
     * @param  array{from: string, to: string, measured_to: string, open: bool}  $period
     * @return array<string, mixed>
     */
    private static function postPeriod(int $userId, array $period): array
    {
        $snapshot = CompanyLedger::profitSnapshot($userId, $period['from'], $period['measured_to']);
        $profitBeforeTax = round((float) $snapshot['profit_before_tax'], 2);
        $year = (int) substr($period['to'], 0, 4);
        $rate = self::resolveRate($year);
        $taxable = max(0, $profitBeforeTax);
        $liability = self::liability($profitBeforeTax, $rate['rate']);
        $saved = CompanyLedger::syncCorporateTaxProvision(
            $userId,
            $period['to'],
            $period['measured_to'],
            $liability,
            'Corporate tax provision · '.self::display($period['from']).' – '.self::display($period['to']),
            'max(0, taxable profit × '.$rate['label'].')'
        );

        return self::assessment($period, $snapshot, $profitBeforeTax, $taxable, $rate, $liability, $saved);
    }

    /**
     * @param  array{from: string, to: string, measured_to: string, open: bool}  $period
     * @param  array<string, mixed>  $snapshot
     * @param  array{rate: float, label: string, source: string, year: ?int, assumed: bool, assumption: string}  $rate
     * @param  array{posted_amount: float, posted_date: ?string, blocked: bool, reason: ?string}  $saved
     * @return array<string, mixed>
     */
    private static function assessment(
        array $period,
        array $snapshot,
        float $profitBeforeTax,
        float $taxable,
        array $rate,
        float $liability,
        array $saved
    ): array {
        $working = 'max(0, '.self::money($taxable).' × '.$rate['label'].') = '.self::money($liability);
        $notice = 'Nothing is filed from this screen. This is the ledger provision for the financial period.';
        if ($period['open']) {
            $notice .= ' The period is still open, so the figure can change.';
        }
        if ($saved['blocked'] && $saved['reason']) {
            $notice .= ' '.$saved['reason'].' The ledger was not changed.';
        }

        $components = [];
        foreach ([
            'revenue' => 'Revenue',
            'cost_of_sales' => 'Cost of sales',
            'operating' => 'Operating expenses',
        ] as $key => $group) {
            foreach ($snapshot[$key] ?? [] as $row) {
                $components[] = [
                    'account_code' => $row['account_code'],
                    'name' => $row['name'],
                    'group' => $group,
                    'amount' => $row['amount'],
                ];
            }
        }

        $ledgerDetail = $liability < 0.005
            ? 'No journal. A loss or a nil profit does not create a tax asset, so 2500 Corporate tax payable stays at zero.'
            : 'Debit 7000 Corporate tax expense '.self::money($saved['posted_amount'])
                .'. Credit 2500 Corporate tax payable '.self::money($saved['posted_amount']).'.';
        if ($saved['posted_date']) {
            $ledgerDetail .= ' Dated '.self::display($saved['posted_date']).'.';
        }
        if ($saved['blocked'] && abs($saved['posted_amount'] - $liability) >= 0.005) {
            $ledgerDetail .= ' Calculated liability '.self::money($liability).' is not the figure on the ledger.';
        }

        $receipt = [];
        $receipt[] = [
            'label' => 'Period',
            'detail' => 'First financial period when it is still that period. Profit is taken from the ledger, excluding VAT and excluding this tax charge.',
            'amount' => null,
            'text' => self::display($period['from']).' – '.self::display($period['to']),
            'strong' => false,
        ];
        $receipt[] = [
            'label' => 'Measured to',
            'detail' => $period['open']
                ? 'Later profit in this period will move this provision. The measurement date is not after today.'
                : 'The period has ended. The provision is measured through the period end.',
            'amount' => null,
            'text' => self::display($period['measured_to']),
            'strong' => false,
        ];
        foreach ($components as $row) {
            $receipt[] = [
                'label' => $row['account_code'].' · '.$row['name'],
                'detail' => $row['group'],
                'amount' => $row['amount'],
                'text' => null,
                'strong' => false,
            ];
        }
        $receipt[] = [
            'label' => 'Profit before tax',
            'detail' => 'Revenue − cost of sales − operating expenses. Account 7000 is not in this figure.',
            'amount' => $profitBeforeTax,
            'text' => null,
            'strong' => false,
        ];
        $receipt[] = [
            'label' => 'Taxable profit',
            'detail' => 'max(0, profit before tax). No TA22 cap and no personal bracket. No extra add-back is applied on top of the ledger.',
            'amount' => $taxable,
            'text' => null,
            'strong' => false,
        ];
        $receipt[] = [
            'label' => 'Rate',
            'detail' => $rate['assumption'],
            'amount' => null,
            'text' => $rate['label'],
            'strong' => false,
        ];
        $receipt[] = [
            'label' => 'Corporate tax',
            'detail' => $working,
            'amount' => $liability,
            'text' => null,
            'strong' => true,
        ];
        $receipt[] = [
            'label' => 'Ledger',
            'detail' => $ledgerDetail,
            'amount' => null,
            'text' => $saved['posted_date'] ? self::display($saved['posted_date']) : 'Not posted',
            'strong' => false,
        ];

        return [
            'period_from' => $period['from'],
            'period_to' => $period['to'],
            'period_end' => $period['to'],
            'measured_to' => $period['measured_to'],
            'period_label' => self::display($period['from']).' – '.self::display($period['to']),
            'measured_label' => self::display($period['measured_to']),
            'period_open' => $period['open'],
            'period_year' => (int) substr($period['to'], 0, 4),
            'profit_before_tax' => $profitBeforeTax,
            'taxable_profit' => $taxable,
            'revenue_total' => round((float) ($snapshot['revenue_total'] ?? 0), 2),
            'cost_of_sales_total' => round((float) ($snapshot['cost_of_sales_total'] ?? 0), 2),
            'operating_total' => round((float) ($snapshot['operating_total'] ?? 0), 2),
            'rate' => $rate['rate'],
            'rate_label' => $rate['label'],
            'rate_source' => $rate['source'],
            'rate_year' => $rate['year'],
            'assumed' => $rate['assumed'],
            'assumption' => $rate['assumption'],
            'liability' => $liability,
            'working' => $working,
            'posted_amount' => $saved['posted_amount'],
            'posted_date' => $saved['posted_date'],
            'blocked' => $saved['blocked'],
            'blocked_reason' => $saved['reason'],
            'notice' => $notice,
            'components' => $components,
            'receipt' => $receipt,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function emptyAssessment(string $asOf): array
    {
        $rate = self::statutoryRate(null, false);

        return [
            'period_from' => null,
            'period_to' => null,
            'period_end' => null,
            'measured_to' => $asOf,
            'period_label' => null,
            'measured_label' => self::display($asOf),
            'period_open' => false,
            'period_year' => null,
            'profit_before_tax' => 0.0,
            'taxable_profit' => 0.0,
            'revenue_total' => 0.0,
            'cost_of_sales_total' => 0.0,
            'operating_total' => 0.0,
            'rate' => $rate['rate'],
            'rate_label' => $rate['label'],
            'rate_source' => $rate['source'],
            'rate_year' => null,
            'assumed' => true,
            'assumption' => $rate['assumption'],
            'liability' => 0.0,
            'working' => 'max(0, €0.00 × '.$rate['label'].') = €0.00',
            'posted_amount' => 0.0,
            'posted_date' => null,
            'blocked' => false,
            'blocked_reason' => null,
            'notice' => 'Nothing is filed from this screen.',
            'components' => [],
            'receipt' => [[
                'label' => 'Corporate tax',
                'detail' => $rate['assumption'],
                'amount' => 0.0,
                'text' => null,
                'strong' => true,
            ]],
        ];
    }

    /**
     * @return array{rate: float, label: string, source: string, year: ?int, assumed: bool, assumption: string}
     */
    private static function resolveRate(int $year): array
    {
        if (! Schema::hasTable('tax_rates')) {
            return self::statutoryRate($year, false);
        }

        $rows = TaxRate::query()
            ->whereIn('type', self::RATE_TYPES)
            ->where('year', '<=', $year)
            ->orderByDesc('year')
            ->get();

        if ($rows->isEmpty()) {
            return self::statutoryRate($year, false);
        }

        $years = $rows->pluck('year')->unique()->sortDesc()->values();
        $sawCompanyRow = false;
        foreach ($years as $rowYear) {
            $pool = $rows->where('year', (int) $rowYear);
            foreach (self::RATE_TYPES as $type) {
                $row = $pool->firstWhere('type', $type);
                if (! $row) {
                    continue;
                }
                $sawCompanyRow = true;
                $flat = self::flatRate($row->rates_json);
                if ($flat === null) {
                    continue;
                }
                $label = self::percentLabel($flat);
                if ((int) $rowYear === $year) {
                    return [
                        'rate' => $flat,
                        'label' => $label,
                        'source' => 'stored',
                        'year' => $year,
                        'assumed' => false,
                        'assumption' => 'Company rate of '.$label.' for '.$year.', stored as a flat company tax rate. Sole-trader TA22 and personal brackets are not applied.',
                    ];
                }

                return [
                    'rate' => $flat,
                    'label' => $label,
                    'source' => 'prior_year',
                    'year' => (int) $rowYear,
                    'assumed' => false,
                    'assumption' => 'No company rate is stored for '.$year.'. The '.$rowYear.' company rate of '.$label.' is used. Sole-trader TA22 and personal brackets are not applied.',
                ];
            }
        }

        return self::statutoryRate($year, $sawCompanyRow);
    }

    /**
     * @return array{rate: float, label: string, source: string, year: ?int, assumed: bool, assumption: string}
     */
    private static function statutoryRate(?int $year, bool $unusableRow): array
    {
        $label = self::percentLabel(self::STATUTORY_RATE);
        $assumption = $unusableRow
            ? 'A company tax row is stored for '.$year.' but it is not a flat rate, so the statutory '.$label.' is used. Sole-trader TA22 and personal brackets are not applied.'
            : 'No company rate is stored. Statutory Maltese corporate tax of '.$label.' on taxable profit is used for this period. Sole-trader TA22 and personal brackets are not applied.';

        return [
            'rate' => self::STATUTORY_RATE,
            'label' => $label,
            'source' => 'statutory',
            'year' => null,
            'assumed' => true,
            'assumption' => $assumption,
        ];
    }

    private static function flatRate(mixed $json): ?float
    {
        if (! is_array($json) || ! array_key_exists('rate', $json) || is_array($json['rate'])) {
            return null;
        }

        $rate = (float) $json['rate'];
        if ($rate < 0 || $rate > 1) {
            return null;
        }

        return $rate;
    }

    private static function percentLabel(float $rate): string
    {
        $percent = rtrim(rtrim(number_format($rate * 100, 2, '.', ''), '0'), '.');

        return $percent.'%';
    }

    private static function prepareLedger(int $userId): void
    {
        CompanyLedger::syncComputerDepreciation($userId);
        CompanyLedger::ensureChartForUserId($userId);
    }

    private static function clampAsOf(?string $asOf): string
    {
        $today = now()->toDateString();
        if ($asOf === null || $asOf === '' || $asOf > $today) {
            return $today;
        }

        return substr($asOf, 0, 10);
    }

    private static function yearEndOnOrAfter(CompanyProfile $profile, string $start): string
    {
        $month = (int) ($profile->financial_year_end_month ?: 12);
        $day = (int) ($profile->financial_year_end_day ?: 31);
        $year = (int) substr($start, 0, 4);
        $end = self::dateInYear($year, $month, $day);
        if ($end < $start) {
            $end = self::dateInYear($year + 1, $month, $day);
        }

        return $end;
    }

    private static function dateInYear(int $year, int $month, int $day): string
    {
        $month = min(12, max(1, $month));
        $base = Carbon::create($year, $month, 1)->startOfDay();
        $day = min(max(1, $day), $base->daysInMonth);

        return Carbon::create($year, $month, $day)->toDateString();
    }

    private static function day(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        if (! is_string($value) || $value === '') {
            return null;
        }

        return substr($value, 0, 10);
    }

    private static function display(string $day): string
    {
        return Carbon::parse(substr($day, 0, 10))->format('d M Y');
    }

    private static function money(float $amount): string
    {
        $sign = $amount < 0 ? '-' : '';

        return $sign.'€'.number_format(abs($amount), 2);
    }
}

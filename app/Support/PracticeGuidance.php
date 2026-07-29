<?php

namespace App\Support;

use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Soft deadlines + first-week checklist for sole-trader UX (not a CFR calendar engine).
 */
class PracticeGuidance
{
    /**
     * @return list<array{key: string, label: string, hint: string, href: string, due: ?string, urgent: bool}>
     */
    public static function softDeadlines(User $user, ?int $year = null): array
    {
        $year = $year ?: (int) date('Y');
        $today = Carbon::today();
        $chips = [];

        $vatStatus = $user->vat_status;

        if ($user->canAccessReports() && ($vatStatus === 'article_10' || $vatStatus === 'article_11')) {
            // Soft approximate CFR-style windows (mid-month after quarter) — advisory only.
            $quarters = [
                1 => ['due' => Carbon::create($year, 5, 15), 'period' => '1', 'label' => 'VAT Q1'],
                2 => ['due' => Carbon::create($year, 8, 15), 'period' => '2', 'label' => 'VAT Q2'],
                3 => ['due' => Carbon::create($year, 11, 15), 'period' => '3', 'label' => 'VAT Q3'],
                4 => ['due' => Carbon::create($year + 1, 2, 15), 'period' => '4', 'label' => 'VAT Q4'],
            ];

            $next = null;
            foreach ($quarters as $q) {
                if ($q['due']->gte($today->copy()->subDays(7))) {
                    $next = $q;
                    break;
                }
            }
            if ($next && $vatStatus === 'article_10') {
                $chips[] = [
                    'key' => 'vat_q',
                    'label' => $next['label'],
                    'hint' => 'Around '.$next['due']->format('d M Y').' — open your period pack',
                    'href' => '/reports?year='.$year.'&period='.$next['period'].'#tab-vat',
                    'due' => $next['due']->toDateString(),
                    'urgent' => $next['due']->diffInDays($today) <= 21,
                ];
            } elseif ($next && $vatStatus === 'article_11') {
                $chips[] = [
                    'key' => 'vat_art11',
                    'label' => 'Article 11 watch',
                    'hint' => 'Keep billed revenue under €35k — check your report',
                    'href' => '/reports?year='.$year.'#tab-vat',
                    'due' => null,
                    'urgent' => false,
                ];
            }
        }

        if ($user->canAccessReports()) {
            // Soft provisional tax reminders (common instalment months).
            $ptMonths = [
                Carbon::create($year, 4, 30),
                Carbon::create($year, 8, 31),
                Carbon::create($year, 12, 31),
            ];
            foreach ($ptMonths as $due) {
                if ($due->gte($today->copy()->subDays(14))) {
                    $chips[] = [
                        'key' => 'pt',
                        'label' => 'Provisional tax',
                        'hint' => 'Around '.$due->format('M Y').' — log a payment when you pay',
                        'href' => '/reports?year='.$year.'#tab-payments',
                        'due' => $due->toDateString(),
                        'urgent' => $due->diffInDays($today) <= 21,
                    ];
                    break;
                }
            }
        }

        return $chips;
    }

    /**
     * @return array{items: list<array{key: string, label: string, href: string, done: bool}>, complete: int, total: int, all_done: bool}
     */
    public static function firstWeekChecklist(User $user): array
    {
        $hasClient = Client::where('user_id', $user->id)->exists();
        $hasInvoice = Invoice::where('user_id', $user->id)->whereIn('type', ['invoice', 'rfp'])->exists();
        $hasExpense = Expense::where('user_id', $user->id)->exists();
        $taxOk = filled($user->employment_type) && filled($user->vat_status);
        $canReports = $user->canAccessReports();

        $items = [
            [
                'key' => 'tax',
                'label' => 'Confirm how you work & VAT (tax setup)',
                'href' => '/settings#tax-setup',
                'done' => $taxOk,
            ],
            [
                'key' => 'client',
                'label' => 'Add your first client',
                'href' => '/clients/create',
                'done' => $hasClient,
            ],
            [
                'key' => 'invoice',
                'label' => 'Create your first invoice or RFP',
                'href' => '/ledger/create',
                'done' => $hasInvoice,
            ],
        ];

        if ($canReports) {
            $items[] = [
                'key' => 'expense',
                'label' => 'Log a practice expense',
                'href' => '/expenses/create',
                'done' => $hasExpense,
            ];
            $items[] = [
                'key' => 'report',
                'label' => 'Open Tax & VAT report',
                'href' => '/reports',
                'done' => $hasInvoice || $hasExpense,
            ];
        }

        $complete = count(array_filter($items, fn ($i) => $i['done']));

        return [
            'items' => $items,
            'complete' => $complete,
            'total' => count($items),
            'all_done' => $complete === count($items),
        ];
    }

    /**
     * Current quarter number 1–4.
     */
    public static function currentQuarter(?Carbon $date = null): int
    {
        $date = $date ?: Carbon::today();

        return (int) ceil($date->month / 3);
    }
}

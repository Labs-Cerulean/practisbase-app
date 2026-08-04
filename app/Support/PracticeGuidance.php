<?php

namespace App\Support;

use App\Models\ArchitectProject;
use App\Models\Client;
use App\Models\ClinicalEntry;
use App\Models\EngineerProject;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\MedicalVault;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Soft deadlines + first-week checklist for sole trader UX (not a CFR calendar engine).
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
            // Soft approximate CFR-style windows (mid-month after quarter). Advisory only.
            // Include prior-year Q4 so Jan/Feb still surfaces that return before Q1.
            $quarters = [
                ['due' => Carbon::create($year, 2, 15), 'period' => '4', 'label' => 'VAT Q4', 'report_year' => $year - 1],
                ['due' => Carbon::create($year, 5, 15), 'period' => '1', 'label' => 'VAT Q1', 'report_year' => $year],
                ['due' => Carbon::create($year, 8, 15), 'period' => '2', 'label' => 'VAT Q2', 'report_year' => $year],
                ['due' => Carbon::create($year, 11, 15), 'period' => '3', 'label' => 'VAT Q3', 'report_year' => $year],
                ['due' => Carbon::create($year + 1, 2, 15), 'period' => '4', 'label' => 'VAT Q4', 'report_year' => $year],
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
                    'hint' => 'Around '.$next['due']->format('d M Y').'. Open your period pack',
                    'href' => '/reports?year='.$next['report_year'].'&period='.$next['period'].'#tab-vat',
                    'due' => $next['due']->toDateString(),
                    'urgent' => $next['due']->diffInDays($today) <= 21,
                ];
            } elseif ($next && $vatStatus === 'article_11') {
                $chips[] = [
                    'key' => 'vat_art11',
                    'label' => 'Article 11 watch',
                    'hint' => 'Keep billed revenue under €35k. Check your report',
                    'href' => '/reports?year='.$year.'#tab-vat',
                    'due' => null,
                    'urgent' => false,
                ];
            }
        }

        if ($user->canAccessReports()) {
            $ptMonths = [
                Carbon::create($year, 4, 30),
                Carbon::create($year, 8, 31),
                Carbon::create($year, 12, 21),
            ];
            foreach ($ptMonths as $due) {
                if ($due->gte($today->copy()->subDays(14))) {
                    $chips[] = [
                        'key' => 'pt',
                        'label' => 'Provisional tax',
                        'hint' => 'Around '.$due->format('M Y').'. Log a payment when you pay',
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
        $items = [];

        if ($user->canAccessProPackage('med')) {
            $vault = MedicalVault::activeForUser($user->id);
            $hasPatient = Patient::where('user_id', $user->id)->exists();
            $hasStampable = ClinicalEntry::where('user_id', $user->id)
                ->whereIn('entry_type', ClinicalEntry::STAMPABLE_TYPES)
                ->exists();

            $items[] = [
                'key' => 'vault',
                'label' => 'Set up or unlock your clinical vault',
                'href' => $vault ? '/pro/medical/vault/unlock' : '/pro/medical/vault/setup',
                'done' => (bool) $vault,
            ];
            $items[] = [
                'key' => 'patient',
                'label' => 'Add your first patient',
                'href' => '/pro/medical/patients/create',
                'done' => $hasPatient,
            ];
            $items[] = [
                'key' => 'stampable',
                'label' => 'Draft a prescription, referral, or certificate',
                'href' => '/pro/medical/patients',
                'done' => $hasStampable,
            ];
        } elseif ($user->canAccessProPackage('arch')) {
            $hasClient = \App\Models\ArchitectClient::where('user_id', $user->id)->exists();
            $hasProject = ArchitectProject::where('user_id', $user->id)->exists();
            $items[] = [
                'key' => 'arch_client',
                'label' => 'Add your first architect client',
                'href' => '/pro/architect/clients/create',
                'done' => $hasClient,
            ];
            $items[] = [
                'key' => 'arch_project',
                'label' => 'Create a project under a client',
                'href' => '/pro/architect/projects/create',
                'done' => $hasProject,
            ];
            $items[] = [
                'key' => 'templates',
                'label' => 'Open the BCA templates library',
                'href' => '/pro/architect/templates',
                'done' => $hasProject,
            ];
        } elseif ($user->canAccessProPackage('eng')) {
            $hasClient = \App\Models\EngineerClient::where('user_id', $user->id)->exists();
            $firstProject = EngineerProject::where('user_id', $user->id)
                ->where('status', '!=', 'archived')
                ->orderByDesc('updated_at')
                ->first();
            $hasProject = $firstProject !== null;
            $items[] = [
                'key' => 'eng_client',
                'label' => 'Add your first engineering client',
                'href' => '/pro/engineer/clients/create',
                'done' => $hasClient,
            ];
            $items[] = [
                'key' => 'eng_project',
                'label' => 'Create a project (PA number can wait)',
                'href' => '/pro/engineer/projects/create',
                'done' => $hasProject,
            ];
            $items[] = [
                'key' => 'eng_open',
                'label' => 'Open a project desk',
                'href' => $hasProject
                    ? '/pro/engineer/projects/'.$firstProject->id
                    : '/pro/engineer/projects',
                'done' => $hasProject,
            ];
            $items[] = [
                'key' => 'eng_docs',
                'label' => 'Upload a drawing or document (Rev 1)',
                'href' => $hasProject
                    ? '/pro/engineer/documents/create?project_id='.$firstProject->id
                    : '/pro/engineer/documents/create',
                'done' => \App\Models\EngineerDocument::where('user_id', $user->id)->exists(),
            ];
            $items[] = [
                'key' => 'certificates',
                'label' => 'Open the certificate register',
                'href' => '/pro/certificates',
                'done' => $hasProject,
            ];
        }

        $hasClient = Client::where('user_id', $user->id)->exists();
        $hasInvoice = Invoice::where('user_id', $user->id)->whereIn('type', ['invoice', 'rfp'])->exists();
        $hasExpense = Expense::where('user_id', $user->id)->exists();
        $taxOk = filled($user->employment_type) && filled($user->vat_status);
        $canReports = $user->canAccessReports();
        $practiceOnly = $user->isPracticeOnly();

        if ($canReports) {
            $items[] = [
                'key' => 'tax',
                'label' => 'Confirm how you work and VAT (tax setup)',
                'href' => '/settings#tax-setup',
                'done' => $taxOk,
            ];
        } elseif (! $user->hasPracticeTools()) {
            $items[] = [
                'key' => 'tax',
                'label' => 'Confirm how you work and VAT (tax setup)',
                'href' => '/settings#tax-setup',
                'done' => $taxOk,
            ];
        }

        if (! $practiceOnly || $canReports) {
            $items[] = [
                'key' => 'client',
                'label' => 'Add your first billing client',
                'href' => '/clients/create',
                'done' => $hasClient,
            ];
            $items[] = [
                'key' => 'invoice',
                'label' => 'Create your first invoice or RFP',
                'href' => '/ledger/create',
                'done' => $hasInvoice,
            ];
        } else {
            // Practice-only: billing is secondary. Keep one light invoicing nudge.
            $items[] = [
                'key' => 'client',
                'label' => 'Add a billing client when you need to invoice (Free layer)',
                'href' => '/clients/create',
                'done' => $hasClient,
            ];
        }

        if ($canReports) {
            $items[] = [
                'key' => 'expense',
                'label' => 'Log a practice expense',
                'href' => '/expenses/create',
                'done' => $hasExpense,
            ];
            $items[] = [
                'key' => 'report',
                'label' => 'Open Tax and VAT report',
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

<?php

namespace App\Http\Controllers;

use App\Models\ArchitectProject;
use App\Models\Client;
use App\Models\ClinicalEntry;
use App\Models\EngineerProject;
use App\Models\Invoice;
use App\Models\MedicalVault;
use App\Models\Patient;
use App\Models\Payment;
use App\Support\FiscalReportEngine;
use App\Support\MedicalVaultCrypto;
use App\Support\PracticeGuidance;
use App\Support\TierPolicy;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $userId = $user->id;
        $year = (int) date('Y');

        $hasFinancial = $user->hasStandardFinancial();
        $hasPractice = $user->hasPracticeTools();
        $practiceOnly = $user->isPracticeOnly();
        $package = $user->proPackage();

        $clientCount = Client::where('user_id', $userId)->count();
        $archivedCount = Client::onlyTrashed()->where('user_id', $userId)->count();
        $clientCap = $user->hasUnlimitedClients() ? null : $user->freeClientCap();
        $clientsUsed = (int) ($user->clients_created_count ?? $clientCount);

        $billing = null;
        if ($hasFinancial || $practiceOnly || ! $hasPractice) {
            $billing = $this->billingSnapshot($userId, $year);
        }

        $glance = null;
        if ($hasFinancial) {
            $report = FiscalReportEngine::compute($user, $year);
            $taxDue = (float) ($report['totalTaxLiability'] ?? 0);
            $sscDue = (float) ($report['sscLiability'] ?? 0);
            $taxPaid = (float) ($report['ptTaxPaid'] ?? 0);
            $sscPaid = (float) ($report['ptSscPaid'] ?? 0);
            $netProfit = (float) ($report['netProfit'] ?? 0);
            $glance = [
                'fiscal_revenue' => (float) ($report['fiscalRevenue'] ?? 0),
                'net_profit' => $netProfit,
                'tax_set_aside' => max(0, ($taxDue + $sscDue) - ($taxPaid + $sscPaid)),
                'tax_only_set_aside' => max(0, $taxDue - $taxPaid),
                'ssc_set_aside' => max(0, $sscDue - $sscPaid),
                'tax_due' => $taxDue,
                'ssc_due' => $sscDue,
                'ssc_minimum_band' => $netProfit <= 0.009 && $sscDue > 0.009,
                'vat_balance' => (float) ($report['vatBalance'] ?? 0),
                'has_article_10' => (bool) ($report['hasArticle10'] ?? $report['isArticle10'] ?? false),
            ];
        }

        $practiceDesk = null;
        if ($hasPractice && $package === 'med' && $user->canAccessProPackage('med')) {
            $practiceDesk = $this->medicalDesk($userId);
        } elseif ($hasPractice && $package === 'arch' && $user->canAccessProPackage('arch')) {
            $practiceDesk = $this->architectDesk($userId);
        } elseif ($hasPractice && $package === 'eng' && $user->canAccessProPackage('eng')) {
            $practiceDesk = $this->engineerDesk($userId);
        }

        $checklist = PracticeGuidance::firstWeekChecklist($user);
        $deadlines = $hasFinancial ? PracticeGuidance::softDeadlines($user, $year) : [];

        $mode = match (true) {
            $practiceOnly => 'practice',
            $hasPractice && $hasFinancial => 'pro',
            $hasFinancial => 'standard',
            default => 'free',
        };

        return view('dashboard', [
            'user' => $user,
            'year' => $year,
            'mode' => $mode,
            'package' => $package,
            'tierLabel' => TierPolicy::label($user->tier ?? 'free'),
            'hasFinancial' => $hasFinancial,
            'hasPractice' => $hasPractice,
            'practiceOnly' => $practiceOnly,
            'clientCount' => $clientCount,
            'archivedCount' => $archivedCount,
            'clientCap' => $clientCap,
            'clientsUsed' => $clientsUsed,
            'billing' => $billing,
            'glance' => $glance,
            'practiceDesk' => $practiceDesk,
            'checklist' => $checklist,
            'deadlines' => $deadlines,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function billingSnapshot(int $userId, int $year): array
    {
        $ytdInvoices = Invoice::where('user_id', $userId)
            ->where('type', 'invoice')
            ->whereYear('issue_date', $year)
            ->sum('total');
        $ytdCredits = Invoice::where('user_id', $userId)
            ->where('type', 'credit_note')
            ->whereYear('issue_date', $year)
            ->sum('total');
        $ytdNetInvoiced = $ytdInvoices - $ytdCredits;

        $ytdInvoiceCash = Payment::where('user_id', $userId)
            ->whereYear('payment_date', $year)
            ->whereHas('invoice', fn ($q) => $q->where('type', 'invoice'))
            ->sum('amount');
        $ytdRfpCash = Payment::where('user_id', $userId)
            ->whereYear('payment_date', $year)
            ->whereHas('invoice', fn ($q) => $q->where('type', 'rfp'))
            ->sum('amount');

        $ytdOfficialDues = max(0, $ytdNetInvoiced - max(0, $ytdInvoiceCash));

        $openInvoices = Invoice::where('user_id', $userId)
            ->where('type', 'invoice')
            ->with([
                'client',
                'childDocuments' => fn ($q) => $q->where('type', 'credit_note'),
            ])
            ->orderBy('due_date')
            ->get()
            ->map(function (Invoice $invoice) {
                $credits = $invoice->childDocuments->sum('total');
                $balance = max(0, ($invoice->total - $credits) - (float) $invoice->amount_paid);
                $invoice->open_balance = $balance;
                $invoice->is_overdue = $balance > 0.009
                    && $invoice->due_date
                    && $invoice->due_date->lt(now()->startOfDay());

                return $invoice;
            })
            ->filter(fn (Invoice $invoice) => $invoice->open_balance > 0.009)
            ->values();

        $topLevelTotal = Invoice::where('user_id', $userId)->whereNull('parent_document_id')->sum('total');
        $lifetimeCredits = Invoice::where('user_id', $userId)->where('type', 'credit_note')->sum('total');
        $totalPipeline = $topLevelTotal - $lifetimeCredits;
        $lifetimeInvoices = Invoice::where('user_id', $userId)->where('type', 'invoice')->sum('total');
        $netInvoiced = $lifetimeInvoices - $lifetimeCredits;
        $unbilledPipeline = max(0, $totalPipeline - $netInvoiced);

        return [
            'ytdNetInvoiced' => $ytdNetInvoiced,
            'ytdInvoiceCash' => $ytdInvoiceCash,
            'ytdRfpCash' => $ytdRfpCash,
            'ytdOfficialDues' => $ytdOfficialDues,
            'unpaidCount' => $openInvoices->count(),
            'overdueCount' => $openInvoices->where('is_overdue', true)->count(),
            'overdueTotal' => $openInvoices->where('is_overdue', true)->sum('open_balance'),
            'unpaidTotal' => $openInvoices->sum('open_balance'),
            'recentOpen' => $openInvoices->take(5),
            'unbilledPipeline' => $unbilledPipeline,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function medicalDesk(int $userId): array
    {
        $vault = MedicalVault::activeForUser($userId);
        $vaultUnlocked = $vault && MedicalVaultCrypto::keyFromSession(session('medical_vault_key')) !== null;

        $patientCount = Patient::where('user_id', $userId)->count();
        $drafts = ClinicalEntry::where('user_id', $userId)
            ->whereIn('entry_type', ClinicalEntry::STAMPABLE_TYPES)
            ->whereNull('issued_at')
            ->count();
        $issuedYtd = ClinicalEntry::where('user_id', $userId)
            ->whereNotNull('issued_at')
            ->whereYear('issued_at', (int) date('Y'))
            ->count();
        $recentDrafts = ClinicalEntry::where('user_id', $userId)
            ->whereIn('entry_type', ClinicalEntry::STAMPABLE_TYPES)
            ->whereNull('issued_at')
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get(['id', 'patient_id', 'entry_type', 'entry_date', 'updated_at']);

        return [
            'kind' => 'med',
            'title' => 'Clinical desk',
            'vault' => $vault,
            'vault_unlocked' => $vaultUnlocked,
            'vault_setup' => (bool) $vault,
            'backup_overdue' => $vault ? $vault->isBackupOverdue() : false,
            'patient_count' => $patientCount,
            'draft_stampables' => $drafts,
            'issued_ytd' => $issuedYtd,
            'recent_drafts' => $recentDrafts,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function architectDesk(int $userId): array
    {
        $projects = ArchitectProject::where('user_id', $userId)
            ->with('client')
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        return [
            'kind' => 'arch',
            'title' => 'Studio desk',
            'client_count' => \App\Models\ArchitectClient::where('user_id', $userId)->count(),
            'project_count' => ArchitectProject::where('user_id', $userId)->count(),
            'pa_count' => \App\Models\ArchitectPaApplication::where('user_id', $userId)->count(),
            'active_count' => ArchitectProject::where('user_id', $userId)->where('status', 'active')->count(),
            'recent_projects' => $projects,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function engineerDesk(int $userId): array
    {
        $projects = EngineerProject::where('user_id', $userId)
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        return [
            'kind' => 'eng',
            'title' => 'Technical desk',
            'project_count' => EngineerProject::where('user_id', $userId)->count(),
            'active_count' => EngineerProject::where('user_id', $userId)->where('status', 'active')->count(),
            'recent_projects' => $projects,
        ];
    }
}

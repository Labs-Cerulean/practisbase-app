<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Mail\CompanyClientBillingMail;
use App\Models\CompanyClient;
use App\Models\CompanyInvoice;
use App\Models\CompanyProfile;
use App\Models\CompanyRecurringInvoice;
use App\Support\CompanyBooks;
use App\Support\CompanyClientStatement;
use App\Support\EstateHubBilling;
use App\Support\TenantStorage;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class RecurringInvoiceController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $schedules = CompanyRecurringInvoice::with('client')
            ->where('user_id', $user->id)
            ->orderByDesc('is_active')
            ->orderBy('next_issue_on')
            ->get();
        $clients = CompanyClient::where('user_id', $user->id)->orderBy('name')->get();

        return view('company.accounts.recurring', compact('schedules', 'clients'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'company_client_id' => 'required|exists:company_clients,id,user_id,'.$user->id,
            'section_os' => 'accepted',
            'section_plant' => 'nullable|boolean',
            'section_sales' => 'nullable|boolean',
            'agreed_rate_os' => 'required|numeric|min:0',
            'agreed_rate_plant' => 'nullable|numeric|min:0',
            'agreed_rate_sales' => 'nullable|numeric|min:0',
            'start_date' => 'required|date',
            'day_of_month' => 'required|integer|min:1|max:28',
            'due_days' => 'required|integer|min:0|max:90',
            'notes' => 'nullable|string|max:2000',
            'auto_email' => 'nullable|boolean',
            'auto_reminders' => 'nullable|boolean',
            'reminder_include_statement' => 'nullable|boolean',
            'confirmed' => 'accepted',
        ]);

        $sections = [EstateHubBilling::SECTION_OS];
        if ($request->boolean('section_plant')) {
            $sections[] = EstateHubBilling::SECTION_PLANT;
        }
        if ($request->boolean('section_sales')) {
            $sections[] = EstateHubBilling::SECTION_SALES;
        }

        if (in_array(EstateHubBilling::SECTION_PLANT, $sections, true) && ! $request->filled('agreed_rate_plant')) {
            return back()->withErrors(['agreed_rate_plant' => 'Enter the agreed Plant hub monthly rate.'])->withInput();
        }
        if (in_array(EstateHubBilling::SECTION_SALES, $sections, true) && ! $request->filled('agreed_rate_sales')) {
            return back()->withErrors(['agreed_rate_sales' => 'Enter the agreed Sales hub monthly rate.'])->withInput();
        }

        $rates = [
            'os' => (float) $validated['agreed_rate_os'],
            'plant' => $request->filled('agreed_rate_plant') ? (float) $validated['agreed_rate_plant'] : null,
            'sales' => $request->filled('agreed_rate_sales') ? (float) $validated['agreed_rate_sales'] : null,
        ];

        $items = EstateHubBilling::buildItems($sections, $rates);
        $title = EstateHubBilling::packageLabel($sections);
        $start = Carbon::parse($validated['start_date']);
        $day = (int) $validated['day_of_month'];
        $nextIssue = $this->resolveFirstIssueDate($start, $day);

        $schedule = CompanyRecurringInvoice::create([
            'user_id' => $user->id,
            'company_client_id' => $validated['company_client_id'],
            'title' => $title,
            'day_of_month' => $day,
            'next_issue_on' => $nextIssue->toDateString(),
            'due_days' => $validated['due_days'],
            'items' => $items,
            'notes' => $validated['notes'] ?? null,
            'is_active' => true,
            'package_sections' => $sections,
            'agreed_rate_os' => $rates['os'],
            'agreed_rate_plant' => in_array(EstateHubBilling::SECTION_PLANT, $sections, true) ? $rates['plant'] : null,
            'agreed_rate_sales' => in_array(EstateHubBilling::SECTION_SALES, $sections, true) ? $rates['sales'] : null,
            'start_date' => $start->toDateString(),
            'auto_email' => $request->boolean('auto_email'),
            'auto_reminders' => $request->boolean('auto_reminders'),
            'reminder_include_statement' => $request->boolean('reminder_include_statement', true),
        ]);

        $subtotal = EstateHubBilling::itemsSubtotal($items);

        return redirect('/company/recurring#schedule-'.$schedule->id)->with(
            'success',
            $title.' confirmed for €'.number_format($subtotal, 2).' ex-VAT / month. Upload the signed SLA on the schedule card below.'
        );
    }

    public function generateDue()
    {
        $user = Auth::user();
        $profile = CompanyBooks::ensureProfile($user);
        $today = now()->toDateString();
        $due = CompanyRecurringInvoice::with('client')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->where('next_issue_on', '<=', $today)
            ->get();

        $created = 0;
        $emailed = 0;
        foreach ($due as $schedule) {
            $rfp = null;
            DB::transaction(function () use ($user, $profile, $schedule, &$created, &$rfp) {
                $issueDate = $schedule->next_issue_on->format('Y-m-d');
                $items = $schedule->items ?? [];
                $subtotal = EstateHubBilling::itemsSubtotal($items);
                $vat = $profile->isArticle10() ? round($subtotal * 0.18, 2) : 0.0;
                $total = round($subtotal + $vat, 2);
                $number = CompanyBooks::nextDocumentNumber($user->id, 'rfp', (int) $schedule->next_issue_on->format('Y'));

                $rfp = CompanyInvoice::create([
                    'user_id' => $user->id,
                    'company_client_id' => $schedule->company_client_id,
                    'type' => 'rfp',
                    'document_number' => $number,
                    'issue_date' => $issueDate,
                    'supply_date' => $issueDate,
                    'due_date' => Carbon::parse($issueDate)->addDays((int) $schedule->due_days)->toDateString(),
                    'subtotal' => $subtotal,
                    'vat_total' => $vat,
                    'total' => $total,
                    'amount_paid' => 0,
                    'status' => 'unpaid',
                    'items' => $items,
                    'notes' => trim(($schedule->notes ? $schedule->notes."\n" : '').'Recurring proforma: '.$schedule->title),
                ]);

                $next = $schedule->next_issue_on->copy()->addMonthNoOverflow()->day(min((int) $schedule->day_of_month, 28));
                $schedule->update([
                    'last_generated_on' => $issueDate,
                    'last_invoice_id' => $rfp->id,
                    'next_issue_on' => $next->toDateString(),
                ]);
                $created++;
            });

            if ($rfp && $schedule->auto_email && $this->sendBillingMail(
                $profile,
                $schedule,
                'proforma',
                $rfp,
                $schedule->reminder_include_statement
            )) {
                $emailed++;
            }
        }

        $msg = $created === 0
            ? 'No recurring proformas were due today.'
            : $created.' recurring proforma(s) generated (VAT commits when paid and converted).';
        if ($emailed > 0) {
            $msg .= ' '.$emailed.' emailed to client(s).';
        }

        return back()->with('success', $msg);
    }

    public function toggle(int $schedule)
    {
        $model = CompanyRecurringInvoice::where('user_id', Auth::id())->where('id', $schedule)->firstOrFail();
        $model->update(['is_active' => ! $model->is_active]);

        return back()->with('success', $model->is_active ? 'Schedule activated.' : 'Schedule paused.');
    }

    public function updateSettings(Request $request, int $schedule)
    {
        $model = CompanyRecurringInvoice::where('user_id', Auth::id())->where('id', $schedule)->firstOrFail();
        $model->update([
            'auto_email' => $request->boolean('auto_email'),
            'auto_reminders' => $request->boolean('auto_reminders'),
            'reminder_include_statement' => $request->boolean('reminder_include_statement'),
        ]);

        return back()->with('success', 'Email / reminder settings saved for '.$model->title.'.');
    }

    public function uploadSla(Request $request, int $schedule)
    {
        $user = Auth::user();
        $model = CompanyRecurringInvoice::where('user_id', $user->id)->where('id', $schedule)->firstOrFail();

        $validated = $request->validate([
            'sla' => 'required|file|mimes:pdf,jpg,jpeg,png|max:12288',
        ]);

        if ($model->sla_path) {
            TenantStorage::disk()->delete($model->sla_path);
        }

        $path = $request->file('sla')->store(
            TenantStorage::companySlaPath($user->id),
            TenantStorage::diskName()
        );

        $model->update([
            'sla_path' => $path,
            'sla_original_name' => $validated['sla']->getClientOriginalName(),
        ]);

        return back()->with('success', 'Signed SLA uploaded for '.$model->title.'.');
    }

    public function downloadSla(int $schedule)
    {
        $user = Auth::user();
        $model = CompanyRecurringInvoice::where('user_id', $user->id)->where('id', $schedule)->firstOrFail();
        if (! $model->sla_path || ! TenantStorage::disk()->exists($model->sla_path)) {
            return back()->withErrors(['sla' => 'No SLA file on file for this schedule.']);
        }

        return TenantStorage::disk()->download(
            $model->sla_path,
            $model->sla_original_name ?: 'signed-sla.pdf'
        );
    }

    public function statementPdf(int $schedule)
    {
        $user = Auth::user();
        $profile = CompanyBooks::ensureProfile($user);
        $model = CompanyRecurringInvoice::with('client')
            ->where('user_id', $user->id)
            ->where('id', $schedule)
            ->firstOrFail();

        $statement = CompanyClientStatement::forClient($model->client, $user->id);
        $pdf = Pdf::loadView('company.pdf.client-statement', [
            'profile' => $profile,
            'client' => $model->client,
            'statement' => $statement,
        ]);

        $slug = preg_replace('/[^a-z0-9]+/i', '-', $model->client->name ?? 'client');

        return $pdf->download('statement-'.$slug.'-'.date('Ymd').'.pdf');
    }

    public function sendReminder(Request $request, int $schedule)
    {
        $user = Auth::user();
        $profile = CompanyBooks::ensureProfile($user);
        $model = CompanyRecurringInvoice::with('client')
            ->where('user_id', $user->id)
            ->where('id', $schedule)
            ->firstOrFail();

        $includeStatement = $request->boolean('include_statement', $model->reminder_include_statement);
        $ok = $this->sendBillingMail($profile, $model, 'reminder', null, $includeStatement);

        if (! $ok) {
            return back()->withErrors([
                'email' => 'Could not send reminder. Add a client email on the client record and check mail configuration.',
            ]);
        }

        return back()->with('success', 'Payment reminder sent to '.$model->client->email.'.');
    }

    public function sendDueReminders()
    {
        $user = Auth::user();
        $profile = CompanyBooks::ensureProfile($user);
        $schedules = CompanyRecurringInvoice::with('client')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->where('auto_reminders', true)
            ->get();

        $sent = 0;
        $skipped = 0;
        foreach ($schedules as $schedule) {
            $statement = CompanyClientStatement::forClient($schedule->client, $user->id);
            if ($statement['total_owed'] <= 0.009) {
                $skipped++;
                continue;
            }
            if ($this->sendBillingMail($profile, $schedule, 'reminder', null, $schedule->reminder_include_statement)) {
                $sent++;
            } else {
                $skipped++;
            }
        }

        return back()->with('success', $sent === 0
            ? 'No auto-reminders sent (no open balances or missing client emails).'
            : $sent.' payment reminder(s) sent'.($skipped ? ' ('.$skipped.' skipped).' : '.'));
    }

    private function resolveFirstIssueDate(Carbon $start, int $dayOfMonth): Carbon
    {
        $candidate = $start->copy()->day(min($dayOfMonth, 28));
        if ($candidate->lt($start)) {
            $candidate->addMonthNoOverflow()->day(min($dayOfMonth, 28));
        }

        return $candidate;
    }

    private function sendBillingMail(
        CompanyProfile $profile,
        CompanyRecurringInvoice $schedule,
        string $kind,
        ?CompanyInvoice $document = null,
        bool $includeStatement = false
    ): bool {
        $schedule->loadMissing('client');
        $client = $schedule->client;
        if (! $client || ! filled($client->email)) {
            return false;
        }

        $statement = null;
        $pdfBinary = null;
        if ($includeStatement || $kind === 'statement' || $kind === 'reminder') {
            $statement = CompanyClientStatement::forClient($client, (int) $schedule->user_id);
        }
        if ($includeStatement && $statement) {
            $pdfBinary = Pdf::loadView('company.pdf.client-statement', [
                'profile' => $profile,
                'client' => $client,
                'statement' => $statement,
            ])->output();
        }

        try {
            Mail::to($client->email)->send(new CompanyClientBillingMail(
                $profile,
                $client,
                $schedule,
                $kind,
                $document,
                $statement,
                $pdfBinary
            ));
        } catch (\Throwable $e) {
            report($e);

            return false;
        }

        return true;
    }
}

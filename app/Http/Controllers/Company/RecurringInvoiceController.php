<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

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

        $issuedBySchedule = [];
        foreach ($schedules as $schedule) {
            $issuedBySchedule[$schedule->id] = $this->documentsForSchedule($user->id, $schedule);
        }

        return view('company.accounts.recurring', [
            'schedules' => $schedules,
            'clients' => $clients,
            'issuedBySchedule' => $issuedBySchedule,
            'openCreate' => request()->boolean('new') || old('company_client_id') || old('confirmed'),
            'dueCatchUpCount' => $schedules->filter(
                fn ($s) => $s->is_active && $s->next_issue_on->lte(now()->startOfDay())
            )->count(),
        ]);
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
            'reminder_include_statement' => $request->boolean('reminder_include_statement'),
        ]);

        $profile = CompanyBooks::ensureProfile($user);
        $caughtUp = $this->catchUpSchedule($user, $profile, $schedule);
        $subtotal = EstateHubBilling::itemsSubtotal($items);

        $msg = $title.' saved · €'.number_format($subtotal, 2).' ex-VAT / month.';
        if ($caughtUp['created'] > 0) {
            $msg .= ' Generated '.$caughtUp['created'].' proforma(s) from start date through today.';
        } else {
            $msg .= ' Next issue '.$schedule->fresh()->next_issue_on->format('d M Y').'.';
        }

        return redirect('/company/recurring#schedule-'.$schedule->id)->with('success', $msg);
    }

    public function generateDueGet()
    {
        return redirect('/company/recurring')->with(
            'error',
            'Use the Catch up / Generate button on this page — opening that link directly does not issue proformas.'
        );
    }

    public function generateDue()
    {
        $user = Auth::user();
        $profile = CompanyBooks::ensureProfile($user);
        $due = CompanyRecurringInvoice::with('client')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->where('next_issue_on', '<=', now()->toDateString())
            ->get();

        $created = 0;
        $emailed = 0;
        foreach ($due as $schedule) {
            $result = $this->catchUpSchedule($user, $profile, $schedule);
            $created += $result['created'];
            $emailed += $result['emailed'];
        }

        $msg = $created === 0
            ? 'No proformas were due — all schedules are caught up.'
            : $created.' proforma(s) generated (catch-up through today). VAT commits when paid and converted.';
        if ($emailed > 0) {
            $msg .= ' '.$emailed.' emailed.';
        }

        return redirect('/company/recurring')->with('success', $msg);
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

        $includeStatement = $request->boolean('include_statement', (bool) $model->reminder_include_statement);
        $result = $this->sendBillingMail($profile, $model, 'reminder', null, $includeStatement);

        if (! $result['ok']) {
            return back()->withErrors(['email' => $result['error']]);
        }

        $extra = $result['warning'] ? ' '.$result['warning'] : '';

        return back()->with('success', 'Payment reminder sent to '.$result['to'].'.'.$extra);
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
        $lastError = null;
        foreach ($schedules as $schedule) {
            $statement = CompanyClientStatement::forClient($schedule->client, $user->id);
            if ($statement['total_owed'] <= 0.009) {
                $skipped++;
                continue;
            }
            $result = $this->sendBillingMail($profile, $schedule, 'reminder', null, (bool) $schedule->reminder_include_statement);
            if ($result['ok']) {
                $sent++;
            } else {
                $skipped++;
                $lastError = $result['error'];
            }
        }

        if ($sent === 0 && $lastError) {
            return back()->withErrors(['email' => $lastError]);
        }

        return back()->with('success', $sent === 0
            ? 'No auto-reminders sent (no open balances or missing client emails).'
            : $sent.' payment reminder(s) sent'.($skipped ? ' ('.$skipped.' skipped).' : '.'));
    }

    /**
     * Issue every due month for a schedule until next_issue_on is after today (max 36).
     *
     * @return array{created: int, emailed: int}
     */
    private function catchUpSchedule($user, CompanyProfile $profile, CompanyRecurringInvoice $schedule): array
    {
        $created = 0;
        $emailed = 0;
        $today = Carbon::today();
        $guard = 0;

        $schedule->refresh();

        while ($schedule->is_active && $schedule->next_issue_on->lte($today) && $guard < 36) {
            $guard++;
            $rfp = null;

            DB::transaction(function () use ($user, $profile, $schedule, &$created, &$rfp) {
                $locked = CompanyRecurringInvoice::where('user_id', $user->id)
                    ->where('id', $schedule->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($locked->next_issue_on->gt(Carbon::today())) {
                    $schedule->refresh();

                    return;
                }

                $issueDate = $locked->next_issue_on->format('Y-m-d');
                $items = $locked->items ?? [];
                $subtotal = EstateHubBilling::itemsSubtotal($items);
                $vat = $profile->isArticle10() ? round($subtotal * 0.18, 2) : 0.0;
                $total = round($subtotal + $vat, 2);
                $number = CompanyBooks::nextDocumentNumber($user->id, 'rfp', (int) $locked->next_issue_on->format('Y'));

                $payload = [
                    'user_id' => $user->id,
                    'company_client_id' => $locked->company_client_id,
                    'type' => 'rfp',
                    'document_number' => $number,
                    'issue_date' => $issueDate,
                    'supply_date' => $issueDate,
                    'due_date' => Carbon::parse($issueDate)->addDays((int) $locked->due_days)->toDateString(),
                    'subtotal' => $subtotal,
                    'vat_total' => $vat,
                    'total' => $total,
                    'amount_paid' => 0,
                    'status' => 'unpaid',
                    'items' => $items,
                    'notes' => trim(($locked->notes ? $locked->notes."\n" : '').'Recurring proforma: '.$locked->title),
                ];
                if ($this->hasRecurringLinkColumn()) {
                    $payload['company_recurring_invoice_id'] = $locked->id;
                }

                $rfp = CompanyInvoice::create($payload);

                $next = $locked->next_issue_on->copy()->addMonthNoOverflow()->day(min((int) $locked->day_of_month, 28));
                $locked->update([
                    'last_generated_on' => $issueDate,
                    'last_invoice_id' => $rfp->id,
                    'next_issue_on' => $next->toDateString(),
                ]);
                $schedule->refresh();
                $created++;
            });

            if ($rfp && $schedule->auto_email) {
                $mail = $this->sendBillingMail(
                    $profile,
                    $schedule,
                    'proforma',
                    $rfp,
                    (bool) $schedule->reminder_include_statement
                );
                if ($mail['ok']) {
                    $emailed++;
                }
            }
        }

        return ['created' => $created, 'emailed' => $emailed];
    }

    /**
     * @return Collection<int, CompanyInvoice>
     */
    private function documentsForSchedule(int $userId, CompanyRecurringInvoice $schedule): Collection
    {
        $query = CompanyInvoice::with(['payments', 'childDocuments'])
            ->where('user_id', $userId)
            ->whereNull('parent_document_id')
            ->where(function ($q) use ($schedule) {
                if ($this->hasRecurringLinkColumn()) {
                    $q->where('company_recurring_invoice_id', $schedule->id);
                }
                $q->orWhere(function ($inner) use ($schedule) {
                    $inner->where('company_client_id', $schedule->company_client_id)
                        ->where('notes', 'like', '%Recurring proforma: '.$schedule->title.'%');
                });
                if ($schedule->last_invoice_id) {
                    $q->orWhere('id', $schedule->last_invoice_id);
                }
            })
            ->orderByDesc('issue_date')
            ->orderByDesc('id');

        $docs = $query->get()->unique('id')->values();

        $rfpIds = $docs->where('type', 'rfp')->pluck('id');
        if ($rfpIds->isNotEmpty()) {
            $tax = CompanyInvoice::with(['payments', 'childDocuments'])
                ->where('user_id', $userId)
                ->where('type', 'invoice')
                ->whereIn('linked_document_id', $rfpIds)
                ->get();
            foreach ($tax as $invoice) {
                if (! $docs->contains('id', $invoice->id)) {
                    $docs->push($invoice);
                }
            }
        }

        return $docs->sortByDesc(fn ($d) => $d->issue_date->format('Y-m-d').'-'.$d->id)->values();
    }

    private function hasRecurringLinkColumn(): bool
    {
        static $has = null;
        if ($has === null) {
            try {
                $has = Schema::hasColumn('company_invoices', 'company_recurring_invoice_id');
            } catch (\Throwable) {
                $has = false;
            }
        }

        return $has;
    }

    private function resolveFirstIssueDate(Carbon $start, int $dayOfMonth): Carbon
    {
        $candidate = $start->copy()->day(min($dayOfMonth, 28));
        if ($candidate->lt($start)) {
            $candidate->addMonthNoOverflow()->day(min($dayOfMonth, 28));
        }

        return $candidate;
    }

    /**
     * @return array{ok: bool, error: ?string, warning: ?string, to: ?string}
     */
    private function sendBillingMail(
        CompanyProfile $profile,
        CompanyRecurringInvoice $schedule,
        string $kind,
        ?CompanyInvoice $document = null,
        bool $includeStatement = false
    ): array {
        $schedule->loadMissing('client');
        $client = $schedule->client;
        $to = trim((string) ($client->email ?? ''));

        if (! $client || $to === '') {
            return [
                'ok' => false,
                'error' => 'Client has no email on the company client record. Edit the client and add one.',
                'warning' => null,
                'to' => null,
            ];
        }

        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return [
                'ok' => false,
                'error' => 'Client email "'.$to.'" is not a valid address. Fix it on the client record.',
                'warning' => null,
                'to' => null,
            ];
        }

        $statement = null;
        if ($includeStatement || $kind === 'statement' || $kind === 'reminder') {
            try {
                $statement = CompanyClientStatement::forClient($client, (int) $schedule->user_id);
            } catch (\Throwable $e) {
                report($e);

                return [
                    'ok' => false,
                    'error' => 'Could not build the account statement: '.$e->getMessage(),
                    'warning' => null,
                    'to' => $to,
                ];
            }
        }

        $pdfBinary = null;
        $warning = null;
        if ($includeStatement && $statement) {
            if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class) && ! class_exists(\api_jacket\DomPDF\PDF::class)) {
                $warning = 'Reminder emailed without PDF (DomPDF not installed on this server).';
            } else {
                try {
                    $pdfBinary = Pdf::loadView('company.pdf.client-statement', [
                        'profile' => $profile,
                        'client' => $client,
                        'statement' => $statement,
                    ])->output();
                } catch (\Throwable $e) {
                    report($e);
                    $warning = 'Reminder emailed without PDF (statement render failed: '.$e->getMessage().').';
                    $pdfBinary = null;
                }
            }
        }

        try {
            try {
                $html = view('company.mail.billing-notice', [
                    'kind' => $kind,
                    'clientName' => (string) $client->name,
                    'companyName' => (string) ($profile->legal_name ?: 'Cerulean Labs Ltd'),
                    'vatNumber' => (string) ($profile->vat_number ?: ''),
                    'packageLabel' => $schedule->packageLabel(),
                    'documentNumber' => (string) ($document?->document_number ?? ''),
                    'documentTotal' => number_format((float) ($document?->total ?? 0), 2),
                    'documentDue' => $document && $document->due_date
                        ? $document->due_date->format('d M Y')
                        : '',
                    'officialOwed' => $statement
                        ? number_format((float) $statement['official_owed'], 2)
                        : null,
                    'rfpOwed' => $statement
                        ? number_format((float) $statement['rfp_owed'], 2)
                        : null,
                    'totalOwed' => $statement
                        ? number_format((float) $statement['total_owed'], 2)
                        : null,
                ])->render();
            } catch (\Throwable $e) {
                report($e);

                return [
                    'ok' => false,
                    'error' => 'Mail body render failed: '.$e->getMessage(),
                    'warning' => null,
                    'to' => $to,
                ];
            }

            $company = $profile->legal_name ?: 'Cerulean Labs Ltd';
            $subject = match ($kind) {
                'proforma' => $company.' - monthly proforma '.($document?->document_number ?? ''),
                'reminder' => $company.' - payment reminder',
                'statement' => $company.' - account statement',
                default => $company.' - billing notice',
            };

            $operatorEmail = trim((string) (Auth::user()?->email ?? ''));
            $operatorName = filled($profile->legal_name) ? (string) $profile->legal_name : null;

            Mail::html($html, function ($message) use ($to, $subject, $pdfBinary, $operatorEmail, $operatorName) {
                $message->to($to)->subject($subject);

                if ($operatorEmail !== '' && filter_var($operatorEmail, FILTER_VALIDATE_EMAIL)) {
                    if ($operatorName) {
                        $message->replyTo($operatorEmail, $operatorName);
                    } else {
                        $message->replyTo($operatorEmail);
                    }
                }

                if (is_string($pdfBinary) && $pdfBinary !== '') {
                    $message->attachData($pdfBinary, 'account-statement.pdf', [
                        'mime' => 'application/pdf',
                    ]);
                }
            });
        } catch (\Throwable $e) {
            report($e);

            $hint = $e->getMessage();
            if (stripos($hint, 'Connection') !== false || stripos($hint, 'SMTP') !== false) {
                $hint .= ' Check MAIL_MAILER / MAIL_HOST / MAIL_USERNAME on the server.';
            }

            return [
                'ok' => false,
                'error' => 'Mail send failed: '.$hint,
                'warning' => null,
                'to' => $to,
            ];
        }

        return [
            'ok' => true,
            'error' => null,
            'warning' => $warning,
            'to' => $to,
        ];
    }
}

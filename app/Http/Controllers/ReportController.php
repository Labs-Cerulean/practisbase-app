<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\TaxPayment;
use App\Support\FiscalReportEngine;
use App\Support\FiscalYearGuard;
use App\Support\VatPeriodSummary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $currentYear = (int) date('Y');

        $earliestInvoice = Invoice::where('user_id', $user->id)->orderBy('issue_date', 'asc')->first();
        $earliestPayment = Payment::where('user_id', $user->id)->orderBy('payment_date', 'asc')->first();

        $earliestYear = $currentYear;
        if ($earliestInvoice) {
            $earliestYear = min($earliestYear, (int) date('Y', strtotime($earliestInvoice->issue_date)));
        }
        if ($earliestPayment) {
            $earliestYear = min($earliestYear, (int) date('Y', strtotime($earliestPayment->payment_date)));
        }

        $requestedYear = (int) $request->input('year', $currentYear);
        $selectedYear = max($earliestYear, min($currentYear, $requestedYear));

        $closedRow = FiscalReportEngine::loadClosedYearRow($user->id, $selectedYear);
        $isYearClosed = $closedRow !== null;

        $uninvoicedRfps = Invoice::where('user_id', $user->id)
            ->where('type', 'rfp')
            ->where('amount_paid', '>', 0)
            ->where('status', '!=', 'converted')
            ->whereYear('issue_date', '<=', $selectedYear)
            ->get();

        $uninvoicedRfpCount = $uninvoicedRfps->count();
        $uninvoicedRfpCash = $uninvoicedRfps->sum('amount_paid');

        $snapshot = null;
        if ($isYearClosed && $closedRow) {
            $raw = $closedRow->snapshot_json ?? null;
            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                $snapshot = is_array($decoded) ? $decoded : null;
            } elseif (is_array($raw)) {
                $snapshot = $raw;
            }
        }

        $report = null;
        if ($isYearClosed && $snapshot) {
            $report = FiscalReportEngine::hydrateFromSnapshot($snapshot, $user, $selectedYear);
        }

        if (! $report) {
            $report = FiscalReportEngine::compute($user, $selectedYear);
            if ($isYearClosed) {
                $report['from_snapshot'] = false;
                $report['legacy_closed_without_snapshot'] = true;
            }
        }

        $selectedQuarter = VatPeriodSummary::parsePeriodParam($request->input('period', 'full'));
        $vatPeriod = VatPeriodSummary::forUser($user, $selectedYear, $selectedQuarter);
        $vatPeriodOptions = VatPeriodSummary::periodOptions($selectedYear);

        return view('reports.index', array_merge($report, [
            'user' => $user,
            'selectedYear' => $selectedYear,
            'currentYear' => $currentYear,
            'earliestYear' => $earliestYear,
            'isYearClosed' => $isYearClosed,
            'uninvoicedRfpCount' => $uninvoicedRfpCount,
            'uninvoicedRfpCash' => $uninvoicedRfpCash,
            'legacyClosedWithoutSnapshot' => (bool) ($report['legacy_closed_without_snapshot'] ?? false),
            'snapshotFrozenAt' => $report['frozen_at'] ?? ($closedRow->closed_at ?? null),
            'vatPeriod' => $vatPeriod,
            'vatPeriodOptions' => $vatPeriodOptions,
            'selectedPeriod' => $vatPeriod['period_key'] === 'full' ? 'full' : (string) $selectedQuarter,
        ]));
    }

    public function downloadVatPeriod(Request $request)
    {
        $user = Auth::user();
        $selectedYear = (int) $request->input('year', date('Y'));
        $selectedQuarter = VatPeriodSummary::parsePeriodParam($request->input('period', 'full'));
        $vatPeriod = VatPeriodSummary::forUser($user, $selectedYear, $selectedQuarter);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.vat-period', [
            'user' => $user,
            'vatPeriod' => $vatPeriod,
            'generatedAt' => now(),
        ]);
        $pdf->setPaper('a4', 'portrait');

        $slug = $vatPeriod['period_key'] === 'full'
            ? 'VAT-'.$selectedYear
            : 'VAT-'.$selectedYear.'-'.$vatPeriod['period_key'];

        return $pdf->download($slug.'.pdf');
    }

    public function downloadTa22(Request $request)
    {
        $user = Auth::user();

        $selectedYear = (int) $request->input('year', date('Y'));
        $closedRow = FiscalReportEngine::loadClosedYearRow($user->id, $selectedYear);
        $report = null;
        if ($closedRow) {
            $raw = $closedRow->snapshot_json ?? null;
            $snapshot = is_string($raw) ? json_decode($raw, true) : (is_array($raw) ? $raw : null);
            $report = FiscalReportEngine::hydrateFromSnapshot($snapshot, $user, $selectedYear);
        }
        if (! $report) {
            $report = FiscalReportEngine::compute($user, $selectedYear);
        }

        if (! ($report['hasPartTime'] ?? false) && ($report['ta22Liability'] ?? 0) <= 0 && ($report['profile']['employment_type'] ?? '') !== 'part_time') {
            return redirect('/reports')->withErrors([
                'fiscal_error' => 'TA22 summary is only available for part-time self-employed periods in this year.',
            ]);
        }

        $invoicedRevenue = $report['invoicedRevenue'];
        $deductibleExpenses = $report['deductibleExpenses'];
        $expenseInfo = $report['expenseInfo'];
        $netProfit = $report['netProfit'];
        $ta22Rules = FiscalReportEngine::getRatesSafely('ta22', $selectedYear);
        $ta22Cap = $ta22Rules['max_limit'] ?? 12000;
        $ta22Rate = $ta22Rules['rate'] ?? 0.10;
        $amountEligibleForTa22 = min($netProfit, $ta22Cap);
        $ta22Liability = $report['ta22Liability'];
        $spilloverProfit = max(0, $netProfit - $ta22Cap);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.ta22', compact(
            'user', 'selectedYear', 'invoicedRevenue', 'deductibleExpenses', 'expenseInfo',
            'netProfit', 'ta22Cap', 'ta22Rate', 'amountEligibleForTa22', 'ta22Liability', 'spilloverProfit'
        ));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('TA22-summary-'.$selectedYear.'.pdf');
    }

    public function storeTaxPayment(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'payment_type' => 'required|in:income_tax,ssc,vat',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date|before_or_equal:today',
        ]);

        if (FiscalYearGuard::isClosed(Auth::id(), (int) $request->year)) {
            return back()->withErrors(['fiscal_error' => 'Cannot modify payments for a closed fiscal year.']);
        }

        TaxPayment::create([
            'user_id' => Auth::id(),
            'year' => $request->year,
            'payment_type' => $request->payment_type,
            'amount' => $request->amount,
            'payment_date' => $request->payment_date,
        ]);

        return back()->with('success', 'Tax payment logged successfully.');
    }

    public function destroyTaxPayment($id)
    {
        $payment = TaxPayment::where('id', $id)->where('user_id', Auth::id())->firstOrFail();

        if (FiscalYearGuard::isClosed(Auth::id(), (int) $payment->year)) {
            return back()->withErrors(['fiscal_error' => 'Cannot modify payments for a closed fiscal year.']);
        }

        $payment->delete();

        return back()->with('success', 'Tax payment removed.');
    }

    public function closeYear(Request $request)
    {
        $request->validate(['year' => 'required|integer']);
        $year = (int) $request->year;
        $user = Auth::user();

        if ($year >= (int) date('Y')) {
            return back()->withErrors(['fiscal_error' => 'You cannot close the current fiscal year until December 31st has passed.']);
        }

        if (FiscalYearGuard::isClosed($user->id, $year)) {
            return back()->withErrors(['fiscal_error' => "Fiscal year {$year} is already closed."]);
        }

        $report = FiscalReportEngine::compute($user, $year);
        $snapshot = FiscalReportEngine::buildSnapshotPayload($report);

        DB::table('fiscal_years')->insert([
            'user_id' => $user->id,
            'year' => $year,
            'closed_at' => now(),
            'snapshot_json' => json_encode($snapshot),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', "Fiscal Year {$year} has been permanently closed. The final report is frozen — later Settings changes will not recalculate {$year}.");
    }
}

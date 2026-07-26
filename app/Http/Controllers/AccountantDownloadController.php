<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\TaxPayment;
use App\Support\SimpleZipWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;
use ZipArchive;

class AccountantDownloadController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $currentYear = (int) date('Y');
        $years = range($currentYear, $currentYear - 5);

        return view('exports.accountant', compact('user', 'years', 'currentYear'));
    }

    public function download(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'year' => 'required|integer|min:2000|max:' . ((int) date('Y')),
        ]);

        $year = (int) $validated['year'];
        $filename = 'practisbase-accountant-' . $year . '-' . $user->id . '.zip';

        try {
            $files = [
                "{$year}-documents.csv" => $this->documentsCsv($user->id, $year),
                "{$year}-payments.csv" => $this->paymentsCsv($user->id, $year),
                "{$year}-clients.csv" => $this->clientsCsv($user->id),
                "{$year}-expenses.csv" => $this->expensesCsv($user->id, $year),
                "{$year}-tax-payments.csv" => $this->taxPaymentsCsv($user->id, $year),
                "{$year}-vat-summary.csv" => $this->vatSummaryCsv($user, $year),
                'README.txt' => $this->readme($user->name, $year),
            ];

            $tmp = tempnam(sys_get_temp_dir(), 'pb_acct_');
            if ($tmp === false) {
                return back()->withErrors(['export' => 'Could not create a temporary export file.']);
            }

            // Prefer ext-zip when available; otherwise pure-PHP store ZIP (Railway often lacks php-zip).
            if (class_exists(ZipArchive::class)) {
                $zip = new ZipArchive();
                if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
                    @unlink($tmp);

                    return back()->withErrors(['export' => 'Could not create export archive.']);
                }
                foreach ($files as $name => $contents) {
                    $zip->addFromString($name, $contents);
                }
                $zip->close();
            } else {
                $zip = new SimpleZipWriter();
                foreach ($files as $name => $contents) {
                    $zip->addFile($name, $contents);
                }
                $zip->writeTo($tmp);
            }

            return response()->download($tmp, $filename, [
                'Content-Type' => 'application/zip',
            ])->deleteFileAfterSend(true);
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors([
                'export' => 'Accountant download failed. Please try again or contact support if it persists.',
            ]);
        }
    }

    private function csv(array $headers, array $rows): string
    {
        $fh = fopen('php://temp', 'r+');
        fputcsv($fh, $headers);
        foreach ($rows as $row) {
            fputcsv($fh, $row);
        }
        rewind($fh);
        $csv = stream_get_contents($fh) ?: '';
        fclose($fh);

        return $csv;
    }

    private function documentsCsv(int $userId, int $year): string
    {
        $docs = Invoice::where('user_id', $userId)
            ->whereYear('issue_date', $year)
            ->with('client')
            ->orderBy('issue_date')
            ->orderBy('id')
            ->get();

        $rows = [];
        foreach ($docs as $doc) {
            $fiscal = $doc->type === 'rfp' ? 'non-fiscal (RFP)' : ($doc->type === 'credit_note' ? 'fiscal credit' : 'fiscal invoice');
            $rows[] = [
                $doc->invoice_number,
                $doc->type,
                $fiscal,
                optional($doc->issue_date)->format('Y-m-d'),
                optional($doc->due_date)->format('Y-m-d'),
                optional($doc->client)->name ?? '',
                $doc->client_id,
                number_format((float) $doc->subtotal, 2, '.', ''),
                number_format((float) $doc->vat_total, 2, '.', ''),
                number_format((float) $doc->total, 2, '.', ''),
                number_format((float) $doc->amount_paid, 2, '.', ''),
                $doc->status,
                $doc->parent_document_id,
            ];
        }

        return $this->csv([
            'document_number', 'type', 'fiscal_weight', 'issue_date', 'due_date',
            'client_name', 'client_id', 'subtotal', 'vat_total', 'total',
            'amount_paid', 'status', 'parent_document_id',
        ], $rows);
    }

    private function paymentsCsv(int $userId, int $year): string
    {
        $payments = Payment::where('user_id', $userId)
            ->whereYear('payment_date', $year)
            ->with('invoice')
            ->orderBy('payment_date')
            ->get();

        $rows = [];
        foreach ($payments as $payment) {
            $invoice = $payment->invoice;
            $rows[] = [
                optional($payment->payment_date)->format('Y-m-d'),
                number_format((float) $payment->amount, 2, '.', ''),
                $invoice->invoice_number ?? '',
                $invoice->type ?? '',
                ($invoice && $invoice->type === 'rfp') ? 'non-fiscal' : 'fiscal',
                $invoice->client_id ?? '',
            ];
        }

        return $this->csv([
            'payment_date', 'amount', 'document_number', 'document_type', 'fiscal_weight', 'client_id',
        ], $rows);
    }

    private function clientsCsv(int $userId): string
    {
        $clients = Client::withTrashed()
            ->where('user_id', $userId)
            ->orderBy('name')
            ->get();

        $rows = [];
        foreach ($clients as $client) {
            $profile = is_array($client->profile_data) ? $client->profile_data : [];
            $rows[] = [
                $client->id,
                $client->name,
                $client->type,
                $client->email,
                $client->phone,
                $client->billing_address,
                $profile['vat_number'] ?? '',
                $profile['registration_number'] ?? '',
                $profile['id_card_number'] ?? '',
                $profile['contact_person'] ?? '',
                $client->trashed() ? 'archived' : 'active',
            ];
        }

        return $this->csv([
            'client_id', 'name', 'type', 'email', 'phone', 'billing_address',
            'vat_number', 'registration_number', 'id_card_number', 'contact_person', 'status',
        ], $rows);
    }

    private function expensesCsv(int $userId, int $year): string
    {
        $expenses = Expense::where('user_id', $userId)
            ->whereYear('expense_date', $year)
            ->orderBy('expense_date')
            ->get();

        $rows = [];
        foreach ($expenses as $expense) {
            $rows[] = [
                optional($expense->expense_date)->format('Y-m-d'),
                $expense->category,
                $expense->description,
                number_format((float) $expense->amount, 2, '.', ''),
                number_format((float) $expense->vat_amount, 2, '.', ''),
                number_format($expense->totalWithVat(), 2, '.', ''),
                $expense->receipt_path ? 'yes' : 'no',
            ];
        }

        return $this->csv([
            'expense_date', 'category', 'description', 'amount_ex_vat', 'vat_amount', 'total', 'has_receipt',
        ], $rows);
    }

    private function taxPaymentsCsv(int $userId, int $year): string
    {
        $payments = TaxPayment::where('user_id', $userId)
            ->where('year', $year)
            ->orderBy('payment_date')
            ->get();

        $rows = [];
        foreach ($payments as $payment) {
            $rows[] = [
                $payment->year,
                $payment->payment_type,
                optional($payment->payment_date)->format('Y-m-d'),
                number_format((float) $payment->amount, 2, '.', ''),
            ];
        }

        return $this->csv(['year', 'payment_type', 'payment_date', 'amount'], $rows);
    }

    private function vatSummaryCsv($user, int $year): string
    {
        $invoiced = Invoice::where('user_id', $user->id)
            ->where('type', 'invoice')
            ->whereYear('issue_date', $year)
            ->sum('total');
        $credits = Invoice::where('user_id', $user->id)
            ->where('type', 'credit_note')
            ->whereYear('issue_date', $year)
            ->sum('total');
        $net = max(0, $invoiced - $credits);
        $vatOnDocs = Invoice::where('user_id', $user->id)
            ->whereIn('type', ['invoice', 'credit_note'])
            ->whereYear('issue_date', $year)
            ->get()
            ->sum(function (Invoice $doc) {
                return $doc->type === 'credit_note'
                    ? -1 * (float) $doc->vat_total
                    : (float) $doc->vat_total;
            });

        $rows = [
            ['vat_status', $user->vat_status ?: ''],
            ['vat_number', $user->vat_number ?: ''],
            ['net_official_invoiced', number_format($net, 2, '.', '')],
            ['document_vat_total_net', number_format($vatOnDocs, 2, '.', '')],
            ['note', 'RFP cash is excluded from official fiscal totals'],
        ];

        return $this->csv(['metric', 'value'], $rows);
    }

    private function readme(string $name, int $year): string
    {
        return "PractisBase Accountant Pack\n"
            . "Practitioner: {$name}\n"
            . "Year: {$year}\n\n"
            . "This ZIP contains full ledger detail for your accountant.\n"
            . "- documents.csv: invoices, RFPs, credit notes (fiscal_weight column)\n"
            . "- payments.csv: cash received (RFP payments marked non-fiscal)\n"
            . "- clients.csv: billing counterparties (includes archived)\n"
            . "- expenses.csv: expense ledger for the year\n"
            . "- tax-payments.csv: PT / SSC / VAT payments logged in PractisBase\n"
            . "- vat-summary.csv: VAT profile and totals\n\n"
            . "PractisBase is a tool, not certified accounting software.\n";
    }
}

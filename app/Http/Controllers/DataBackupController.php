<?php

namespace App\Http\Controllers;

use App\Models\CapitalAsset;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\TaxPayment;
use App\Models\UserRegimeSegment;
use App\Support\SimpleZipWriter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;
use ZipArchive;

class DataBackupController extends Controller
{
    public function form()
    {
        $user = Auth::user();

        if ($user->canAccessCompanyBooks()) {
            return redirect('/company');
        }

        return view('exports.data-backup', [
            'user' => $user,
            'backupOverdue' => $user->isDataBackupOverdue(),
            'lastBackupAt' => $user->last_data_backup_at,
        ]);
    }

    public function download()
    {
        $user = Auth::user();

        if ($user->canAccessCompanyBooks()) {
            return redirect('/company');
        }

        $filename = 'practisbase-my-data-' . now()->format('Y-m-d-His') . '-u' . $user->id . '.zip';

        try {
            $files = [
                'manifest.json' => json_encode([
                    'format' => 'practisbase-user-data-backup-v1',
                    'exported_at' => now()->toIso8601String(),
                    'user_id' => $user->id,
                    'note' => 'This archive contains only your PractisBase account data (clients, ledger, expenses, tax payments, profile). Clinical vault data is not included — use the medical vault backup for that.',
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'profile.json' => json_encode($this->profilePayload($user), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'clients.csv' => $this->clientsCsv($user->id),
                'documents.csv' => $this->documentsCsv($user->id),
                'payments.csv' => $this->paymentsCsv($user->id),
                'expenses.csv' => $this->expensesCsv($user->id),
                'tax-payments.csv' => $this->taxPaymentsCsv($user->id),
                'capital-assets.csv' => $this->capitalAssetsCsv($user->id),
                'regime-segments.csv' => $this->regimeSegmentsCsv($user->id),
                'fiscal-years.csv' => $this->fiscalYearsCsv($user->id),
                'README.txt' => $this->readme($user->name),
            ];

            $tmp = tempnam(sys_get_temp_dir(), 'pb_backup_');
            if ($tmp === false) {
                return back()->withErrors(['backup' => 'Could not create a temporary backup file.']);
            }

            if (class_exists(ZipArchive::class)) {
                $zip = new ZipArchive;
                if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
                    @unlink($tmp);

                    return back()->withErrors(['backup' => 'Could not create backup archive.']);
                }
                foreach ($files as $name => $contents) {
                    $zip->addFromString($name, $contents);
                }
                $zip->close();
            } else {
                $zip = new SimpleZipWriter;
                foreach ($files as $name => $contents) {
                    $zip->addFile($name, $contents);
                }
                $zip->writeTo($tmp);
            }

            $user->forceFill(['last_data_backup_at' => now()])->save();

            return response()->download($tmp, $filename, [
                'Content-Type' => 'application/zip',
            ])->deleteFileAfterSend(true);
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors([
                'backup' => 'Data backup failed. Please try again or contact support if it persists.',
            ]);
        }
    }

    private function profilePayload($user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'profession' => $user->profession,
            'warrant_type' => $user->warrant_type,
            'warrant_number' => $user->warrant_number,
            'postnominals' => $user->postnominals,
            'tier' => $user->tier,
            'employment_type' => $user->employment_type,
            'date_of_birth' => optional($user->date_of_birth)->format('Y-m-d'),
            'vat_status' => $user->vat_status,
            'vat_number' => $user->vat_number,
            'tax_computation' => $user->tax_computation,
            'primary_salary' => $user->primary_salary,
            'max_ssc_paid' => (bool) $user->max_ssc_paid,
            'estimated_expenses' => $user->estimated_expenses,
            'estimated_expenses_by_year' => $user->estimated_expenses_by_year,
            'car_business_use_percent' => $user->car_business_use_percent,
            'home_office_percent' => $user->home_office_percent,
            'payment_instructions' => $user->payment_instructions,
            'payment_methods' => $user->payment_methods,
            'clinic_phone' => $user->clinic_phone,
            'clinic_address' => $user->clinic_address,
            'referral_code' => $user->referral_code,
            'clients_created_count' => $user->clients_created_count,
            'trial_ends_at' => optional($user->trial_ends_at)->toIso8601String(),
        ];
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

    private function clientsCsv(int $userId): string
    {
        $clients = Client::withTrashed()
            ->where('user_id', $userId)
            ->orderBy('id')
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
                optional($client->created_at)->toIso8601String(),
            ];
        }

        return $this->csv([
            'client_id', 'name', 'type', 'email', 'phone', 'billing_address',
            'vat_number', 'registration_number', 'id_card_number', 'contact_person', 'status', 'created_at',
        ], $rows);
    }

    private function documentsCsv(int $userId): string
    {
        $docs = Invoice::where('user_id', $userId)
            ->with('client')
            ->orderBy('issue_date')
            ->orderBy('id')
            ->get();

        $rows = [];
        foreach ($docs as $doc) {
            $fiscal = $doc->type === 'rfp' ? 'non-fiscal (RFP)' : ($doc->type === 'credit_note' ? 'fiscal credit' : 'fiscal invoice');
            $rows[] = [
                $doc->id,
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
                $doc->notes,
            ];
        }

        return $this->csv([
            'document_id', 'document_number', 'type', 'fiscal_weight', 'issue_date', 'due_date',
            'client_name', 'client_id', 'subtotal', 'vat_total', 'total',
            'amount_paid', 'status', 'parent_document_id', 'notes',
        ], $rows);
    }

    private function paymentsCsv(int $userId): string
    {
        $payments = Payment::where('user_id', $userId)
            ->with('invoice')
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get();

        $rows = [];
        foreach ($payments as $payment) {
            $invoice = $payment->invoice;
            $rows[] = [
                $payment->id,
                optional($payment->payment_date)->format('Y-m-d'),
                number_format((float) $payment->amount, 2, '.', ''),
                $invoice->invoice_number ?? '',
                $invoice->type ?? '',
                ($invoice && $invoice->type === 'rfp') ? 'non-fiscal' : 'fiscal',
                $payment->is_transfer ? 'internal_transfer' : 'client_cash',
                $invoice->client_id ?? '',
            ];
        }

        return $this->csv([
            'payment_id', 'payment_date', 'amount', 'document_number', 'document_type', 'fiscal_weight', 'cash_kind', 'client_id',
        ], $rows);
    }

    private function expensesCsv(int $userId): string
    {
        $expenses = Expense::where('user_id', $userId)
            ->orderBy('expense_date')
            ->orderBy('id')
            ->get();

        $rows = [];
        foreach ($expenses as $expense) {
            $rows[] = [
                $expense->id,
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
            'expense_id', 'expense_date', 'category', 'description', 'amount_ex_vat', 'vat_amount', 'total', 'has_receipt',
        ], $rows);
    }

    private function taxPaymentsCsv(int $userId): string
    {
        $payments = TaxPayment::where('user_id', $userId)
            ->orderBy('year')
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get();

        $rows = [];
        foreach ($payments as $payment) {
            $rows[] = [
                $payment->id,
                $payment->year,
                $payment->payment_type,
                optional($payment->payment_date)->format('Y-m-d'),
                number_format((float) $payment->amount, 2, '.', ''),
            ];
        }

        return $this->csv(['tax_payment_id', 'year', 'payment_type', 'payment_date', 'amount'], $rows);
    }

    private function capitalAssetsCsv(int $userId): string
    {
        $assets = CapitalAsset::where('user_id', $userId)
            ->orderBy('purchase_date')
            ->orderBy('id')
            ->get();

        $rows = [];
        foreach ($assets as $asset) {
            $rows[] = [
                $asset->id,
                $asset->expense_id,
                $asset->asset_class,
                $asset->description,
                optional($asset->purchase_date)->format('Y-m-d'),
                number_format((float) $asset->cost_basis, 2, '.', ''),
                number_format((float) ($asset->cost_ex_vat ?? 0), 2, '.', ''),
                number_format((float) ($asset->vat_amount ?? 0), 2, '.', ''),
                number_format((float) ($asset->business_use_percent ?? 100), 2, '.', ''),
                number_format((float) ($asset->annual_rate ?? 0), 4, '.', ''),
            ];
        }

        return $this->csv([
            'asset_id', 'expense_id', 'asset_class', 'description', 'purchase_date',
            'cost_basis', 'cost_ex_vat', 'vat_amount', 'business_use_percent', 'annual_rate',
        ], $rows);
    }

    private function regimeSegmentsCsv(int $userId): string
    {
        $segments = UserRegimeSegment::where('user_id', $userId)
            ->orderBy('effective_from')
            ->orderBy('id')
            ->get();

        $rows = [];
        foreach ($segments as $segment) {
            $rows[] = [
                $segment->id,
                optional($segment->effective_from)->format('Y-m-d'),
                $segment->employment_type ?? '',
                $segment->vat_status ?? '',
                $segment->tax_computation ?? '',
                $segment->max_ssc_paid ? 'yes' : 'no',
                number_format((float) ($segment->primary_salary ?? 0), 2, '.', ''),
            ];
        }

        return $this->csv([
            'segment_id', 'effective_from', 'employment_type', 'vat_status', 'tax_computation', 'max_ssc_paid', 'primary_salary',
        ], $rows);
    }

    private function fiscalYearsCsv(int $userId): string
    {
        $years = DB::table('fiscal_years')
            ->where('user_id', $userId)
            ->orderBy('year')
            ->get();

        $rows = [];
        foreach ($years as $year) {
            $rows[] = [
                $year->id ?? '',
                $year->year ?? '',
                $year->closed_at ?? '',
                ! empty($year->snapshot_json) ? 'yes' : 'no',
            ];
        }

        return $this->csv(['fiscal_year_id', 'year', 'closed_at', 'has_snapshot'], $rows);
    }

    private function readme(string $name): string
    {
        return "PractisBase personal data backup\n"
            . "================================\n\n"
            . "Owner: {$name}\n"
            . 'Exported: ' . now()->toIso8601String() . "\n\n"
            . "This ZIP contains only your own account data.\n"
            . "It does not include other users, Cerulean Labs company books, or decrypted clinical vault journals.\n"
            . "Doctors: download a separate medical vault backup from Patients → Backup (requires your recovery code).\n\n"
            . "Store this file somewhere safe offline. Re-download weekly from Settings → Data backup.\n";
    }
}

<?php

namespace App\Http\Controllers\Pro\Medical;

use App\Http\Controllers\Controller;
use App\Models\ClinicalEntry;
use App\Models\Patient;
use App\Support\DocxDocument;
use App\Support\MedicalVaultCrypto;
use App\Support\PatientReport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PatientReportController extends Controller
{
    public function download(Patient $patient)
    {
        $user = Auth::user();
        if ($patient->user_id !== $user->id) {
            abort(403);
        }

        $key = MedicalVaultCrypto::keyFromSession(session('medical_vault_key'));
        if (! $key) {
            return redirect('/pro/medical/vault/unlock');
        }

        $payload = MedicalVaultCrypto::decrypt($patient->payload_ciphertext, $patient->payload_nonce, $key);

        $history = [];
        $entries = ClinicalEntry::where('user_id', $user->id)
            ->where('patient_id', $patient->id)
            ->whereNull('archived_at')
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        foreach ($entries as $entry) {
            try {
                $data = MedicalVaultCrypto::decrypt($entry->payload_ciphertext, $entry->payload_nonce, $key);
            } catch (\Throwable) {
                $data = ['title' => '', 'body' => ''];
            }

            $history[] = [
                'date' => $entry->entry_date?->format('d M Y') ?? '',
                'type_label' => ClinicalEntry::TYPES[$entry->entry_type] ?? $entry->entry_type,
                'body' => $entry->entry_type === 'prescription' ? '' : (string) ($data['body'] ?? ''),
                'medicines' => $entry->entry_type === 'prescription'
                    ? ClinicalEntry::medicinesFromPayload($data)
                    : [],
                'referred_to' => $data['referred_to'] ?? null,
                'certificate_kind_label' => $entry->entry_type === 'certificate'
                    ? ClinicalEntry::certificateKindLabel($data['certificate_kind'] ?? null)
                    : null,
                'issue_code' => $entry->issue_code,
                'archived' => false,
            ];
        }

        $dob = trim((string) ($payload['date_of_birth'] ?? ''));
        $dobFormatted = $dob;
        if ($dob !== '') {
            try {
                $dobFormatted = \Illuminate\Support\Carbon::parse($dob)->format('d M Y');
            } catch (\Throwable) {
                $dobFormatted = $dob;
            }
        }

        $registration = trim((string) ($user->warrant_number ?? ''));
        $blocks = PatientReport::blocks(
            [
                'name' => (string) $user->name,
                'postnominals' => (string) ($user->postnominalsLine() ?? ''),
                'profession' => (string) ($user->profession ?? ''),
                'registration' => $registration,
                'email' => (string) ($user->email ?? ''),
                'phone' => (string) ($user->clinic_phone ?? ''),
                'address' => (string) ($user->clinic_address ?? ''),
            ],
            [
                'display_name' => (string) ($payload['display_name'] ?? 'Patient'),
                'date_of_birth' => $dobFormatted,
                'age' => (string) ($payload['age'] ?? ''),
                'id_card' => (string) ($payload['id_card'] ?? ''),
                'tel' => (string) ($payload['tel'] ?? ''),
                'email' => (string) ($payload['email'] ?? ''),
                'address' => (string) ($payload['address'] ?? ''),
            ],
            $history
        );

        $bytes = DocxDocument::render($blocks);
        $slug = Str::slug((string) ($payload['display_name'] ?? 'patient')) ?: 'patient';
        $filename = 'patient-report-'.$slug.'-'.now()->format('Y-m-d').'.docx';

        return response($bytes, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, private',
        ]);
    }
}

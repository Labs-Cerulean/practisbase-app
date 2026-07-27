<?php

namespace App\Http\Controllers\Pro\Medical;

use App\Http\Controllers\Controller;
use App\Models\ClinicalEntry;
use App\Models\Patient;
use App\Support\MedicalVaultCrypto;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class ClinicalEntryPdfController extends Controller
{
    public function download(Patient $patient, ClinicalEntry $entry)
    {
        $user = Auth::user();
        if ($patient->user_id !== $user->id || $entry->user_id !== $user->id || $entry->patient_id !== $patient->id) {
            abort(403);
        }

        if (! $entry->isStampable()) {
            abort(400, 'Only stampable clinical documents can be exported as PDF.');
        }

        if (! $entry->isIssued()) {
            return redirect('/pro/medical/patients/' . $patient->id)
                ->withErrors(['entry' => 'Stamp & issue the document before downloading the official PDF.']);
        }

        $key = MedicalVaultCrypto::keyFromSession(session('medical_vault_key'));
        if (! $key) {
            return redirect('/pro/medical/vault/unlock');
        }

        $patientPayload = MedicalVaultCrypto::decrypt($patient->payload_ciphertext, $patient->payload_nonce, $key);
        $entryPayload = MedicalVaultCrypto::decrypt($entry->payload_ciphertext, $entry->payload_nonce, $key);

        $documentTitle = match ($entry->entry_type) {
            'prescription' => 'Digital Prescription',
            'referral' => 'Referral Letter',
            'certificate' => ClinicalEntry::certificateKindLabel($entryPayload['certificate_kind'] ?? null),
            default => $entry->typeLabel(),
        };

        $view = match ($entry->entry_type) {
            'prescription' => 'pro.medical.pdf.prescription',
            'referral' => 'pro.medical.pdf.referral',
            'certificate' => 'pro.medical.pdf.certificate',
            default => 'pro.medical.pdf.prescription',
        };

        $pdf = Pdf::loadView($view, [
            'user' => $user,
            'patient' => $patient,
            'patientPayload' => $patientPayload,
            'entry' => $entry,
            'entryPayload' => $entryPayload,
            'documentTitle' => $documentTitle,
        ]);
        $pdf->setPaper('a4', 'portrait');

        $safeCode = preg_replace('/[^A-Za-z0-9\-]/', '', (string) $entry->issue_code);
        $safeRef = preg_replace('/[^A-Za-z0-9\-]/', '', $patient->public_ref) ?: 'patient';
        $filename = strtolower($entry->entry_type)
            . '_' . ($safeCode ?: $safeRef)
            . '_' . $entry->issued_at->format('Y-m-d')
            . '.pdf';

        return $pdf->download($filename);
    }
}

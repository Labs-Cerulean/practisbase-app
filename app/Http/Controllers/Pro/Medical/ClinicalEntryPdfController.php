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

        if (! in_array($entry->entry_type, ['prescription', 'referral'], true)) {
            abort(400, 'Only prescriptions and referral letters can be exported as PDF.');
        }

        $key = MedicalVaultCrypto::keyFromSession(session('medical_vault_key'));
        if (! $key) {
            return redirect('/pro/medical/vault/unlock');
        }

        $patientPayload = MedicalVaultCrypto::decrypt($patient->payload_ciphertext, $patient->payload_nonce, $key);
        $entryPayload = MedicalVaultCrypto::decrypt($entry->payload_ciphertext, $entry->payload_nonce, $key);

        $documentTitle = $entry->entry_type === 'prescription'
            ? 'Digital Prescription'
            : 'Referral Letter';

        $pdf = Pdf::loadView('pro.medical.clinical-pdf', [
            'user' => $user,
            'patient' => $patient,
            'patientPayload' => $patientPayload,
            'entry' => $entry,
            'entryPayload' => $entryPayload,
            'documentTitle' => $documentTitle,
        ]);
        $pdf->setPaper('a4', 'portrait');

        $safeRef = preg_replace('/[^A-Za-z0-9\-]/', '', $patient->public_ref) ?: 'patient';
        $filename = strtolower($entry->entry_type) . '_' . $safeRef . '_' . $entry->entry_date->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }
}

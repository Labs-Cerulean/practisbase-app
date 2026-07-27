<?php

namespace App\Http\Controllers\Pro\Medical;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\ClinicalEntry;
use App\Models\MedicalVault;
use App\Models\Patient;
use App\Support\IssueCode;
use App\Support\MedicalVaultCrypto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StampableLedgerController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $key = MedicalVaultCrypto::keyFromSession(session('medical_vault_key'));
        $vault = MedicalVault::activeForUser($user->id);

        if (! $vault || ! $key) {
            return redirect('/pro/medical/vault/unlock');
        }

        $patients = Patient::where('user_id', $user->id)
            ->where('vault_id', $vault->id)
            ->get()
            ->keyBy('id');

        $patientNames = [];
        foreach ($patients as $patient) {
            try {
                $payload = MedicalVaultCrypto::decrypt($patient->payload_ciphertext, $patient->payload_nonce, $key);
                $patientNames[$patient->id] = $payload['display_name'] ?? 'Patient';
            } catch (\Throwable) {
                $patientNames[$patient->id] = '[Unable to decrypt]';
            }
        }

        $clinicalRows = ClinicalEntry::where('user_id', $user->id)
            ->where('vault_id', $vault->id)
            ->whereIn('entry_type', ClinicalEntry::STAMPABLE_TYPES)
            ->orderByDesc('issued_at')
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->get()
            ->map(function (ClinicalEntry $entry) use ($key, $patients, $patientNames) {
                try {
                    $data = MedicalVaultCrypto::decrypt($entry->payload_ciphertext, $entry->payload_nonce, $key);
                } catch (\Throwable) {
                    $data = ['title' => '[Unable to decrypt]', 'body' => ''];
                }

                $patient = $patients->get($entry->patient_id);

                return [
                    'source' => 'clinical',
                    'id' => $entry->id,
                    'patient_id' => $entry->patient_id,
                    'patient_name' => $patientNames[$entry->patient_id] ?? 'Patient',
                    'patient_ref' => $patient?->public_ref ?? '',
                    'title' => $data['title'] ?? 'Entry',
                    'body' => $data['body'] ?? '',
                    'entry_type' => $entry->entry_type,
                    'type_label' => $entry->entry_type === 'certificate'
                        ? ClinicalEntry::certificateKindLabel($data['certificate_kind'] ?? null)
                        : $entry->typeLabel(),
                    'meta_line' => match ($entry->entry_type) {
                        'certificate' => trim(implode(' · ', array_filter([
                            $data['subject_name'] ?? null,
                            ! empty($data['expires_on']) ? 'expires ' . $data['expires_on'] : null,
                        ]))),
                        'referral' => $data['referred_to'] ?? '',
                        default => '',
                    },
                    'entry_date' => $entry->entry_date,
                    'issued_at' => $entry->issued_at,
                    'issue_code' => $entry->issue_code,
                    'is_issued' => $entry->isIssued(),
                    'is_editable' => $entry->isEditable(),
                    'status' => $entry->isIssued() ? 'issued' : 'draft',
                    'legacy_certificate_id' => null,
                    'sort_at' => $entry->issued_at ?? $entry->entry_date,
                ];
            });

        $legacyRows = Certificate::where('user_id', $user->id)
            ->orderByDesc('stamped_at')
            ->orderByDesc('issued_on')
            ->orderByDesc('id')
            ->get()
            ->map(function (Certificate $cert) {
                return [
                    'source' => 'legacy',
                    'id' => 'legacy-' . $cert->id,
                    'patient_id' => null,
                    'patient_name' => $cert->subject_name ?: '—',
                    'patient_ref' => '',
                    'title' => $cert->title,
                    'body' => $cert->notes ?? '',
                    'entry_type' => 'legacy_certificate',
                    'type_label' => 'Legacy · ' . (Certificate::KINDS[$cert->kind] ?? $cert->kind),
                    'meta_line' => $cert->subject_name ?: '',
                    'entry_date' => $cert->issued_on,
                    'issued_at' => $cert->stamped_at,
                    'issue_code' => $cert->issue_code,
                    'is_issued' => $cert->isStamped(),
                    'is_editable' => false,
                    'status' => $cert->isStamped() ? 'issued' : 'draft',
                    'legacy_certificate_id' => $cert->id,
                    'sort_at' => $cert->stamped_at ?? $cert->issued_on,
                ];
            });

        $rows = $clinicalRows->concat($legacyRows)
            ->sortByDesc(fn ($row) => optional($row['sort_at'])->timestamp ?? 0)
            ->values();

        $types = array_intersect_key(ClinicalEntry::TYPES, array_flip(ClinicalEntry::STAMPABLE_TYPES));
        if ($legacyRows->isNotEmpty()) {
            $types['legacy_certificate'] = 'Legacy shared certificate';
        }

        return view('pro.medical.stampables-index', [
            'rows' => $rows,
            'types' => $types,
            'hasLegacy' => $legacyRows->isNotEmpty(),
        ]);
    }

    public function lookupIssueCode(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'issue_code' => 'required|string|max:32',
        ]);

        $code = IssueCode::normalize($validated['issue_code']);

        $entry = ClinicalEntry::where('user_id', $user->id)
            ->where('issue_code', $code)
            ->first();

        if ($entry) {
            return redirect('/pro/medical/stampables')
                ->with('success', 'Authenticity match: ' . $entry->issue_code . ' · ' . $entry->typeLabel() . ' · issued ' . optional($entry->issued_at)->format('d M Y H:i') . '.')
                ->with('highlight_entry_id', $entry->id)
                ->with('highlight_patient_id', $entry->patient_id);
        }

        $cert = Certificate::where('user_id', $user->id)
            ->where('issue_code', $code)
            ->first();

        if ($cert) {
            return redirect('/pro/medical/stampables')
                ->with('success', 'Authenticity match (legacy register): ' . $cert->issue_code . ' · ' . $cert->title . ' · stamped ' . optional($cert->stamped_at)->format('d M Y H:i') . '.')
                ->with('highlight_entry_id', 'legacy-' . $cert->id);
        }

        return redirect('/pro/medical/stampables')
            ->withErrors(['issue_code' => 'No stamped document in your register matches ' . ($code ?: 'that code') . '. If this code was presented as a reprint, treat it as unverified.']);
    }
}

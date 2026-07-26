<?php

namespace App\Http\Controllers\Pro\Medical;

use App\Http\Controllers\Controller;
use App\Models\ClinicalAttachment;
use App\Models\ClinicalEntry;
use App\Models\MedicalVault;
use App\Models\Patient;
use App\Support\MedicalVaultCrypto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PatientController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $key = MedicalVaultCrypto::keyFromSession(session('medical_vault_key'));
        $patients = Patient::where('user_id', $user->id)->orderByDesc('id')->get();

        $rows = $patients->map(function (Patient $patient) use ($key) {
            $payload = [];
            try {
                $payload = MedicalVaultCrypto::decrypt($patient->payload_ciphertext, $patient->payload_nonce, $key);
            } catch (\Throwable) {
                $payload = ['display_name' => '[Unable to decrypt]'];
            }

            return [
                'model' => $patient,
                'display_name' => $payload['display_name'] ?? 'Patient',
                'public_ref' => $patient->public_ref,
            ];
        });

        return view('pro.medical.patients-index', [
            'rows' => $rows,
            'user' => $user,
        ]);
    }

    public function create()
    {
        return view('pro.medical.patients-create');
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $vault = MedicalVault::activeForUser($user->id);
        $key = MedicalVaultCrypto::keyFromSession(session('medical_vault_key'));

        if (! $vault || ! $key) {
            return redirect('/pro/medical/vault/unlock');
        }

        $validated = $request->validate([
            'display_name' => 'required|string|max:255',
            'date_of_birth' => 'nullable|date|before_or_equal:today',
            'notes' => 'nullable|string|max:2000',
        ]);

        $encrypted = MedicalVaultCrypto::encrypt([
            'display_name' => $validated['display_name'],
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ], $key);

        $patient = Patient::create([
            'user_id' => $user->id,
            'vault_id' => $vault->id,
            'public_ref' => 'PAT-' . strtoupper(Str::random(8)),
            'billing_client_id' => null,
            'payload_ciphertext' => $encrypted['ciphertext'],
            'payload_nonce' => $encrypted['nonce'],
        ]);

        return redirect('/pro/medical/patients/' . $patient->id)
            ->with('success', 'Patient record created in the encrypted vault.');
    }

    public function show(Patient $patient)
    {
        $user = Auth::user();
        if ($patient->user_id !== $user->id) {
            abort(403);
        }

        $key = MedicalVaultCrypto::keyFromSession(session('medical_vault_key'));
        $payload = MedicalVaultCrypto::decrypt($patient->payload_ciphertext, $patient->payload_nonce, $key);

        $attachmentsByEntry = ClinicalAttachment::where('user_id', $user->id)
            ->where('patient_id', $patient->id)
            ->orderBy('id')
            ->get()
            ->groupBy('clinical_entry_id');

        $entries = ClinicalEntry::where('user_id', $user->id)
            ->where('patient_id', $patient->id)
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->get()
            ->map(function (ClinicalEntry $entry) use ($key, $attachmentsByEntry) {
                try {
                    $data = MedicalVaultCrypto::decrypt($entry->payload_ciphertext, $entry->payload_nonce, $key);
                } catch (\Throwable) {
                    $data = ['title' => '[Unable to decrypt]', 'body' => ''];
                }

                $attachments = ($attachmentsByEntry->get($entry->id) ?? collect())->map(function (ClinicalAttachment $attachment) use ($key) {
                    try {
                        $meta = MedicalVaultCrypto::decrypt($attachment->meta_ciphertext, $attachment->meta_nonce, $key);
                    } catch (\Throwable) {
                        $meta = ['original_name' => '[Unable to decrypt]', 'mime' => 'unknown'];
                    }

                    return [
                        'id' => $attachment->id,
                        'name' => $meta['original_name'] ?? 'Attachment',
                        'mime' => $meta['mime'] ?? 'unknown',
                        'byte_size' => $attachment->byte_size,
                    ];
                });

                return [
                    'model' => $entry,
                    'title' => $data['title'] ?? 'Entry',
                    'body' => $data['body'] ?? '',
                    'type_label' => ClinicalEntry::TYPES[$entry->entry_type] ?? $entry->entry_type,
                    'attachments' => $attachments,
                ];
            });

        return view('pro.medical.patients-show', compact('patient', 'payload', 'entries'));
    }
}

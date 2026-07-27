<?php

namespace App\Http\Controllers\Pro\Medical;

use App\Http\Controllers\Controller;
use App\Models\ClinicalAttachment;
use App\Models\ClinicalEntry;
use App\Models\MedicalVault;
use App\Models\Patient;
use App\Support\IssueCode;
use App\Support\MedicalVaultCrypto;
use App\Support\TenantStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ClinicalEntryController extends Controller
{
    public function create(Patient $patient, Request $request)
    {
        $user = Auth::user();
        if ($patient->user_id !== $user->id) {
            abort(403);
        }

        $key = MedicalVaultCrypto::keyFromSession(session('medical_vault_key'));
        if (! $key) {
            return redirect('/pro/medical/vault/unlock');
        }

        $patientPayload = MedicalVaultCrypto::decrypt($patient->payload_ciphertext, $patient->payload_nonce, $key);
        $defaultType = $request->query('type');
        if (! is_string($defaultType) || ! array_key_exists($defaultType, ClinicalEntry::TYPES)) {
            $defaultType = 'journal';
        }

        return view('pro.medical.entries-create', [
            'patient' => $patient,
            'patientPayload' => $patientPayload,
            'types' => ClinicalEntry::TYPES,
            'certificateKinds' => ClinicalEntry::CERTIFICATE_KINDS,
            'defaultType' => old('entry_type', $defaultType),
        ]);
    }

    public function store(Request $request, Patient $patient)
    {
        $user = Auth::user();
        if ($patient->user_id !== $user->id) {
            abort(403);
        }

        $vault = MedicalVault::activeForUser($user->id);
        $key = MedicalVaultCrypto::keyFromSession(session('medical_vault_key'));

        if (! $vault || ! $key) {
            return redirect('/pro/medical/vault/unlock');
        }

        $validated = $this->validateEntryPayload($request);
        $payload = $this->buildEncryptedPayload($validated);

        $encrypted = MedicalVaultCrypto::encrypt($payload, $key);

        $entry = ClinicalEntry::create([
            'user_id' => $user->id,
            'vault_id' => $vault->id,
            'patient_id' => $patient->id,
            'entry_type' => $validated['entry_type'],
            'entry_date' => $validated['entry_date'],
            'payload_ciphertext' => $encrypted['ciphertext'],
            'payload_nonce' => $encrypted['nonce'],
            'issued_at' => null,
            'issued_by_user_id' => null,
            'issue_code' => null,
        ]);

        if ($request->hasFile('attachment')) {
            $this->storeAttachmentFile($request, $user->id, $vault->id, $patient->id, $entry->id, $key);
        }

        $msg = in_array($validated['entry_type'], ClinicalEntry::STAMPABLE_TYPES, true)
            ? 'Draft ' . (ClinicalEntry::TYPES[$validated['entry_type']] ?? 'document') . ' saved. Edit until Stamp & issue — then it locks and gets an issue code on the PDF.'
            : 'Journal note saved encrypted in your vault.';

        return redirect('/pro/medical/patients/' . $patient->id)->with('success', $msg);
    }

    public function edit(Patient $patient, ClinicalEntry $entry)
    {
        $user = Auth::user();
        $this->assertOwned($user->id, $patient, $entry);

        if (! $entry->isEditable()) {
            return redirect('/pro/medical/patients/' . $patient->id)
                ->withErrors(['entry' => 'This document was stamped and issued. It can no longer be edited.']);
        }

        $key = MedicalVaultCrypto::keyFromSession(session('medical_vault_key'));
        if (! $key) {
            return redirect('/pro/medical/vault/unlock');
        }

        $payload = MedicalVaultCrypto::decrypt($entry->payload_ciphertext, $entry->payload_nonce, $key);
        $patientPayload = MedicalVaultCrypto::decrypt($patient->payload_ciphertext, $patient->payload_nonce, $key);

        return view('pro.medical.entries-edit', [
            'patient' => $patient,
            'patientPayload' => $patientPayload,
            'entry' => $entry,
            'payload' => $payload,
            'types' => ClinicalEntry::TYPES,
            'certificateKinds' => ClinicalEntry::CERTIFICATE_KINDS,
        ]);
    }

    public function update(Request $request, Patient $patient, ClinicalEntry $entry)
    {
        $user = Auth::user();
        $this->assertOwned($user->id, $patient, $entry);

        if (! $entry->isEditable()) {
            return redirect('/pro/medical/patients/' . $patient->id)
                ->withErrors(['entry' => 'This document was stamped and issued. It can no longer be edited.']);
        }

        $key = MedicalVaultCrypto::keyFromSession(session('medical_vault_key'));
        if (! $key) {
            return redirect('/pro/medical/vault/unlock');
        }

        $request->merge(['entry_type' => $entry->entry_type]);
        $validated = $this->validateEntryPayload($request, updating: true);
        $payload = $this->buildEncryptedPayload($validated);

        $encrypted = MedicalVaultCrypto::encrypt($payload, $key);

        $entry->entry_date = $validated['entry_date'];
        $entry->payload_ciphertext = $encrypted['ciphertext'];
        $entry->payload_nonce = $encrypted['nonce'];
        $entry->save();

        if ($request->hasFile('attachment')) {
            $vault = MedicalVault::activeForUser($user->id);
            if ($vault) {
                $this->storeAttachmentFile($request, $user->id, $vault->id, $patient->id, $entry->id, $key);
            }
        }

        return redirect('/pro/medical/patients/' . $patient->id)
            ->with('success', 'Entry updated.');
    }

    public function issue(Patient $patient, ClinicalEntry $entry)
    {
        $user = Auth::user();
        $this->assertOwned($user->id, $patient, $entry);

        if (! $entry->isStampable()) {
            return back()->withErrors(['entry' => 'Journal notes are not stamped.']);
        }

        if ($entry->isIssued()) {
            return back()->withErrors(['entry' => 'Already stamped and issued.']);
        }

        $entry->issued_at = now();
        $entry->issued_by_user_id = $user->id;
        $entry->issue_code = IssueCode::allocateForClinicalEntry($entry->entry_type);
        $entry->save();

        return redirect('/pro/medical/patients/' . $patient->id)
            ->with('success', 'Document stamped and issued as ' . $entry->issue_code . '. Code and issue date are printed on the PDF. It is now locked.');
    }

    private function validateEntryPayload(Request $request, bool $updating = false): array
    {
        $type = $request->input('entry_type');

        $rules = [
            'entry_type' => 'required|in:' . implode(',', array_keys(ClinicalEntry::TYPES)),
            'entry_date' => 'required|date|before_or_equal:today',
            'title' => 'nullable|string|max:255',
            'body' => 'nullable|string|max:20000',
            'attachment' => 'nullable|file|max:' . ClinicalAttachment::MAX_KILOBYTES . '|mimetypes:' . implode(',', ClinicalAttachment::ALLOWED_MIMES),
        ];

        if ($type !== 'prescription') {
            $rules['title'] = 'required|string|max:255';
            $rules['body'] = 'required|string|max:20000';
        }

        if ($type === 'certificate') {
            $rules['certificate_kind'] = ['required', Rule::in(array_keys(ClinicalEntry::CERTIFICATE_KINDS))];
            $rules['subject_name'] = 'nullable|string|max:255';
            $rules['expires_on'] = 'nullable|date|after_or_equal:entry_date';
        }

        if ($type === 'prescription') {
            $rules['medicines'] = 'required|array|min:1|max:40';
            $rules['medicines.*.name'] = 'nullable|string|max:255';
            $rules['medicines.*.strength'] = 'nullable|string|max:120';
            $rules['medicines.*.dose'] = 'nullable|string|max:255';
            $rules['medicines.*.quantity'] = 'nullable|string|max:120';
            $rules['medicines.*.instructions'] = 'nullable|string|max:2000';
            $rules['body'] = 'nullable|string|max:20000';
            $rules['title'] = 'nullable|string|max:255';
        }

        if ($type === 'referral') {
            $rules['referred_to'] = 'nullable|string|max:255';
        }

        $validated = $request->validate($rules);

        if ($type === 'prescription') {
            $medicines = [];
            foreach ($validated['medicines'] ?? [] as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $name = trim((string) ($row['name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $medicines[] = [
                    'name' => $name,
                    'strength' => trim((string) ($row['strength'] ?? '')),
                    'dose' => trim((string) ($row['dose'] ?? '')),
                    'quantity' => trim((string) ($row['quantity'] ?? '')),
                    'instructions' => trim((string) ($row['instructions'] ?? '')),
                ];
            }

            if ($medicines === []) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'medicines' => 'Add at least one medicine with a name.',
                ]);
            }

            $validated['medicines'] = $medicines;
            $validated['title'] = ClinicalEntry::prescriptionSummaryTitle($medicines, $validated['title'] ?? null);
            $validated['body'] = trim((string) ($validated['body'] ?? ''));
        }

        return $validated;
    }

    private function buildEncryptedPayload(array $validated): array
    {
        $payload = [
            'title' => $validated['title'],
            'body' => $validated['body'] ?? '',
        ];

        if ($validated['entry_type'] === 'certificate') {
            $payload['certificate_kind'] = $validated['certificate_kind'];
            $payload['subject_name'] = $validated['subject_name'] ?? null;
            $payload['expires_on'] = $validated['expires_on'] ?? null;
        }

        if ($validated['entry_type'] === 'referral') {
            $payload['referred_to'] = $validated['referred_to'] ?? null;
        }

        if ($validated['entry_type'] === 'prescription') {
            $payload['medicines'] = $validated['medicines'];
        }

        return $payload;
    }

    private function storeAttachmentFile(Request $request, int $userId, int $vaultId, int $patientId, int $entryId, string $key): void
    {
        $file = $request->file('attachment');
        if (! $file) {
            return;
        }

        $plain = file_get_contents($file->getRealPath());
        if ($plain === false) {
            return;
        }

        $encryptedFile = MedicalVaultCrypto::encryptBytes($plain, $key);
        $meta = MedicalVaultCrypto::encrypt([
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType() ?: 'application/octet-stream',
        ], $key);

        $storageDir = TenantStorage::medicalAttachmentsPath($userId, $vaultId);
        $storagePath = $storageDir . '/' . Str::uuid()->toString() . '.bin';
        TenantStorage::disk()->put($storagePath, $encryptedFile['ciphertext']);

        ClinicalAttachment::create([
            'user_id' => $userId,
            'vault_id' => $vaultId,
            'patient_id' => $patientId,
            'clinical_entry_id' => $entryId,
            'meta_ciphertext' => $meta['ciphertext'],
            'meta_nonce' => $meta['nonce'],
            'file_nonce' => $encryptedFile['nonce'],
            'storage_path' => $storagePath,
            'byte_size' => strlen($encryptedFile['ciphertext']),
            'ciphertext_sha256' => hash('sha256', $encryptedFile['ciphertext']),
        ]);
    }

    private function assertOwned(int $userId, Patient $patient, ClinicalEntry $entry): void
    {
        if ($patient->user_id !== $userId || $entry->user_id !== $userId || $entry->patient_id !== $patient->id) {
            abort(403);
        }
    }
}

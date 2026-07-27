<?php

namespace App\Http\Controllers\Pro\Medical;

use App\Http\Controllers\Controller;
use App\Models\ClinicalEntry;
use App\Models\MedicalVault;
use App\Models\Patient;
use App\Support\MedicalVaultCrypto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClinicalEntryController extends Controller
{
    public function create(Patient $patient)
    {
        $user = Auth::user();
        if ($patient->user_id !== $user->id) {
            abort(403);
        }

        return view('pro.medical.entries-create', [
            'patient' => $patient,
            'types' => ClinicalEntry::TYPES,
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

        $validated = $request->validate([
            'entry_type' => 'required|in:' . implode(',', array_keys(ClinicalEntry::TYPES)),
            'entry_date' => 'required|date|before_or_equal:today',
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:20000',
        ]);

        $encrypted = MedicalVaultCrypto::encrypt([
            'title' => $validated['title'],
            'body' => $validated['body'],
        ], $key);

        ClinicalEntry::create([
            'user_id' => $user->id,
            'vault_id' => $vault->id,
            'patient_id' => $patient->id,
            'entry_type' => $validated['entry_type'],
            'entry_date' => $validated['entry_date'],
            'payload_ciphertext' => $encrypted['ciphertext'],
            'payload_nonce' => $encrypted['nonce'],
            'issued_at' => null,
            'issued_by_user_id' => null,
        ]);

        $msg = in_array($validated['entry_type'], ClinicalEntry::STAMPABLE_TYPES, true)
            ? 'Draft saved. Edit freely until you press Stamp & issue — then it locks.'
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

        return view('pro.medical.entries-edit', [
            'patient' => $patient,
            'entry' => $entry,
            'payload' => $payload,
            'types' => ClinicalEntry::TYPES,
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

        $validated = $request->validate([
            'entry_date' => 'required|date|before_or_equal:today',
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:20000',
        ]);

        $encrypted = MedicalVaultCrypto::encrypt([
            'title' => $validated['title'],
            'body' => $validated['body'],
        ], $key);

        $entry->entry_date = $validated['entry_date'];
        $entry->payload_ciphertext = $encrypted['ciphertext'];
        $entry->payload_nonce = $encrypted['nonce'];
        $entry->save();

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
        $entry->save();

        return redirect('/pro/medical/patients/' . $patient->id)
            ->with('success', 'Document stamped and issued. It is now locked against further edits.');
    }

    private function assertOwned(int $userId, Patient $patient, ClinicalEntry $entry): void
    {
        if ($patient->user_id !== $userId || $entry->user_id !== $userId || $entry->patient_id !== $patient->id) {
            abort(403);
        }
    }
}

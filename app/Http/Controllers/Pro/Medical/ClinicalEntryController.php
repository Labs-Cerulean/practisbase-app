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
        ]);

        return redirect('/pro/medical/patients/' . $patient->id)
            ->with('success', 'Clinical entry saved encrypted in your vault.');
    }
}

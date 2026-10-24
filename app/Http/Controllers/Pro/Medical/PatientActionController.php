<?php

namespace App\Http\Controllers\Pro\Medical;

use App\Http\Controllers\Controller;
use App\Models\ClinicalLabel;
use App\Models\MedicalVault;
use App\Models\Patient;
use App\Models\PatientAction;
use App\Support\ClinicDeskSchema;
use App\Support\MedicalVaultCrypto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PatientActionController extends Controller
{
    public function store(Request $request, Patient $patient)
    {
        $user = Auth::user();
        if ($patient->user_id !== $user->id) {
            abort(403);
        }

        if (! ClinicDeskSchema::ensure()) {
            return back()->withErrors(['action' => 'Action labels are on staging until the clinic tables are applied.']);
        }

        if ($patient->isArchived()) {
            return back()->withErrors(['action' => 'Restore this patient before adding an action.']);
        }

        $vault = MedicalVault::activeForUser($user->id);
        $key = MedicalVaultCrypto::keyFromSession(session('medical_vault_key'));
        if (! $vault || ! $key) {
            return redirect('/pro/medical/vault/unlock');
        }

        $validated = $request->validate([
            'label_name' => 'required|string|max:80',
            'note' => 'nullable|string|max:2000',
        ]);

        $name = trim($validated['label_name']);
        if ($name === '') {
            return back()->withErrors(['label_name' => 'Name the label.'])->withInput();
        }

        $label = ClinicalLabel::where('user_id', $user->id)
            ->whereRaw('lower(name) = ?', [mb_strtolower($name)])
            ->first();

        if (! $label) {
            $label = ClinicalLabel::create([
                'user_id' => $user->id,
                'name' => $name,
            ]);
        }

        $existing = PatientAction::where('user_id', $user->id)
            ->where('patient_id', $patient->id)
            ->where('label_id', $label->id)
            ->whereNull('done_at')
            ->exists();

        if ($existing) {
            return back()->with('success', 'That action is already open on this chart.');
        }

        $note = trim((string) ($validated['note'] ?? ''));
        $encrypted = $note !== '' ? MedicalVaultCrypto::encrypt(['note' => $note], $key) : null;

        PatientAction::create([
            'user_id' => $user->id,
            'vault_id' => $vault->id,
            'patient_id' => $patient->id,
            'label_id' => $label->id,
            'note_ciphertext' => $encrypted['ciphertext'] ?? null,
            'note_nonce' => $encrypted['nonce'] ?? null,
            'done_at' => null,
        ]);

        return back()->with('success', 'Action added.');
    }

    public function done(Patient $patient, PatientAction $action)
    {
        $this->assertOwned($patient, $action);

        if ($action->done_at === null) {
            $action->done_at = now();
            $action->save();
        }

        return back()->with('success', 'Action marked done.');
    }

    public function reopen(Patient $patient, PatientAction $action)
    {
        $this->assertOwned($patient, $action);

        $action->done_at = null;
        $action->save();

        return back()->with('success', 'Action reopened.');
    }

    public function destroy(Patient $patient, PatientAction $action)
    {
        $this->assertOwned($patient, $action);
        $action->delete();

        return back()->with('success', 'Action removed.');
    }

    public function renameLabel(Request $request, ClinicalLabel $label)
    {
        $user = Auth::user();
        if ($label->user_id !== $user->id) {
            abort(403);
        }

        if (! ClinicDeskSchema::ensure()) {
            return back()->withErrors(['label' => 'Action labels are on staging until the clinic tables are applied.']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:80',
        ]);
        $name = trim($validated['name']);
        if ($name === '') {
            return back()->withErrors(['name' => 'Name the label.'])->withInput();
        }

        $clash = ClinicalLabel::where('user_id', $user->id)
            ->where('id', '!=', $label->id)
            ->whereRaw('lower(name) = ?', [mb_strtolower($name)])
            ->exists();

        if ($clash) {
            return back()->withErrors(['name' => 'You already have a label with that name.']);
        }

        $label->name = $name;
        $label->save();

        return back()->with('success', 'Label renamed.');
    }

    public function destroyLabel(ClinicalLabel $label)
    {
        $user = Auth::user();
        if ($label->user_id !== $user->id) {
            abort(403);
        }

        $label->delete();

        return back()->with('success', 'Label removed from every chart.');
    }

    private function assertOwned(Patient $patient, PatientAction $action): void
    {
        $user = Auth::user();
        if ($patient->user_id !== $user->id || $action->user_id !== $user->id || (int) $action->patient_id !== (int) $patient->id) {
            abort(403);
        }
    }
}

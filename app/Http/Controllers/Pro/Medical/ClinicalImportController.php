<?php

namespace App\Http\Controllers\Pro\Medical;

use App\Http\Controllers\Controller;
use App\Models\ClinicalEntry;
use App\Models\MedicalVault;
use App\Models\Patient;
use App\Support\MedicalVaultCrypto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClinicalImportController extends Controller
{
    private const ALLOWED_EMAIL = 'sarah.darmanin2@gmail.com';

    private function assertAllowedImporter(): void
    {
        $email = strtolower(trim((string) (Auth::user()->email ?? '')));
        if ($email !== self::ALLOWED_EMAIL) {
            abort(404);
        }
    }

    public function form()
    {
        $this->assertAllowedImporter();

        return view('pro.medical.import-gynae', [
            'maxBatch' => 5,
        ]);
    }

    /**
     * Accept pre-encrypted patient + entry payloads only.
     * Plaintext clinical content must never be posted here.
     */
    public function commit(Request $request)
    {
        $this->assertAllowedImporter();

        $user = Auth::user();
        $vault = MedicalVault::activeForUser($user->id);
        $key = MedicalVaultCrypto::keyFromSession(session('medical_vault_key'));

        if (! $vault || ! $key) {
            return response()->json(['message' => 'Vault is locked.'], 403);
        }

        $validated = $request->validate([
            'records' => 'required|array|min:1|max:5',
            'records.*.patient.payload_ciphertext' => 'required|string|max:500000',
            'records.*.patient.payload_nonce' => 'required|string|max:128',
            'records.*.entries' => 'required|array|min:1|max:20',
            'records.*.entries.*.entry_type' => 'required|in:' . implode(',', array_keys(ClinicalEntry::TYPES)),
            'records.*.entries.*.entry_date' => 'required|date|before_or_equal:today',
            'records.*.entries.*.payload_ciphertext' => 'required|string|max:500000',
            'records.*.entries.*.payload_nonce' => 'required|string|max:128',
        ]);

        $imported = 0;

        try {
            DB::transaction(function () use ($validated, $user, $vault, $key, &$imported) {
                foreach ($validated['records'] as $record) {
                    // Integrity check only — payload is not persisted in cleartext.
                    MedicalVaultCrypto::decrypt(
                        $record['patient']['payload_ciphertext'],
                        $record['patient']['payload_nonce'],
                        $key
                    );

                    $patient = Patient::create([
                        'user_id' => $user->id,
                        'vault_id' => $vault->id,
                        'public_ref' => 'PAT-' . strtoupper(Str::random(8)),
                        'billing_client_id' => null,
                        'payload_ciphertext' => $record['patient']['payload_ciphertext'],
                        'payload_nonce' => $record['patient']['payload_nonce'],
                    ]);

                    foreach ($record['entries'] as $entry) {
                        MedicalVaultCrypto::decrypt(
                            $entry['payload_ciphertext'],
                            $entry['payload_nonce'],
                            $key
                        );

                        ClinicalEntry::create([
                            'user_id' => $user->id,
                            'vault_id' => $vault->id,
                            'patient_id' => $patient->id,
                            'entry_type' => $entry['entry_type'],
                            'entry_date' => $entry['entry_date'],
                            'payload_ciphertext' => $entry['payload_ciphertext'],
                            'payload_nonce' => $entry['payload_nonce'],
                        ]);
                    }

                    $imported++;
                }
            });
        } catch (\Throwable) {
            return response()->json([
                'message' => 'One or more ciphertext blobs could not be verified with your vault key. Nothing was saved.',
            ], 422);
        }

        return response()->json([
            'imported' => $imported,
            'message' => 'Imported ' . $imported . ' patient(s) as ciphertext only.',
        ]);
    }
}

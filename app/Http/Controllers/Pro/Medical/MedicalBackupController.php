<?php

namespace App\Http\Controllers\Pro\Medical;

use App\Http\Controllers\Controller;
use App\Models\ClinicalAttachment;
use App\Models\ClinicalEntry;
use App\Models\MedicalVault;
use App\Models\Patient;
use App\Support\MedicalVaultCrypto;
use App\Support\SimpleZipWriter;
use App\Support\TenantStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MedicalBackupController extends Controller
{
    public function form()
    {
        return redirect('/exports/backup#medical');
    }

    public function download(Request $request): StreamedResponse|\Illuminate\Http\RedirectResponse
    {
        $user = Auth::user();
        $vault = MedicalVault::activeForUser($user->id);

        if (! $vault) {
            return redirect('/pro/medical/vault/setup');
        }

        $request->validate([
            'recovery_code' => 'required|string|max:100',
        ]);

        if (! MedicalVaultCrypto::matches($request->recovery_code, $vault->recovery_verifier)) {
            return back()->withErrors([
                'recovery_code' => 'That recovery code does not match this vault. No backup was created.',
            ]);
        }

        $key = MedicalVaultCrypto::deriveKey($request->recovery_code);
        $zip = new SimpleZipWriter;

        $patients = Patient::where('user_id', $user->id)
            ->where('vault_id', $vault->id)
            ->orderBy('id')
            ->get();

        $manifest = [
            'format' => 'practisbase-medical-backup-v1',
            'exported_at' => now()->toIso8601String(),
            'vault_id' => $vault->id,
            'user_id' => $user->id,
            'patient_count' => $patients->count(),
            'note' => 'Decrypted offline backup for the practitioner. Store securely. Cerulean Labs cannot recover this for you.',
        ];
        $zip->addFile('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $patientSummaries = [];

        foreach ($patients as $patient) {
            try {
                $patientPayload = MedicalVaultCrypto::decrypt($patient->payload_ciphertext, $patient->payload_nonce, $key);
            } catch (\Throwable) {
                return back()->withErrors([
                    'recovery_code' => 'Unable to decrypt patient ' . $patient->public_ref . '. Backup aborted.',
                ]);
            }

            $entriesOut = [];
            $entries = ClinicalEntry::where('user_id', $user->id)
                ->where('patient_id', $patient->id)
                ->orderBy('id')
                ->get();

            foreach ($entries as $entry) {
                try {
                    $entryPayload = MedicalVaultCrypto::decrypt($entry->payload_ciphertext, $entry->payload_nonce, $key);
                } catch (\Throwable) {
                    return back()->withErrors([
                        'recovery_code' => 'Unable to decrypt a clinical entry for ' . $patient->public_ref . '. Backup aborted.',
                    ]);
                }

                $attachmentMeta = [];
                $attachments = ClinicalAttachment::where('user_id', $user->id)
                    ->where('clinical_entry_id', $entry->id)
                    ->orderBy('id')
                    ->get();

                foreach ($attachments as $attachment) {
                    try {
                        $meta = MedicalVaultCrypto::decrypt($attachment->meta_ciphertext, $attachment->meta_nonce, $key);
                        $cipherBytes = TenantStorage::disk()->get($attachment->storage_path);
                        $plainBytes = MedicalVaultCrypto::decryptBytes($cipherBytes, $attachment->file_nonce, $key);
                    } catch (\Throwable) {
                        return back()->withErrors([
                            'recovery_code' => 'Unable to decrypt an attachment for ' . $patient->public_ref . '. Backup aborted.',
                        ]);
                    }

                    $safeName = preg_replace('/[^A-Za-z0-9._-]+/', '_', $meta['original_name'] ?? ('file-' . $attachment->id)) ?: ('file-' . $attachment->id);
                    $relPath = 'patients/' . $patient->public_ref . '/attachments/' . $attachment->id . '_' . $safeName;
                    $zip->addFile($relPath, $plainBytes);

                    $attachmentMeta[] = [
                        'id' => $attachment->id,
                        'original_name' => $meta['original_name'] ?? null,
                        'mime' => $meta['mime'] ?? null,
                        'backup_path' => $relPath,
                    ];
                }

                $entriesOut[] = [
                    'id' => $entry->id,
                    'entry_type' => $entry->entry_type,
                    'entry_date' => $entry->entry_date?->format('Y-m-d'),
                    'title' => $entryPayload['title'] ?? null,
                    'body' => $entryPayload['body'] ?? null,
                    'attachments' => $attachmentMeta,
                ];
            }

            $patientRecord = [
                'id' => $patient->id,
                'public_ref' => $patient->public_ref,
                'payload' => $patientPayload,
                'entries' => $entriesOut,
            ];

            $zip->addFile(
                'patients/' . $patient->public_ref . '/patient.json',
                json_encode($patientRecord, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );

            $patientSummaries[] = [
                'public_ref' => $patient->public_ref,
                'display_name' => $patientPayload['display_name'] ?? null,
                'entry_count' => count($entriesOut),
            ];
        }

        $zip->addFile('patients_index.json', json_encode($patientSummaries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $vault->forceFill(['last_backup_at' => now()])->save();

        $binary = $zip->finish();
        $filename = 'practisbase-medical-backup-' . now()->format('Y-m-d-His') . '.zip';

        return response()->streamDownload(function () use ($binary) {
            echo $binary;
        }, $filename, [
            'Content-Type' => 'application/zip',
            'Cache-Control' => 'no-store, private',
        ]);
    }
}

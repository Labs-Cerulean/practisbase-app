<?php

namespace App\Http\Controllers\Pro\Medical;

use App\Http\Controllers\Controller;
use App\Models\ClinicalAttachment;
use App\Models\ClinicalEntry;
use App\Models\MedicalVault;
use App\Models\Patient;
use App\Support\MedicalVaultCrypto;
use App\Support\TenantStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClinicalAttachmentController extends Controller
{
    public function store(Request $request, Patient $patient, ClinicalEntry $entry)
    {
        $user = Auth::user();
        if ($patient->user_id !== $user->id || $entry->user_id !== $user->id || $entry->patient_id !== $patient->id) {
            abort(403);
        }

        $vault = MedicalVault::activeForUser($user->id);
        $key = MedicalVaultCrypto::keyFromSession(session('medical_vault_key'));

        if (! $vault || ! $key || (int) $entry->vault_id !== (int) $vault->id) {
            return redirect('/pro/medical/vault/unlock');
        }

        $request->validate([
            'attachment' => 'required|file|max:' . ClinicalAttachment::MAX_KILOBYTES . '|mimetypes:' . implode(',', ClinicalAttachment::ALLOWED_MIMES),
        ]);

        $file = $request->file('attachment');
        $plain = file_get_contents($file->getRealPath());
        if ($plain === false) {
            return back()->withErrors(['attachment' => 'Unable to read the uploaded file.']);
        }

        $encryptedFile = MedicalVaultCrypto::encryptBytes($plain, $key);
        $meta = MedicalVaultCrypto::encrypt([
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType() ?: 'application/octet-stream',
        ], $key);

        $storageDir = TenantStorage::medicalAttachmentsPath($user->id, $vault->id);
        $storagePath = $storageDir . '/' . Str::uuid()->toString() . '.bin';
        TenantStorage::disk()->put($storagePath, $encryptedFile['ciphertext']);

        ClinicalAttachment::create([
            'user_id' => $user->id,
            'vault_id' => $vault->id,
            'patient_id' => $patient->id,
            'clinical_entry_id' => $entry->id,
            'meta_ciphertext' => $meta['ciphertext'],
            'meta_nonce' => $meta['nonce'],
            'file_nonce' => $encryptedFile['nonce'],
            'storage_path' => $storagePath,
            'byte_size' => strlen($encryptedFile['ciphertext']),
            'ciphertext_sha256' => hash('sha256', $encryptedFile['ciphertext']),
        ]);

        return redirect('/pro/medical/patients/' . $patient->id)
            ->with('success', 'Attachment encrypted and stored in your medical vault.');
    }

    public function download(Patient $patient, ClinicalAttachment $attachment): StreamedResponse
    {
        $user = Auth::user();
        if ($patient->user_id !== $user->id || $attachment->user_id !== $user->id || $attachment->patient_id !== $patient->id) {
            abort(403);
        }

        $key = MedicalVaultCrypto::keyFromSession(session('medical_vault_key'));
        if (! $key) {
            abort(403);
        }

        if (! TenantStorage::disk()->exists($attachment->storage_path)) {
            abort(404);
        }

        $cipherBytes = TenantStorage::disk()->get($attachment->storage_path);
        $plain = MedicalVaultCrypto::decryptBytes($cipherBytes, $attachment->file_nonce, $key);
        $meta = MedicalVaultCrypto::decrypt($attachment->meta_ciphertext, $attachment->meta_nonce, $key);

        $filename = $meta['original_name'] ?? ('attachment-' . $attachment->id);
        $mime = $meta['mime'] ?? 'application/octet-stream';

        return response()->streamDownload(function () use ($plain) {
            echo $plain;
        }, $filename, [
            'Content-Type' => $mime,
            'Cache-Control' => 'no-store, private',
        ]);
    }
}

<?php

namespace App\Http\Controllers\Pro\Medical;

use App\Http\Controllers\Controller;
use App\Models\ClinicalLibraryDocument;
use App\Models\MedicalVault;
use App\Support\ClinicDeskSchema;
use App\Support\MedicalVaultCrypto;
use App\Support\TenantStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClinicalLibraryController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $ready = ClinicDeskSchema::ensure();
        $key = MedicalVaultCrypto::keyFromSession(session('medical_vault_key'));
        $documents = collect();

        if ($ready && $key) {
            $documents = ClinicalLibraryDocument::where('user_id', $user->id)
                ->orderByDesc('id')
                ->get()
                ->map(fn (ClinicalLibraryDocument $document) => $this->present($document, $key));
        }

        return view('pro.medical.library-index', [
            'documents' => $documents,
            'clinicDeskReady' => $ready,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (! ClinicDeskSchema::ensure()) {
            return back()->withErrors(['library' => 'The reference library is on staging until the clinic tables are applied.']);
        }

        $vault = MedicalVault::activeForUser($user->id);
        $key = MedicalVaultCrypto::keyFromSession(session('medical_vault_key'));
        if (! $vault || ! $key) {
            return redirect('/pro/medical/vault/unlock');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:160',
            'file' => 'required|file|max:'.ClinicalLibraryDocument::MAX_KILOBYTES,
        ]);

        $file = $request->file('file');
        $extension = strtolower((string) $file->getClientOriginalExtension());
        if (! in_array($extension, ClinicalLibraryDocument::ALLOWED_EXTENSIONS, true)) {
            return back()->withErrors(['file' => 'Use a PDF, Word, or image file.'])->withInput();
        }

        $plain = file_get_contents($file->getRealPath());
        if ($plain === false) {
            return back()->withErrors(['file' => 'Unable to read the uploaded file.']);
        }

        $encryptedFile = MedicalVaultCrypto::encryptBytes($plain, $key);
        $title = MedicalVaultCrypto::encrypt(['title' => trim($validated['title'])], $key);
        $meta = MedicalVaultCrypto::encrypt([
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType() ?: 'application/octet-stream',
        ], $key);

        $storageDir = TenantStorage::medicalLibraryPath($user->id, $vault->id);
        $storagePath = $storageDir.'/'.Str::uuid()->toString().'.bin';
        TenantStorage::disk()->put($storagePath, $encryptedFile['ciphertext']);

        ClinicalLibraryDocument::create([
            'user_id' => $user->id,
            'vault_id' => $vault->id,
            'title_ciphertext' => $title['ciphertext'],
            'title_nonce' => $title['nonce'],
            'meta_ciphertext' => $meta['ciphertext'],
            'meta_nonce' => $meta['nonce'],
            'file_nonce' => $encryptedFile['nonce'],
            'storage_path' => $storagePath,
            'byte_size' => strlen($encryptedFile['ciphertext']),
            'ciphertext_sha256' => hash('sha256', $encryptedFile['ciphertext']),
        ]);

        return redirect('/pro/medical/library')->with('success', 'Saved to the library.');
    }

    public function download(ClinicalLibraryDocument $document): StreamedResponse
    {
        $user = Auth::user();
        if ($document->user_id !== $user->id) {
            abort(403);
        }

        $key = MedicalVaultCrypto::keyFromSession(session('medical_vault_key'));
        if (! $key) {
            abort(403);
        }

        if (! TenantStorage::disk()->exists($document->storage_path)) {
            abort(404);
        }

        $cipherBytes = TenantStorage::disk()->get($document->storage_path);
        $plain = MedicalVaultCrypto::decryptBytes($cipherBytes, $document->file_nonce, $key);
        $meta = MedicalVaultCrypto::decrypt($document->meta_ciphertext, $document->meta_nonce, $key);
        $title = $this->title($document, $key);

        $filename = $this->safeFilename($meta['original_name'] ?? ($title.'.bin'));
        $mime = $meta['mime'] ?? 'application/octet-stream';

        return response()->streamDownload(function () use ($plain) {
            echo $plain;
        }, $filename, [
            'Content-Type' => $mime,
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public function destroy(ClinicalLibraryDocument $document)
    {
        $user = Auth::user();
        if ($document->user_id !== $user->id) {
            abort(403);
        }

        if (TenantStorage::disk()->exists($document->storage_path)) {
            TenantStorage::disk()->delete($document->storage_path);
        }
        $document->delete();

        return redirect('/pro/medical/library')->with('success', 'Removed from the library.');
    }

    /**
     * @return array{id: int, title: string}
     */
    private function present(ClinicalLibraryDocument $document, string $key): array
    {
        return [
            'id' => $document->id,
            'title' => $this->title($document, $key),
        ];
    }

    private function title(ClinicalLibraryDocument $document, string $key): string
    {
        try {
            $payload = MedicalVaultCrypto::decrypt($document->title_ciphertext, $document->title_nonce, $key);

            return trim((string) ($payload['title'] ?? '')) ?: 'Document';
        } catch (\Throwable) {
            return 'Document';
        }
    }

    private function safeFilename(string $name): string
    {
        $name = str_replace(["\r", "\n", '"'], '', $name);
        $name = trim($name);

        return $name !== '' ? $name : 'library-file';
    }
}

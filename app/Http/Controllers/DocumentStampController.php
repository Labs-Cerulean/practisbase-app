<?php

namespace App\Http\Controllers;

use App\Models\DocumentStamp;
use App\Support\TenantStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DocumentStampController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $stamps = DocumentStamp::query()
            ->where('user_id', $user->id)
            ->orderByDesc('is_default')
            ->orderBy('label')
            ->get();

        return view('stamper.stamps-index', [
            'stamps' => $stamps,
        ]);
    }

    public function create()
    {
        $user = Auth::user();
        [$first, $last] = $this->splitName((string) $user->name);

        return view('stamper.stamps-form', [
            'stamp' => null,
            'presets' => DocumentStamp::PRESETS,
            'defaults' => [
                'label' => 'My stamp',
                'preset' => 'classic_border',
                'first_name' => $first,
                'last_name' => $last,
                'postnominals' => (string) ($user->postnominals ?? ''),
                'role_title' => (string) ($user->profession ?? ''),
                'is_default' => true,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $validated = $this->validateStamp($request);
        $signaturePath = $this->resolveSignaturePath($request, $user->id, null);
        $isFirst = ! DocumentStamp::query()->where('user_id', $user->id)->exists();
        $isDefault = $request->boolean('is_default') || $isFirst;

        if ($isDefault) {
            $this->clearDefaults($user->id);
        }

        DocumentStamp::create([
            'user_id' => $user->id,
            'label' => $validated['label'],
            'preset' => $validated['preset'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'postnominals' => $validated['postnominals'] ?: null,
            'role_title' => $validated['role_title'],
            'signature_path' => $signaturePath,
            'is_default' => $isDefault,
        ]);

        return redirect('/stamper')
            ->with('success', 'Stamp saved. Place it on a PDF when you are ready.');
    }

    public function edit(int $id)
    {
        $stamp = $this->findOwned($id);

        return view('stamper.stamps-form', [
            'stamp' => $stamp,
            'presets' => DocumentStamp::PRESETS,
            'defaults' => [
                'label' => $stamp->label,
                'preset' => $stamp->preset,
                'first_name' => $stamp->first_name,
                'last_name' => $stamp->last_name,
                'postnominals' => (string) ($stamp->postnominals ?? ''),
                'role_title' => $stamp->role_title,
                'is_default' => (bool) $stamp->is_default,
            ],
        ]);
    }

    public function update(Request $request, int $id)
    {
        $user = Auth::user();
        $stamp = $this->findOwned($id);
        $validated = $this->validateStamp($request);

        $signaturePath = $this->resolveSignaturePath($request, $user->id, $stamp->signature_path);

        if ($request->boolean('is_default')) {
            $this->clearDefaults($user->id, $stamp->id);
        }

        $stamp->update([
            'label' => $validated['label'],
            'preset' => $validated['preset'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'postnominals' => $validated['postnominals'] ?: null,
            'role_title' => $validated['role_title'],
            'signature_path' => $signaturePath,
            'is_default' => $request->boolean('is_default'),
        ]);

        return redirect('/stamper/stamps')
            ->with('success', 'Stamp updated.');
    }

    public function destroy(int $id)
    {
        $stamp = $this->findOwned($id);

        if ($stamp->signature_path) {
            TenantStorage::disk()->delete($stamp->signature_path);
        }

        $wasDefault = $stamp->is_default;
        $userId = $stamp->user_id;
        $stamp->delete();

        if ($wasDefault) {
            $next = DocumentStamp::query()
                ->where('user_id', $userId)
                ->orderBy('id')
                ->first();
            if ($next) {
                $next->update(['is_default' => true]);
            }
        }

        return redirect('/stamper/stamps')
            ->with('success', 'Stamp deleted.');
    }

    public function makeDefault(int $id)
    {
        $stamp = $this->findOwned($id);
        $this->clearDefaults($stamp->user_id, $stamp->id);
        $stamp->update(['is_default' => true]);

        return redirect('/stamper/stamps')
            ->with('success', 'Default stamp updated.');
    }

    private function findOwned(int $id): DocumentStamp
    {
        return DocumentStamp::query()
            ->where('user_id', Auth::id())
            ->where('id', $id)
            ->firstOrFail();
    }

    private function validateStamp(Request $request): array
    {
        return $request->validate([
            'label' => 'required|string|max:120',
            'preset' => ['required', 'string', Rule::in(array_keys(DocumentStamp::PRESETS))],
            'first_name' => 'required|string|max:120',
            'last_name' => 'required|string|max:120',
            'postnominals' => 'nullable|string|max:120',
            'role_title' => 'required|string|max:160',
            'is_default' => 'nullable|boolean',
            'signature' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'signature_data' => 'nullable|string|max:1500000',
            'remove_signature' => 'nullable|boolean',
        ]);
    }

    private function resolveSignaturePath(Request $request, int $userId, ?string $existingPath): ?string
    {
        if ($request->boolean('remove_signature')) {
            if ($existingPath) {
                TenantStorage::disk()->delete($existingPath);
            }

            return null;
        }

        if ($request->hasFile('signature')) {
            if ($existingPath) {
                TenantStorage::disk()->delete($existingPath);
            }

            return $request->file('signature')->store(
                TenantStorage::stampsPath($userId),
                TenantStorage::diskName()
            );
        }

        if ($request->filled('signature_data')) {
            $stored = $this->storeSignatureDataUri($request->input('signature_data'), $userId);
            if ($stored !== null) {
                if ($existingPath) {
                    TenantStorage::disk()->delete($existingPath);
                }

                return $stored;
            }
        }

        return $existingPath;
    }

    private function storeSignatureDataUri(string $dataUri, int $userId): ?string
    {
        if (! preg_match('#^data:image/(png|jpeg|jpg|webp);base64,#i', $dataUri, $matches)) {
            return null;
        }

        $raw = substr($dataUri, strpos($dataUri, ',') + 1);
        $binary = base64_decode($raw, true);
        if ($binary === false || strlen($binary) < 32 || strlen($binary) > 1_500_000) {
            return null;
        }

        $ext = strtolower($matches[1]) === 'jpg' ? 'jpeg' : strtolower($matches[1]);
        $path = TenantStorage::stampsPath($userId).'/'.Str::uuid()->toString().'.'.$ext;
        TenantStorage::disk()->put($path, $binary);

        return $path;
    }

    private function clearDefaults(int $userId, ?int $exceptId = null): void
    {
        $query = DocumentStamp::query()->where('user_id', $userId);
        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }
        $query->update(['is_default' => false]);
    }

    /** @return array{0: string, 1: string} */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        if ($parts === []) {
            return ['', ''];
        }
        if (count($parts) === 1) {
            return [$parts[0], ''];
        }

        $last = array_pop($parts);

        return [implode(' ', $parts), (string) $last];
    }
}

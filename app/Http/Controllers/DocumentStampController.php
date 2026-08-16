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
                'warrant_number' => (string) ($user->warrant_number ?? ''),
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
            'warrant_number' => $validated['warrant_number'] ?: null,
            'signature_path' => $signaturePath,
            'is_default' => $isDefault,
        ]);

        $this->syncWarrantToProfile($user, $validated['warrant_number'] ?? null);

        return redirect('/stamper')
            ->with('success', 'Stamp saved. Place it on a PDF when you are ready.');
    }

    public function edit(int $id)
    {
        $user = Auth::user();
        $stamp = $this->findOwned($id);
        $warrant = (string) ($stamp->warrant_number ?? '');
        if ($warrant === '') {
            $warrant = (string) ($user->warrant_number ?? '');
        }

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
                'warrant_number' => $warrant,
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
            'warrant_number' => $validated['warrant_number'] ?: null,
            'signature_path' => $signaturePath,
            'is_default' => $request->boolean('is_default'),
        ]);

        $this->syncWarrantToProfile($user, $validated['warrant_number'] ?? null);

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

    private function syncWarrantToProfile($user, ?string $warrant): void
    {
        $warrant = filled($warrant) ? trim($warrant) : null;
        if ($warrant === null) {
            return;
        }
        if ((string) ($user->warrant_number ?? '') === $warrant) {
            return;
        }
        $user->update(['warrant_number' => $warrant]);
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
            'warrant_number' => 'nullable|string|max:80',
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

            $file = $request->file('signature');
            $binary = file_get_contents($file->getRealPath());
            $cleaned = $this->knockOutSignaturePaper($binary);
            if ($cleaned !== null) {
                $path = TenantStorage::stampsPath($userId).'/'.Str::uuid()->toString().'.png';
                TenantStorage::disk()->put($path, $cleaned);

                return $path;
            }

            return $file->store(
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

        $cleaned = $this->knockOutSignaturePaper($binary);
        if ($cleaned !== null) {
            $path = TenantStorage::stampsPath($userId).'/'.Str::uuid()->toString().'.png';
            TenantStorage::disk()->put($path, $cleaned);

            return $path;
        }

        $ext = strtolower($matches[1]) === 'jpg' ? 'jpeg' : strtolower($matches[1]);
        $path = TenantStorage::stampsPath($userId).'/'.Str::uuid()->toString().'.'.$ext;
        TenantStorage::disk()->put($path, $binary);

        return $path;
    }

    /**
     * Turn near-white paper pixels transparent so wet scans do not cover PDF text.
     */
    private function knockOutSignaturePaper(string $binary): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        $src = @imagecreatefromstring($binary);
        if ($src === false) {
            return null;
        }

        $width = imagesx($src);
        $height = imagesy($src);
        if ($width < 1 || $height < 1 || $width > 4000 || $height > 4000) {
            imagedestroy($src);

            return null;
        }

        $dst = imagecreatetruecolor($width, $height);
        imagesavealpha($dst, true);
        imagealphablending($dst, false);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $width, $height, $transparent);

        $hard = 235;
        $soft = 200;

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgba = imagecolorat($src, $x, $y);
                $a = ($rgba & 0x7F000000) >> 24;
                $r = ($rgba >> 16) & 0xFF;
                $g = ($rgba >> 8) & 0xFF;
                $b = $rgba & 0xFF;

                if (imageistruecolor($src) === false) {
                    $cols = imagecolorsforindex($src, $rgba);
                    $r = $cols['red'];
                    $g = $cols['green'];
                    $b = $cols['blue'];
                    $a = (int) round(($cols['alpha'] ?? 0));
                }

                $min = min($r, $g, $b);
                $max = max($r, $g, $b);
                $spread = $max - $min;

                // Paper / near-white (low colour spread)
                if ($min >= $hard && $spread < 30) {
                    continue;
                }

                $alpha = 0;
                if ($min >= $soft && $spread < 40) {
                    $alpha = (int) round(127 * (($min - $soft) / max(1, $hard - $soft)));
                    $alpha = max(0, min(127, $alpha));
                }

                // Keep existing transparency if any (PNG alpha is 0-127 in GD)
                if (imageistruecolor($src)) {
                    $alpha = max($alpha, $a);
                }

                $color = imagecolorallocatealpha($dst, $r, $g, $b, $alpha);
                imagesetpixel($dst, $x, $y, $color);
            }
        }

        imagedestroy($src);

        ob_start();
        imagesavealpha($dst, true);
        imagepng($dst, null, 6);
        $png = ob_get_clean();
        imagedestroy($dst);

        return is_string($png) && $png !== '' ? $png : null;
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

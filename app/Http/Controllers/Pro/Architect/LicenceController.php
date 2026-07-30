<?php

namespace App\Http\Controllers\Pro\Architect;

use App\Http\Controllers\Controller;
use App\Models\ArchitectLicenceContact;
use App\Models\ArchitectSiteParty;
use App\Support\Architect\BcaTemplateCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LicenceController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $user = Auth::user();
        $validated = $request->validate([
            'q' => 'nullable|string|max:120',
            'licence_type' => 'nullable|in:'.implode(',', array_keys(ArchitectSiteParty::LICENCE_TYPES)),
            'limit' => 'nullable|integer|min:1|max:20',
        ]);

        $q = trim((string) ($validated['q'] ?? ''));
        $limit = (int) ($validated['limit'] ?? 10);

        $items = ArchitectLicenceContact::query()
            ->where('user_id', $user->id)
            ->when(! empty($validated['licence_type']), fn ($query) => $query->where('licence_type', $validated['licence_type']))
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.$q.'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('full_name', 'ilike', $like)
                        ->orWhere('company_name', 'ilike', $like)
                        ->orWhere('licence_number', 'ilike', $like)
                        ->orWhere('locality', 'ilike', $like);
                });
            })
            ->orderByDesc('last_used_at')
            ->orderBy('full_name')
            ->limit($limit)
            ->get()
            ->map(fn (ArchitectLicenceContact $c) => [
                'id' => $c->id,
                'licence_type' => $c->licence_type,
                'licence_number' => $c->licence_number,
                'full_name' => $c->full_name,
                'company_name' => $c->company_name,
                'mobile' => $c->mobile,
                'locality' => $c->locality,
            ]);

        return response()->json([
            'items' => $items,
            'registers' => BcaTemplateCatalog::REGISTER_URLS,
            'note' => 'Live BCA registers are linked for verification. Save licence contacts here for fast reuse on site teams.',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();
        $validated = $request->validate([
            'licence_type' => 'required|in:'.implode(',', array_keys(ArchitectSiteParty::LICENCE_TYPES)),
            'licence_number' => 'nullable|string|max:120',
            'full_name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'mobile' => 'nullable|string|max:64',
            'locality' => 'nullable|string|max:120',
            'notes' => 'nullable|string|max:2000',
        ]);

        $contact = ArchitectLicenceContact::create([
            'user_id' => $user->id,
            ...$validated,
            'source' => 'manual',
            'last_used_at' => now(),
        ]);

        return response()->json(['item' => $contact], 201);
    }
}

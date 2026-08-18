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
            'role_key' => 'nullable|in:'.implode(',', array_keys(ArchitectSiteParty::ROLES)),
            'limit' => 'nullable|integer|min:1|max:20',
        ]);

        $q = trim((string) ($validated['q'] ?? ''));
        $limit = (int) ($validated['limit'] ?? 12);
        $roleKey = $validated['role_key'] ?? null;

        $contacts = ArchitectLicenceContact::query()
            ->where('user_id', $user->id)
            ->when(! empty($validated['licence_type']), fn ($query) => $query->where('licence_type', $validated['licence_type']))
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.$q.'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('full_name', 'ilike', $like)
                        ->orWhere('company_name', 'ilike', $like)
                        ->orWhere('licence_number', 'ilike', $like)
                        ->orWhere('mobile', 'ilike', $like)
                        ->orWhere('email', 'ilike', $like)
                        ->orWhere('locality', 'ilike', $like);
                });
            })
            ->when($roleKey, function ($query) use ($roleKey) {
                $query->orderByRaw('CASE WHEN preferred_role_key = ? THEN 0 ELSE 1 END', [$roleKey]);
            })
            ->orderByDesc('last_used_at')
            ->orderBy('full_name')
            ->limit($limit)
            ->get()
            ->map(fn (ArchitectLicenceContact $c) => $c->toSuggestPayload());

        $seen = [];
        foreach ($contacts as $item) {
            $seen[mb_strtolower($item['full_name']).'|'.mb_strtolower((string) ($item['licence_number'] ?? ''))] = true;
        }

        $partyQuery = ArchitectSiteParty::query()
            ->where('user_id', $user->id)
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.$q.'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('full_name', 'ilike', $like)
                        ->orWhere('company_name', 'ilike', $like)
                        ->orWhere('licence_number', 'ilike', $like)
                        ->orWhere('mobile', 'ilike', $like)
                        ->orWhere('email', 'ilike', $like);
                });
            })
            ->when($roleKey, function ($query) use ($roleKey) {
                $query->orderByRaw('CASE WHEN role_key = ? THEN 0 ELSE 1 END', [$roleKey]);
            })
            ->orderByDesc('updated_at')
            ->limit($limit * 3)
            ->get();

        $fromParties = [];
        foreach ($partyQuery as $party) {
            $key = mb_strtolower((string) $party->full_name).'|'.mb_strtolower((string) ($party->licence_number ?? ''));
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $fromParties[] = [
                'id' => 'party-'.$party->id,
                'full_name' => (string) $party->full_name,
                'company_name' => $party->company_name,
                'mobile' => $party->mobile,
                'email' => $party->email,
                'id_card' => $party->id_card,
                'licence_type' => $party->licence_type,
                'licence_number' => $party->licence_number,
                'preferred_role_key' => $party->role_key,
                'locality' => null,
                'source' => 'past_project',
            ];
            if (count($fromParties) + $contacts->count() >= $limit) {
                break;
            }
        }

        $items = $contacts->values()->all();
        foreach ($fromParties as $row) {
            if (count($items) >= $limit) {
                break;
            }
            $items[] = $row;
        }

        return response()->json([
            'items' => $items,
            'registers' => BcaTemplateCatalog::REGISTER_URLS,
            'note' => 'Saved team members reuse across projects. Live BCA registers stay available for licence checks.',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();
        $validated = $request->validate([
            'licence_type' => 'nullable|in:'.implode(',', array_keys(ArchitectSiteParty::LICENCE_TYPES)),
            'licence_number' => 'nullable|string|max:120',
            'full_name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'mobile' => 'nullable|string|max:64',
            'email' => 'nullable|email|max:255',
            'id_card' => 'nullable|string|max:64',
            'preferred_role_key' => 'nullable|in:'.implode(',', array_keys(ArchitectSiteParty::ROLES)),
            'locality' => 'nullable|string|max:120',
            'notes' => 'nullable|string|max:2000',
        ]);

        $contact = ArchitectLicenceContact::rememberForUser($user->id, [
            ...$validated,
            'source' => 'manual',
        ]);

        return response()->json(['item' => $contact->toSuggestPayload()], 201);
    }
}

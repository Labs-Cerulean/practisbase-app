<?php

namespace App\Http\Controllers\Pro\Medical;

use App\Http\Controllers\Controller;
use App\Models\PrescriptionCatalogItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PrescriptionCatalogController extends Controller
{
    public function suggest(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'q' => 'nullable|string|max:120',
            'limit' => 'nullable|integer|min:1|max:20',
        ]);

        $items = PrescriptionCatalogItem::suggestForUser(
            $user->id,
            (string) ($validated['q'] ?? ''),
            (int) ($validated['limit'] ?? 8)
        );

        return response()->json(['items' => $items]);
    }
}

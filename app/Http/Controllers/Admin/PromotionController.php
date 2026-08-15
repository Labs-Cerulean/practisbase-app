<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Cerulean operator promo admin (company_books gate).
 * Dynamically mint promo codes, set capacity, toggle active.
 */
class PromotionController extends Controller
{
    public function index(): View
    {
        $promotions = Promotion::query()
            ->orderByDesc('id')
            ->paginate(40);

        return view('admin.promotions-index', [
            'promotions' => $promotions,
            'types' => Promotion::TYPES,
        ]);
    }

    public function create(): View
    {
        return view('admin.promotions-form', [
            'promotion' => null,
            'types' => Promotion::TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $code = Promotion::normalizeCode($data['code'] ?? '');
        if ($code === '') {
            $code = Promotion::generateCode($data['code_prefix'] ?? 'PROMO');
        }

        if (Promotion::query()->where('code', $code)->exists()) {
            return back()->withErrors(['code' => 'That promo code already exists.'])->withInput();
        }

        $promo = Promotion::create([
            'code' => $code,
            'type' => $data['type'],
            'value' => $data['value'],
            'max_uses' => filled($data['max_uses'] ?? null) ? (int) $data['max_uses'] : null,
            'current_uses' => 0,
            'expires_at' => filled($data['expires_at'] ?? null) ? $data['expires_at'] : null,
            'is_active' => $request->boolean('is_active', true),
            'label' => $data['label'] ?? null,
        ]);

        return redirect('/company/promotions')
            ->with('success', 'Promotion created: '.$promo->code.' ('.$promo->valueSummary().').');
    }

    public function edit(Promotion $promotion): View
    {
        return view('admin.promotions-form', [
            'promotion' => $promotion,
            'types' => Promotion::TYPES,
        ]);
    }

    public function update(Request $request, Promotion $promotion): RedirectResponse
    {
        $data = $this->validated($request, $promotion->id);

        $code = Promotion::normalizeCode($data['code']);
        if ($code === '') {
            return back()->withErrors(['code' => 'Code is required.'])->withInput();
        }

        if (Promotion::query()->where('code', $code)->where('id', '!=', $promotion->id)->exists()) {
            return back()->withErrors(['code' => 'That promo code already exists.'])->withInput();
        }

        $promotion->update([
            'code' => $code,
            'type' => $data['type'],
            'value' => $data['value'],
            'max_uses' => filled($data['max_uses'] ?? null) ? (int) $data['max_uses'] : null,
            'expires_at' => filled($data['expires_at'] ?? null) ? $data['expires_at'] : null,
            'is_active' => $request->boolean('is_active'),
            'label' => $data['label'] ?? null,
        ]);

        return redirect('/company/promotions')
            ->with('success', 'Promotion '.$promotion->code.' updated.');
    }

    public function toggle(Promotion $promotion): RedirectResponse
    {
        $promotion->is_active = ! $promotion->is_active;
        $promotion->save();

        return redirect('/company/promotions')
            ->with('success', $promotion->code.' is now '.($promotion->is_active ? 'active' : 'inactive').'.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'code' => 'nullable|string|max:40',
            'code_prefix' => 'nullable|string|max:12',
            'type' => 'required|in:'.implode(',', array_keys(Promotion::TYPES)),
            'value' => 'required|numeric|min:0.01|max:999999',
            'max_uses' => 'nullable|integer|min:1|max:100000',
            'expires_at' => 'nullable|date',
            'label' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);
    }
}

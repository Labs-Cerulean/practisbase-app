@extends('layouts.app')

@section('page_title', $promotion ? 'Edit promotion' : 'New promotion')

@section('content')
    <div style="max-width: 640px; margin: 0 auto;">
        <a href="/company/promotions" style="color: var(--text-muted); font-weight: 600; text-decoration: none; font-size: 0.85rem;">&larr; Promotions</a>
        <h1 style="margin: 0.4rem 0 1rem; color: var(--primary-navy); font-size: 1.4rem;">{{ $promotion ? 'Edit promotion' : 'New promotion' }}</h1>

        @if($errors->any())
            <div style="background: #fef2f2; color: #991b1b; padding: 0.85rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
                @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif

        <form method="POST" action="{{ $promotion ? '/company/promotions/'.$promotion->id : '/company/promotions' }}" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.25rem; box-shadow: var(--shadow-sm);">
            @csrf
            @if($promotion)
                @method('PUT')
            @endif

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.35rem;">Code</label>
                <input type="text" name="code" value="{{ old('code', $promotion->code ?? '') }}" maxlength="40" placeholder="{{ $promotion ? '' : 'Leave blank to auto-generate' }}"
                       style="width: 100%; padding: 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-transform: uppercase; font-weight: 700; letter-spacing: 0.04em;">
                @unless($promotion)
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.35rem;">Optional prefix if auto-generating:</div>
                    <input type="text" name="code_prefix" value="{{ old('code_prefix', 'FOUNDING') }}" maxlength="12" style="width: 100%; margin-top: 0.35rem; padding: 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                @endunless
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.35rem;">Label</label>
                <input type="text" name="label" value="{{ old('label', $promotion->label ?? '') }}" maxlength="255" placeholder="e.g. Founding 50 cohort"
                       style="width: 100%; padding: 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.35rem;">Type</label>
                    <select name="type" required style="width: 100%; padding: 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                        @foreach($types as $key => $label)
                            <option value="{{ $key }}" @selected(old('type', $promotion->type ?? 'free_months') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.35rem;">Value</label>
                    <input type="number" name="value" step="0.01" min="0.01" required value="{{ old('value', $promotion->value ?? '3') }}"
                           style="width: 100%; padding: 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.3rem;">Months, % off, or € off.</div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.35rem;">Max uses <span style="font-weight: 500; color: var(--text-muted);">(optional)</span></label>
                    <input type="number" name="max_uses" min="1" value="{{ old('max_uses', $promotion->max_uses ?? '50') }}"
                           style="width: 100%; padding: 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.35rem;">Expires <span style="font-weight: 500; color: var(--text-muted);">(optional)</span></label>
                    <input type="date" name="expires_at" value="{{ old('expires_at', optional($promotion?->expires_at)->format('Y-m-d')) }}"
                           style="width: 100%; padding: 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>

            <label style="display: flex; gap: 0.55rem; align-items: center; margin-bottom: 1.25rem; cursor: pointer;">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $promotion->is_active ?? true))>
                <span style="font-weight: 600;">Active</span>
            </label>

            <button type="submit" style="width: 100%; padding: 0.85rem; background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">
                {{ $promotion ? 'Save promotion' : 'Create promotion' }}
            </button>
        </form>
    </div>
@endsection

@extends('layouts.app')

@section('page_title', 'Log company expense')

@section('content')
    <div style="max-width: 640px; margin: 0 auto; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.75rem; box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h1 style="font-size: 1.3rem; color: var(--primary-navy); margin: 0;">Log company expense</h1>
            <a href="/company/expenses" style="color: var(--text-muted); font-weight: 600; font-size: 0.85rem; text-decoration: none;">Cancel</a>
        </div>

        <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.45; margin: 0 0 1.25rem;">
            Costs from January before incorporation ({{ $profile->first_period_start->format('d M Y') }}) can be logged as pre-incorporation and funded by you for later refund.
        </p>

        @if($errors->any())
            <div style="margin-bottom: 1rem; padding: 0.85rem 1rem; background: #fef2f2; border: 1px solid #fecaca; border-radius: var(--radius-md); color: #991b1b; font-size: 0.9rem;">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="/company/expenses" enctype="multipart/form-data">
            @csrf
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Date</label>
                <input type="date" name="expense_date" value="{{ old('expense_date', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}" min="2026-01-01" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Category</label>
                <select name="category" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                    @foreach($categories as $key => $label)
                        <option value="{{ $key }}" @selected(old('category', 'software') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Description</label>
                <input type="text" name="description" value="{{ old('description') }}" required placeholder="e.g. Railway Jul 2026" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Amount ex-VAT (€)</label>
                    <input type="number" name="amount" step="0.01" min="0.01" value="{{ old('amount') }}" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">VAT amount (€)</label>
                    <input type="number" name="vat_amount" step="0.01" min="0" value="{{ old('vat_amount', '0') }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.3rem;">Often €0 on reverse-charge / US SaaS invoices.</div>
                </div>
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Who paid?</label>
                <select name="funded_by" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                    <option value="director" @selected(old('funded_by', 'director') === 'director')>I paid personally (company owes me a refund)</option>
                    <option value="company" @selected(old('funded_by') === 'company')>Company bank / card</option>
                </select>
            </div>
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Receipt (photo or PDF)</label>
                <input type="file" name="receipt" accept=".jpg,.jpeg,.png,.pdf,image/*" capture="environment" style="width: 100%; padding: 0.5rem 0;">
            </div>
            <button type="submit" style="background: var(--primary-cerulean); color: white; border: none; padding: 0.7rem 1.25rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Save expense</button>
        </form>
    </div>
@endsection

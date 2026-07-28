@extends('layouts.app')

@section('page_title', 'New expense')

@section('content')
    <div style="max-width: 640px; margin: 0 auto; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2 style="color: var(--primary-navy); margin: 0;">Log Expense</h2>
            <a href="/expenses" style="color: var(--text-muted); text-decoration: none; font-weight: 600; font-size: 0.9rem;">Cancel</a>
        </div>

        <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: var(--radius-md); padding: 0.75rem 1rem; margin-bottom: 1.5rem;">
            <p style="margin: 0; font-size: 0.85rem; color: #78350f; line-height: 1.45;">
                Claim only costs for your practice (or the business share of mixed costs such as car, fuel, or home bills).
                <button type="button" data-open-expense-guide style="background: none; border: none; padding: 0; font: inherit; color: #92400e; font-weight: 700; cursor: pointer; border-bottom: 1px dotted #92400e;">What is claimable?</button>
            </p>
        </div>

        @if($errors->any())
            <div style="background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; border: 1px solid #fecaca;">
                @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif

        <form action="/expenses" method="POST" enctype="multipart/form-data">
            @csrf

            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Date</label>
                <input type="date" name="expense_date" value="{{ old('expense_date', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Category</label>
                <select name="category" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                    @foreach($categories as $key => $label)
                        <option value="{{ $key }}" {{ old('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Description</label>
                <input type="text" name="description" value="{{ old('description') }}" required maxlength="1000" placeholder="e.g. Accounting software annual plan" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Amount (ex VAT)</label>
                    <input type="number" name="amount" step="0.01" min="0.01" value="{{ old('amount') }}" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">VAT amount <span style="font-weight: 500; color: var(--text-muted);">(optional)</span></label>
                    <input type="number" name="vat_amount" step="0.01" min="0" value="{{ old('vat_amount', '0') }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Receipt upload <span style="font-weight: 500; color: var(--text-muted);">(optional)</span></label>
                <input type="file" name="receipt" accept=".jpg,.jpeg,.png,.pdf" style="width: 100%; padding: 0.5rem 0;">
                <p style="font-size: 0.75rem; color: var(--text-muted); margin: 0.35rem 0 0;">JPG, PNG or PDF · max 5MB · stored privately on your tenant object storage (Cloudflare R2 in production).</p>
            </div>

            <button type="submit" style="width: 100%; padding: 0.9rem; background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Save Expense</button>
        </form>
    </div>

    @include('expenses.partials.claim-guide-modal')
@endsection

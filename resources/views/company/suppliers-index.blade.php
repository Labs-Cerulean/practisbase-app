@extends('layouts.app')

@section('page_title', 'Suppliers')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;">
        <div>
            <h1 style="font-size: 1.4rem; color: var(--primary-navy); margin: 0 0 0.25rem;">Suppliers</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">{{ $year }} spend by supplier. Each expense stays on one supplier so supply-chain cost can be read later.</p>
        </div>
        <a href="/company/expenses/create" style="background: var(--primary-cerulean); color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">+ Expense</a>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; border-radius: var(--radius-lg); padding: 0.85rem 1.1rem; margin-bottom: 1rem; font-size: 0.9rem;">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; border-radius: var(--radius-lg); padding: 0.85rem 1.1rem; margin-bottom: 1rem; font-size: 0.9rem;">{{ $errors->first() }}</div>
    @endif

    <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.25rem 1.35rem; box-shadow: var(--shadow-sm); margin-bottom: 1rem;">
        <h2 style="font-size: 1rem; color: var(--primary-navy); margin: 0 0 0.85rem;">Add a supplier</h2>
        <form method="POST" action="/company/suppliers">
            @csrf
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr)); gap: 0.75rem; margin-bottom: 0.75rem;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.35rem; font-size: 0.85rem;">Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="Google" style="width: 100%; padding: 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.35rem; font-size: 0.85rem;">VAT number</label>
                    <input type="text" name="vat_number" value="{{ old('vat_number') }}" placeholder="IE6388047V" style="width: 100%; padding: 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.35rem; font-size: 0.85rem;">Country</label>
                    <input type="text" name="country" value="{{ old('country') }}" placeholder="Ireland" style="width: 100%; padding: 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr)); gap: 0.75rem; align-items: end;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.35rem; font-size: 0.85rem;">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" style="width: 100%; padding: 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.35rem; font-size: 0.85rem;">Usual category</label>
                    <select name="default_category" style="width: 100%; padding: 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                        <option value="">No default</option>
                        @foreach($categories as $key => $label)
                            <option value="{{ $key }}" @selected(old('default_category') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" style="background: var(--primary-navy); color: white; border: none; padding: 0.7rem 1rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Save supplier</button>
            </div>
        </form>
    </div>

    @if($unassignedCount > 0)
        <a href="/company/expenses?year={{ $year }}" style="display: block; background: #fffbeb; border: 1px solid #fde68a; border-radius: var(--radius-lg); padding: 1rem 1.15rem; margin-bottom: 0.75rem; text-decoration: none;">
            <div style="font-weight: 700; color: #92400e;">Not assigned</div>
            <div style="font-size: 0.85rem; color: #92400e; margin-top: 0.2rem;">{{ $unassignedCount }} {{ $unassignedCount === 1 ? 'expense' : 'expenses' }} · cash €{{ number_format($unassignedCash, 2) }}</div>
        </a>
    @endif

    <div style="display: grid; gap: 0.75rem;">
        @forelse($suppliers as $supplier)
            @php
                $row = $spend[$supplier->id] ?? ['count' => 0, 'net' => 0, 'cash' => 0];
            @endphp
            <a href="/company/expenses?year={{ $year }}&supplier={{ $supplier->id }}" style="display: block; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1rem 1.15rem; box-shadow: var(--shadow-sm); text-decoration: none;">
                <div style="display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                    <div>
                        <div style="font-weight: 700; color: var(--primary-navy);">{{ $supplier->name }}</div>
                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">
                            @if($supplier->vat_number) VAT {{ $supplier->vat_number }} @endif
                            @if($supplier->country) · {{ $supplier->country }} @endif
                            @if($supplier->default_category) · {{ $categories[$supplier->default_category] ?? $supplier->default_category }} @endif
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-weight: 700; color: var(--primary-navy); border-bottom: 1px dotted var(--primary-navy);">€{{ number_format($row['cash'], 2) }}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">
                            {{ $row['count'] }} {{ $row['count'] === 1 ? 'invoice' : 'invoices' }}
                            · net €{{ number_format($row['net'], 2) }}
                        </div>
                    </div>
                </div>
            </a>
        @empty
            <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; text-align: center; color: var(--text-muted);">
                No suppliers yet. Add Google or your bank here, or create one while logging the next invoice.
            </div>
        @endforelse
    </div>
@endsection

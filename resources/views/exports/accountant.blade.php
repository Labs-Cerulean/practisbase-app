@extends('layouts.app')

@section('page_title', 'For accountant')

@section('content')
    <div style="max-width: 720px; margin: 0 auto;">
        <h1 style="font-size: 1.35rem; color: var(--primary-navy); margin: 0 0 0.35rem;">Accountant</h1>
        <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0 0 1.5rem; line-height: 1.45;">
            Download a full ledger pack for your accountant. You send the file yourself — there is no accountant login seat.
        </p>

        @if($errors->any())
            <div style="background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; border: 1px solid #fecaca;">
                @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif

        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm);">
            <form action="/exports/accountant" method="POST">
                @csrf
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Fiscal year</label>
                    <select name="year" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white;">
                        @foreach($years as $year)
                            <option value="{{ $year }}" {{ $year === $currentYear ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>

                <ul style="margin: 0 0 1.5rem; padding-left: 1.25rem; color: var(--text-muted); font-size: 0.9rem; line-height: 1.55;">
                    <li>Document register (invoices / RFPs / credit notes with fiscal vs non-fiscal flag)</li>
                    <li>Payment history</li>
                    <li>Client billing identity (including archived)</li>
                    <li>Expense ledger + tax/PT/SSC/VAT payments</li>
                    <li>VAT summary for the year</li>
                </ul>

                <button type="submit" style="width: 100%; padding: 0.9rem; background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">
                    Download ZIP (CSV pack)
                </button>
            </form>
        </div>
    </div>
@endsection

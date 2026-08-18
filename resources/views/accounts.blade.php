@extends('layouts.app')

@section('page_title', 'Accounts')

@section('content')
    @php
        $firstName = $user->name ? explode(' ', $user->name)[0] : '';
    @endphp

    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;">
        <div>
            <div style="font-size: 0.75rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.25rem;">{{ $tierLabel }}</div>
            <h1 style="font-size: 1.5rem; color: var(--primary-navy); margin: 0 0 0.25rem;">Accounts</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">
                @if($hasFinancial)
                    Invoices, collections, and {{ $year }} tax figures.
                @else
                    Free billing layer — invoices and RFPs. Tax and VAT unlocks with Standard.
                @endif
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="/ledger/create" style="background: var(--primary-cerulean); color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">+ Invoice / RFP</a>
            <a href="/clients/create" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">+ Client</a>
            @if($hasFinancial)
                <a href="/expenses/create" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">+ Expense</a>
            @endif
            @if(($package ?? null) === 'arch')
                <a href="/dashboard" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">Studio overview</a>
            @endif
        </div>
    </div>

    @include('partials.accounts-desk')
@endsection

@extends('layouts.app')

@section('page_title', 'Company clients')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;">
        <div>
            <h1 style="font-size: 1.4rem; color: var(--primary-navy); margin: 0 0 0.25rem;">Company clients</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">Customers of Cerulean Labs Ltd (separate from practice clients).</p>
        </div>
        <a href="/company/clients/create" style="background: var(--primary-cerulean); color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">+ Client</a>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; border-radius: var(--radius-lg); padding: 0.85rem 1.1rem; margin-bottom: 1rem; font-size: 0.9rem;">{{ session('success') }}</div>
    @endif

    <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); overflow: hidden;">
        @forelse($clients as $client)
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding: 1rem 1.25rem; border-bottom: 1px solid var(--border-light); flex-wrap: wrap;">
                <div>
                    <div style="font-weight: 700; color: var(--primary-navy);">{{ $client->name }}</div>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.15rem;">
                        {{ $client->vat_number ? 'VAT '.$client->vat_number : 'No VAT number' }}
                        @if($client->email) · {{ $client->email }}@endif
                        · Due €{{ number_format((float) $client->open_due, 2) }}
                    </div>
                </div>
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <a href="/company/clients/{{ $client->id }}?tab=statement" style="font-size: 0.8rem; font-weight: 600; color: var(--primary-cerulean); text-decoration: none; padding: 0.4rem 0.7rem; background: rgba(2, 132, 199, 0.1); border-radius: 6px;">Statement</a>
                    <a href="/company/clients/{{ $client->id }}?tab=history" style="font-size: 0.8rem; font-weight: 600; color: #475569; text-decoration: none; padding: 0.4rem 0.7rem; background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 6px;">History</a>
                    <a href="/company/clients/{{ $client->id }}/edit" style="font-size: 0.8rem; font-weight: 600; color: var(--primary-navy); text-decoration: none; padding: 0.4rem 0.7rem;">Edit</a>
                </div>
            </div>
        @empty
            <div style="padding: 2rem 1.25rem; color: var(--text-muted); text-align: center;">No company clients yet.</div>
        @endforelse
    </div>
@endsection

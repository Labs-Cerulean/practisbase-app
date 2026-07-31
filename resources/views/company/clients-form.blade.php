@extends('layouts.app')

@section('page_title', $client ? 'Edit client' : 'New company client')

@section('content')
    <div style="max-width: 640px; margin: 0 auto; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.75rem; box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h1 style="font-size: 1.3rem; color: var(--primary-navy); margin: 0;">{{ $client ? 'Edit client' : 'New company client' }}</h1>
            <a href="/company/clients" style="color: var(--text-muted); font-weight: 600; font-size: 0.85rem; text-decoration: none;">Cancel</a>
        </div>

        <form method="POST" action="{{ $client ? '/company/clients/'.$client->id : '/company/clients' }}">
            @csrf
            @if($client) @method('PUT') @endif

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Name</label>
                <input type="text" name="name" value="{{ old('name', $client->name ?? '') }}" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Email</label>
                    <input type="email" name="email" value="{{ old('email', $client->email ?? '') }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $client->phone ?? '') }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Client VAT number</label>
                    <input type="text" name="vat_number" value="{{ old('vat_number', $client->vat_number ?? '') }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">C-number / reg.</label>
                    <input type="text" name="registration_number" value="{{ old('registration_number', $client->registration_number ?? '') }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Billing address</label>
                <textarea name="billing_address" rows="3" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); resize: vertical;">{{ old('billing_address', $client->billing_address ?? '') }}</textarea>
            </div>
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Notes</label>
                <textarea name="notes" rows="2" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); resize: vertical;">{{ old('notes', $client->notes ?? '') }}</textarea>
            </div>
            <button type="submit" style="background: var(--primary-cerulean); color: white; border: none; padding: 0.7rem 1.25rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">{{ $client ? 'Save' : 'Add client' }}</button>
        </form>
    </div>
@endsection

@extends('layouts.app')

@section('page_title', $client ? 'Edit client' : 'New client')

@section('content')
    <div style="margin-bottom: 1.25rem;">
        <a href="{{ $client ? '/pro/engineer/clients/'.$client->id : '/pro/engineer/clients' }}" style="color: var(--text-muted); text-decoration: none; font-weight: 600;">← Back</a>
        <h1 style="margin: 0.5rem 0 0; color: var(--primary-navy); font-size: 1.5rem;">{{ $client ? 'Edit client' : 'New client' }}</h1>
    </div>

    @if($errors->any())
        <div style="background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
            <ul style="margin: 0; padding-left: 1.1rem;">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $client ? '/pro/engineer/clients/'.$client->id : '/pro/engineer/clients' }}" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.35rem; box-shadow: var(--shadow-sm); max-width: 720px;">
        @csrf
        @if($client) @method('PUT') @endif

        <div style="display: grid; gap: 0.85rem;">
            <div>
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Full name *</label>
                <input type="text" name="name" value="{{ old('name', $client->name ?? '') }}" required style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.85rem;">
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">ID card</label>
                    <input type="text" name="id_card" value="{{ old('id_card', $client->id_card ?? '') }}" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Mobile</label>
                    <input type="text" name="phone" value="{{ old('phone', $client->phone ?? '') }}" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Email</label>
                    <input type="email" name="email" value="{{ old('email', $client->email ?? '') }}" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
            </div>
            <div>
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Address</label>
                <textarea name="address" rows="2" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ old('address', $client->address ?? '') }}</textarea>
            </div>
            <div>
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Locality</label>
                <input type="text" name="locality" value="{{ old('locality', $client->locality ?? '') }}" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <div>
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Link billing client (optional)</label>
                <select name="billing_client_id" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    <option value="">None</option>
                    @foreach($billingClients as $bc)
                        <option value="{{ $bc->id }}" @selected(old('billing_client_id', $client->billing_client_id ?? '') == $bc->id)>{{ $bc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Notes</label>
                <textarea name="notes" rows="3" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ old('notes', $client->notes ?? '') }}</textarea>
            </div>
            <button type="submit" style="background: var(--primary-cerulean); color: white; border: none; padding: 0.75rem 1.1rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer; width: fit-content;">Save client</button>
        </div>
    </form>
@endsection

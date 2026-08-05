@extends('layouts.app')

@section('page_title', $equipment ? 'Edit equipment' : 'Register equipment')

@section('content')
    <div style="max-width: 720px;">
        <a href="{{ $equipment ? '/pro/engineer/equipment/'.$equipment->id : '/pro/engineer/equipment' }}" style="color: var(--primary-cerulean); font-weight: 600; text-decoration: none; font-size: 0.9rem;">← Back</a>
        <h1 style="margin: 0.4rem 0 0.25rem; color: var(--primary-navy); font-size: 1.5rem;">{{ $equipment ? 'Edit equipment' : 'Register equipment' }}</h1>
        <p style="margin: 0 0 1.25rem; color: var(--text-muted); font-size: 0.95rem;">Tied to a client — not a project — so the asset can move between sites.</p>

        @if($errors->any())
            <div style="background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem; border: 1px solid #fecaca;">
                @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif

        <form method="POST" action="{{ $equipment ? '/pro/engineer/equipment/'.$equipment->id : '/pro/engineer/equipment' }}" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); padding: 1.35rem;">
            @csrf
            @if($equipment) @method('PUT') @endif

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; color: var(--primary-navy); font-size: 0.9rem;">Client</label>
                <select name="client_id" required style="width: 100%; padding: 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font: inherit;">
                    <option value="">Select client…</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" @selected(old('client_id', $equipment->client_id ?? '') == $client->id)>{{ $client->name }}</option>
                    @endforeach
                </select>
                @if($clients->isEmpty())
                    <div style="font-size: 0.8rem; color: #92400e; margin-top: 0.35rem;">No clients yet — <a href="/clients/create" style="color: var(--primary-cerulean); font-weight: 600;">add a client</a> first.</div>
                @endif
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; color: var(--primary-navy); font-size: 0.9rem;">Category</label>
                    <select name="category" required style="width: 100%; padding: 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font: inherit;">
                        @foreach($categories as $key => $label)
                            <option value="{{ $key }}" @selected(old('category', $equipment->category ?? 'forklift') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; color: var(--primary-navy); font-size: 0.9rem;">Status</label>
                    <select name="status" required style="width: 100%; padding: 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font: inherit;">
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}" @selected(old('status', $equipment->status ?? 'active') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; color: var(--primary-navy); font-size: 0.9rem;">Name / description</label>
                <input type="text" name="name" required maxlength="255" value="{{ old('name', $equipment->name ?? '') }}" placeholder="e.g. Toyota 8FG25 — warehouse forklift" style="width: 100%; padding: 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font: inherit;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; color: var(--primary-navy); font-size: 0.9rem;">Make</label>
                    <input type="text" name="make" value="{{ old('make', $equipment->make ?? '') }}" style="width: 100%; padding: 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font: inherit;">
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; color: var(--primary-navy); font-size: 0.9rem;">Model</label>
                    <input type="text" name="model" value="{{ old('model', $equipment->model ?? '') }}" style="width: 100%; padding: 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font: inherit;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; color: var(--primary-navy); font-size: 0.9rem;">Serial / plant no.</label>
                    <input type="text" name="serial_number" value="{{ old('serial_number', $equipment->serial_number ?? '') }}" style="width: 100%; padding: 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font: inherit;">
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; color: var(--primary-navy); font-size: 0.9rem;">Capacity / rating</label>
                    <input type="text" name="capacity_rating" value="{{ old('capacity_rating', $equipment->capacity_rating ?? '') }}" placeholder="e.g. 2500 kg" style="width: 100%; padding: 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font: inherit;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; color: var(--primary-navy); font-size: 0.9rem;">Year of manufacture</label>
                    <input type="number" name="year_of_manufacture" min="1950" max="{{ date('Y') + 1 }}" value="{{ old('year_of_manufacture', $equipment->year_of_manufacture ?? '') }}" style="width: 100%; padding: 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font: inherit;">
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; color: var(--primary-navy); font-size: 0.9rem;">Current location / site</label>
                    <input type="text" name="site_location" value="{{ old('site_location', $equipment->site_location ?? '') }}" placeholder="Can change when the asset moves" style="width: 100%; padding: 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font: inherit;">
                </div>
            </div>

            @if($equipment)
                <div style="margin-bottom: 1rem; font-size: 0.85rem; color: var(--text-muted);">
                    System asset code: <strong style="color: var(--primary-navy);">{{ $equipment->asset_code }}</strong> (assigned on create)
                </div>
            @endif

            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; color: var(--primary-navy); font-size: 0.9rem;">Notes</label>
                <textarea name="notes" rows="3" style="width: 100%; padding: 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font: inherit; resize: vertical;">{{ old('notes', $equipment->notes ?? '') }}</textarea>
            </div>

            <button type="submit" style="background: var(--primary-cerulean); color: white; border: none; padding: 0.7rem 1.25rem; border-radius: var(--radius-md); font-weight: 600; cursor: pointer;">
                {{ $equipment ? 'Save changes' : 'Register equipment' }}
            </button>
        </form>
    </div>

    <style>
        @media (max-width: 700px) {
            div[style*="grid-template-columns: 1fr 1fr"] { display: flex !important; flex-direction: column !important; }
        }
    </style>
@endsection

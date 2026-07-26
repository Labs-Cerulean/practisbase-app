@extends('layouts.app')

@section('page_title', 'Patient')

@section('content')
    <a href="/pro/medical/patients" style="color: var(--text-muted); font-weight: 600; text-decoration: none; font-size: 0.85rem;">&larr; Patients</a>
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin: 0.75rem 0 1.25rem;">
        <div>
            <h1 style="margin: 0; color: var(--primary-navy);">{{ $payload['display_name'] ?? 'Patient' }}</h1>
            <div style="font-size: 0.85rem; color: var(--text-muted);">{{ $patient->public_ref }}</div>
        </div>
        <a href="/pro/medical/patients/{{ $patient->id }}/entries/create" style="background: var(--primary-cerulean); color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">+ Clinical entry</a>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">{{ session('success') }}</div>
    @endif

    <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.25rem; margin-bottom: 1.25rem; box-shadow: var(--shadow-sm);">
        <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">Date of birth</div>
        <div style="margin-bottom: 0.75rem;">{{ !empty($payload['date_of_birth']) ? \Illuminate\Support\Carbon::parse($payload['date_of_birth'])->format('d M Y') : '—' }}</div>
        <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">Notes</div>
        <div style="white-space: pre-wrap;">{{ $payload['notes'] ?: '—' }}</div>
    </div>

    <h3 style="color: var(--primary-navy);">Clinical entries</h3>
    @if($entries->isEmpty())
        <p style="color: var(--text-muted);">No journal / prescription / referral entries yet.</p>
    @else
        <div style="display: grid; gap: 0.75rem;">
            @foreach($entries as $entry)
                <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 1rem; box-shadow: var(--shadow-sm);">
                    <div style="display: flex; justify-content: space-between; gap: 1rem;">
                        <strong style="color: var(--primary-navy);">{{ $entry['title'] }}</strong>
                        <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted);">{{ $entry['type_label'] }} · {{ $entry['model']->entry_date->format('d M Y') }}</span>
                    </div>
                    <div style="margin-top: 0.5rem; color: var(--text-main); white-space: pre-wrap; font-size: 0.9rem;">{{ $entry['body'] }}</div>
                </div>
            @endforeach
        </div>
    @endif
@endsection

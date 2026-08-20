@extends('layouts.app')

@section('page_title', $pa->displayLabel())

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem;">
        <div>
            <a href="/pro/architect/projects/{{ $project->id }}" style="color: var(--text-muted); text-decoration: none; font-weight: 600;">← {{ $project->name }}</a>
            <h1 style="margin: 0.4rem 0 0.25rem; color: var(--primary-navy); font-size: 1.5rem;">{{ $pa->displayLabel() }}</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">
                {{ $pa->title ?: 'No title' }} · {{ \App\Models\ArchitectPaApplication::statusLabelFor($pa->status) }}
                @if($project->client) · {{ $project->client->name }} @endif
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            @if($eappsUrl)
                <a href="{{ $eappsUrl }}" target="_blank" rel="noopener noreferrer" style="background: #1e3a5f; color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">Open eApps ↗</a>
            @endif
            <a href="{{ $mapServerUrl }}" target="_blank" rel="noopener noreferrer" style="background: white; border: 1px solid var(--border-light); color: var(--primary-navy); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">PA MapServer ↗</a>
            <a href="/pro/architect/pa/{{ $pa->id }}/edit" style="background: white; border: 1px solid var(--border-light); color: var(--primary-navy); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">Edit</a>
            <a href="/pro/architect/documents/create?pa_id={{ $pa->id }}" style="background: #3f6212; color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">+ Document</a>
        </div>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">{{ session('success') }}</div>
    @endif

    @if(! $pa->canonicalNumber())
        <div style="background: #eff6ff; color: #1e3a8a; padding: 0.85rem 1rem; border-radius: var(--radius-md); margin-bottom: 1rem; font-size: 0.9rem;">
            Case number still pending. Edit this record when the official number is issued.
        </div>
    @elseif(! $eappsUrl)
        <div style="background: #fffbeb; color: #92400e; padding: 0.85rem 1rem; border-radius: var(--radius-md); margin-bottom: 1rem; font-size: 0.9rem;">
            Case number could not be normalised for eApps. Edit and re-save so the number is five digits (e.g. 00525).
        </div>
    @endif

    <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm); margin-bottom: 1rem;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 0.85rem; font-size: 0.88rem;">
            <div>
                <div style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Type</div>
                <div style="color: var(--primary-navy); font-weight: 650;">{{ $caseTypes[$pa->resolvedCaseType()] ?? $pa->resolvedCaseType() }}</div>
            </div>
            <div>
                <div style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Canonical</div>
                <div style="color: var(--primary-navy); font-weight: 650;">{{ $pa->canonicalNumber() ?: '—' }}</div>
            </div>
            <div>
                <div style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Status</div>
                <div style="color: var(--primary-navy); font-weight: 650;">{{ \App\Models\ArchitectPaApplication::statusLabelFor($pa->status) }}</div>
            </div>
            <div>
                <div style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Site</div>
                <div style="color: var(--primary-navy);">{{ $project->siteAddressLine() ?: 'Not set' }}</div>
            </div>
        </div>
        @if($pa->works_commencement_date)
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.75rem;">Works on site from {{ $pa->works_commencement_date->format('d M Y') }}</div>
        @endif
    </div>

    <div style="display: flex; justify-content: space-between; align-items: baseline; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 0.75rem;">
        <h2 style="margin: 0; font-size: 1.05rem; color: var(--primary-navy);">Case library</h2>
        <a href="/pro/architect/documents/create?pa_id={{ $pa->id }}" style="font-size: 0.82rem; font-weight: 600; color: #3f6212; text-decoration: none;">+ Upload</a>
    </div>
    @include('pro.architect.partials.document-library', [
        'documents' => $pa->documents,
        'emptyCopy' => 'No documents on this case yet.',
    ])
@endsection

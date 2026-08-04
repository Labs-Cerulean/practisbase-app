@extends('layouts.app')

@section('page_title', $client->name)

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem;">
        <div>
            <a href="/pro/engineer/clients" style="color: var(--text-muted); text-decoration: none; font-weight: 600;">← Clients</a>
            <h1 style="margin: 0.4rem 0 0.25rem; color: var(--primary-navy); font-size: 1.5rem;">{{ $client->name }}</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">
                {{ $client->displayAddress() ?: 'No address yet' }}
                @if($client->phone) · {{ $client->phone }} @endif
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="/pro/engineer/clients/{{ $client->id }}/edit" style="background: white; border: 1px solid var(--border-light); color: var(--primary-navy); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">Edit</a>
            <a href="/pro/engineer/projects/create?client_id={{ $client->id }}" style="background: var(--primary-cerulean); color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">+ Project</a>
            <a href="/pro/engineer/documents/create?client_id={{ $client->id }}" style="background: white; border: 1px solid var(--border-light); color: var(--primary-navy); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">+ Document</a>
        </div>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">{{ session('success') }}</div>
    @endif

    <div style="display: grid; grid-template-columns: 1.4fr 1fr; gap: 1.25rem;" class="eng-split">
        <div>
            <h2 style="margin: 0 0 0.75rem; font-size: 1.05rem; color: var(--primary-navy);">Projects</h2>
            @if($client->projects->isEmpty())
                <div style="background: white; border: 1px dashed var(--border-light); border-radius: var(--radius-md); padding: 1.25rem; color: var(--text-muted);">No projects for this client yet.</div>
            @else
                <div style="display: grid; gap: 0.65rem;">
                    @foreach($client->projects as $project)
                        <a href="/pro/engineer/projects/{{ $project->id }}" style="display: block; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.95rem 1.1rem; text-decoration: none; box-shadow: var(--shadow-sm);">
                            <div style="font-weight: 700; color: var(--primary-navy);">{{ $project->name }}</div>
                            <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.2rem;">
                                {{ $disciplines[$project->discipline] ?? $project->discipline }}
                                · {{ $phases[$project->phase] ?? $project->phase }}
                                · {{ $project->pa_applications_count }} PA
                                @if($project->site_locality) · {{ $project->site_locality }} @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
        <div>
            <h2 style="margin: 0 0 0.75rem; font-size: 1.05rem; color: var(--primary-navy);">Client documents</h2>
            @if($client->documents->isEmpty())
                <div style="background: white; border: 1px dashed var(--border-light); border-radius: var(--radius-md); padding: 1.25rem; color: var(--text-muted);">No client-level documents yet.</div>
            @else
                <div style="display: grid; gap: 0.5rem;">
                    @foreach($client->documents as $doc)
                        <a href="/pro/engineer/documents/{{ $doc->id }}" style="display: block; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.75rem 0.9rem; text-decoration: none;">
                            <div style="font-weight: 650; color: var(--primary-navy); font-size: 0.92rem;">{{ $doc->title }}</div>
                            <div style="font-size: 0.78rem; color: var(--text-muted);">Rev {{ $doc->current_revision }} · {{ $doc->status }}</div>
                        </a>
                    @endforeach
                </div>
            @endif
            @if($client->notes)
                <div style="margin-top: 1rem; background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.9rem; font-size: 0.88rem; white-space: pre-wrap;">{{ $client->notes }}</div>
            @endif
        </div>
    </div>

    <style>
        @media (max-width: 800px) { .eng-split { grid-template-columns: 1fr !important; } }
    </style>
@endsection

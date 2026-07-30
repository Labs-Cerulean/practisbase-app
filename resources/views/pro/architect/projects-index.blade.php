@extends('layouts.app')

@section('page_title', 'Projects')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem;">
        <div>
            <h1 style="margin: 0 0 0.25rem; color: var(--primary-navy); font-size: 1.5rem;">Projects</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">Search by project, client, locality or PA number.</p>
        </div>
        <a href="/pro/architect/projects/create" style="background: #3f6212; color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">+ Project</a>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">{{ session('success') }}</div>
    @endif

    <form method="GET" action="/pro/architect/projects" style="margin-bottom: 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <input type="search" name="q" value="{{ $q }}" placeholder="Search projects, PA, locality…"
               style="flex: 1; min-width: 220px; padding: 0.65rem 0.85rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
        <button type="submit" style="background: var(--primary-navy); color: white; border: none; padding: 0.65rem 1rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Search</button>
    </form>

    @if($projects->isEmpty())
        <div style="padding: 3rem; border: 2px dashed var(--border-light); border-radius: var(--radius-md); text-align: center; background: white;">
            <p style="color: var(--text-muted);">No projects yet. Start from a client, then add a project.</p>
        </div>
    @else
        <div style="display: grid; gap: 0.75rem;">
            @foreach($projects as $project)
                <a href="/pro/architect/projects/{{ $project->id }}" style="display: block; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 1rem 1.25rem; text-decoration: none; box-shadow: var(--shadow-sm);">
                    <div style="font-weight: 700; color: var(--primary-navy);">{{ $project->name }}</div>
                    <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.25rem;">
                        {{ $project->client->name ?? 'Unassigned client' }}
                        · {{ $phases[$project->phase] ?? $project->phase }}
                        · {{ $project->pa_applications_count }} PA
                        @if($project->reference_code) · {{ $project->reference_code }} @endif
                    </div>
                    @if($project->siteAddressLine())
                        <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.35rem;">{{ $project->siteAddressLine() }}</div>
                    @endif
                </a>
            @endforeach
        </div>
    @endif
@endsection

@extends('layouts.app')

@section('page_title', 'Projects')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; gap: 1rem; flex-wrap: wrap;">
        <div>
            <h1 style="margin: 0 0 0.25rem; color: var(--primary-navy); font-size: 1.5rem;">Engineering projects</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">
                @if($showArchived)
                    Archived projects. Open one to restore it via Edit → Status.
                @else
                    Client → Project → PA (optional). Search by client, site, or PA number.
                @endif
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            @if($showArchived)
                <a href="/pro/engineer/projects" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">Active register</a>
            @else
                <a href="/pro/engineer/projects?archived=1" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">Archived</a>
            @endif
            <a href="/pro/engineer/projects/create" style="background: var(--primary-cerulean); color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">+ Project</a>
        </div>
    </div>
    @if(session('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">{{ session('success') }}</div>
    @endif

    @unless($showArchived)
        <form method="GET" action="/pro/engineer/projects" style="margin-bottom: 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <input type="search" name="q" value="{{ $q }}" placeholder="Search projects, clients, PA…"
                   style="flex: 1; min-width: 200px; padding: 0.65rem 0.85rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            <button type="submit" style="background: var(--primary-navy); color: white; border: none; padding: 0.65rem 1rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Search</button>
        </form>
    @endunless

    @if($projects->isEmpty())
        <div style="padding: 3rem; border: 2px dashed var(--border-light); border-radius: var(--radius-md); text-align: center; background: white;">
            <p style="color: var(--text-muted); margin: 0 0 1rem;">
                @if($showArchived)
                    No archived projects.
                @elseif($q !== '')
                    No projects match that search.
                @else
                    No engineering projects yet. Add a client first if you have not.
                @endif
            </p>
            @unless($showArchived)
                <a href="/pro/engineer/clients/create" style="display: inline-block; margin-right: 0.5rem; background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">+ Client</a>
                <a href="/pro/engineer/projects/create" style="display: inline-block; background: var(--primary-cerulean); color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">+ Project</a>
            @endunless
        </div>
    @else
        <div style="display: grid; gap: 0.75rem;">
            @foreach($projects as $project)
                <a href="/pro/engineer/projects/{{ $project->id }}" style="display: block; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 1rem 1.25rem; box-shadow: var(--shadow-sm); text-decoration: none;">
                    <div style="display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                        <div style="font-weight: 700; color: var(--primary-navy);">{{ $project->name }}</div>
                        <div style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted);">{{ $statuses[$project->status] ?? $project->status }}</div>
                    </div>
                    <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.25rem;">
                        {{ $project->client->name ?? 'No client linked' }}
                        · {{ $disciplines[$project->discipline] ?? $project->discipline }}
                        · {{ $phases[$project->phase] ?? $project->phase }}
                        · {{ $project->pa_applications_count }} PA
                        @if($project->reference_code) · {{ $project->reference_code }} @endif
                    </div>
                </a>
            @endforeach
        </div>
    @endif
@endsection

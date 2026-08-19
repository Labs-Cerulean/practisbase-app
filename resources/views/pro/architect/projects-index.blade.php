@extends('layouts.app')

@section('page_title', 'Projects')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem;">
        <div>
            <h1 style="margin: 0 0 0.25rem; color: var(--primary-navy); font-size: 1.5rem;">Portfolio</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">Projects, PA/PC/DN cases, and sites on the map.</p>
        </div>
        <a href="/pro/architect/projects/create" style="background: #3f6212; color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">+ Project</a>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">{{ session('success') }}</div>
    @endif

    <form method="GET" action="/pro/architect/projects" style="margin-bottom: 1rem; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 0.9rem 1rem; box-shadow: var(--shadow-sm);">
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 0.65rem;">
            <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Search projects, PA, locality…"
                   style="flex: 1; min-width: 220px; padding: 0.65rem 0.85rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            <button type="submit" style="background: var(--primary-navy); color: white; border: none; padding: 0.65rem 1rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Filter</button>
            @if(collect($filters)->filter(fn ($v) => $v !== '' && $v !== null)->isNotEmpty())
                <a href="/pro/architect/projects" style="padding: 0.65rem 0.9rem; color: var(--text-muted); font-weight: 600; text-decoration: none; font-size: 0.85rem;">Clear</a>
            @endif
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.55rem;">
            <select name="locality" style="padding: 0.55rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                <option value="">All localities</option>
                @foreach($localities as $loc)
                    <option value="{{ $loc }}" @selected($filters['locality'] === $loc)>{{ $loc }}</option>
                @endforeach
            </select>
            <select name="client_id" style="padding: 0.55rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                <option value="">All clients</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" @selected((string) $filters['client_id'] === (string) $client->id)>{{ $client->name }}</option>
                @endforeach
            </select>
            <select name="status" style="padding: 0.55rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                <option value="">All project statuses</option>
                @foreach($statuses as $key => $label)
                    <option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="pa_status" style="padding: 0.55rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                <option value="">All case statuses</option>
                @foreach($paStatuses as $key => $label)
                    <option value="{{ $key }}" @selected($filters['pa_status'] === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="case_type" style="padding: 0.55rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                <option value="">All case types</option>
                @foreach($caseTypes as $key => $label)
                    <option value="{{ $key }}" @selected($filters['case_type'] === $key)>{{ $key }}</option>
                @endforeach
            </select>
        </div>
    </form>

    <div style="margin-bottom: 1.15rem;">
        @include('pro.architect.partials.portfolio-map', [
            'mapId' => 'arch-portfolio-map',
            'pins' => $mapPins,
            'height' => '420px',
            'mapServerUrl' => $mapServerUrl,
        ])
    </div>

    @if($projects->isEmpty())
        <div style="padding: 3rem; border: 2px dashed var(--border-light); border-radius: var(--radius-md); text-align: center; background: white;">
            <p style="color: var(--text-muted);">No projects match. Start from a client, then add a project and pin the site.</p>
        </div>
    @else
        <div style="display: grid; gap: 0.75rem;">
            @foreach($projects as $project)
                <a href="/pro/architect/projects/{{ $project->id }}" style="display: block; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 1rem 1.25rem; text-decoration: none; box-shadow: var(--shadow-sm);">
                    <div style="display: flex; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap;">
                        <div style="font-weight: 700; color: var(--primary-navy);">{{ $project->name }}</div>
                        <div style="font-size: 0.78rem; color: var(--text-muted);">
                            {{ $statuses[$project->status] ?? $project->status }}
                            @if($project->hasMapPin()) · Mapped @endif
                        </div>
                    </div>
                    <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.25rem;">
                        {{ $project->client->name ?? 'Unassigned client' }}
                        · {{ $phases[$project->phase] ?? $project->phase }}
                        · {{ $project->pa_applications_count }} case{{ $project->pa_applications_count === 1 ? '' : 's' }}
                        @if($project->reference_code) · {{ $project->reference_code }} @endif
                    </div>
                    @if($project->siteAddressLine())
                        <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.35rem;">{{ $project->siteAddressLine() }}</div>
                    @endif
                    @if($project->paApplications->isNotEmpty())
                        <div style="margin-top: 0.45rem; display: flex; flex-wrap: wrap; gap: 0.35rem;">
                            @foreach($project->paApplications->take(4) as $pa)
                                <span style="font-size: 0.72rem; padding: 0.2rem 0.45rem; border-radius: 4px; background: #f1f5f9; color: var(--primary-navy);">
                                    {{ $pa->canonicalNumber() ?: ($pa->resolvedCaseType().' pending') }} · {{ $pa->statusLabel() }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </a>
            @endforeach
        </div>
    @endif
@endsection

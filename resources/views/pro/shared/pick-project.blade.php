@extends('layouts.app')

@section('page_title', 'Choose a project')

@section('content')
    <div style="max-width: 640px; margin: 0 auto;">
        <a href="{{ $backHref }}" style="color: var(--text-muted); text-decoration: none; font-weight: 600;">← Back</a>
        <h1 style="margin: 0.5rem 0 0.35rem; color: var(--primary-navy); font-size: 1.5rem;">{{ $heading }}</h1>
        <p style="margin: 0 0 1.25rem; color: var(--text-muted); font-size: 0.9rem;">{{ $lead }}</p>

        @if($projects->isEmpty())
            <div style="padding: 2rem; border: 2px dashed var(--border-light); border-radius: var(--radius-md); text-align: center; background: white;">
                <p style="color: var(--text-muted); margin: 0 0 1rem;">Create a project first — documents live under Client → Project.</p>
                <a href="{{ $createProjectHref }}" style="color: {{ $accent ?? 'var(--primary-navy)' }}; font-weight: 700; text-decoration: none;">+ New project</a>
            </div>
        @else
            <div style="display: grid; gap: 0.55rem;">
                @foreach($projects as $project)
                    <a href="{{ $continueBase }}{{ str_contains($continueBase, '?') ? '&' : '?' }}project_id={{ $project->id }}"
                       style="display: block; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.95rem 1.1rem; text-decoration: none; box-shadow: var(--shadow-sm);">
                        <div style="font-weight: 700; color: var(--primary-navy);">{{ $project->name }}</div>
                        <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.2rem;">
                            @if($project->client){{ $project->client->name }} · @endif
                            {{ $project->reference_code ?: 'P'.$project->id }}
                            @if(method_exists($project, 'siteAddressLine') && $project->siteAddressLine())
                                · {{ \Illuminate\Support\Str::limit($project->siteAddressLine(), 48) }}
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
@endsection

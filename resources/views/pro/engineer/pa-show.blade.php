@extends('layouts.app')

@section('page_title', $pa->displayLabel())

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem;">
        <div>
            <a href="/pro/engineer/projects/{{ $project->id }}" style="color: var(--text-muted); text-decoration: none; font-weight: 600;">← {{ $project->name }}</a>
            <h1 style="margin: 0.4rem 0 0.25rem; color: var(--primary-navy); font-size: 1.5rem;">{{ $pa->displayLabel() }}</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">
                {{ $pa->title ?: 'No title' }} · {{ $statuses[$pa->status] ?? $pa->status }}
                @if($project->client) · {{ $project->client->name }} @endif
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="/pro/engineer/pa/{{ $pa->id }}/edit" style="background: white; border: 1px solid var(--border-light); color: var(--primary-navy); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">Edit</a>
        </div>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">{{ session('success') }}</div>
    @endif

    @if(! filled($pa->pa_number))
        <div style="background: #eff6ff; color: #1e3a8a; padding: 0.85rem 1rem; border-radius: var(--radius-md); margin-bottom: 1rem; font-size: 0.9rem;">
            PA number still pending. Edit this record when the official number is issued.
        </div>
    @endif

    <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm);">
        <div style="font-size: 0.8rem; color: var(--text-muted);">
            Site: {{ $project->siteAddressLine() ?: 'Not set' }}
            @if($pa->works_commencement_date) · Works from {{ $pa->works_commencement_date->format('d M Y') }} @endif
        </div>
        @if($pa->notes)
            <div style="margin-top: 0.85rem; font-size: 0.92rem; white-space: pre-wrap; color: var(--primary-navy);">{{ $pa->notes }}</div>
        @endif
    </div>
@endsection

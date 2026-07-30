@extends('layouts.app')

@section('page_title', 'Documents')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem;">
        <div>
            <h1 style="margin: 0 0 0.25rem; color: var(--primary-navy); font-size: 1.5rem;">Documents</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">Search across client, project and PA libraries. Open any revision from the document card.</p>
        </div>
        <a href="/pro/architect/documents/create" style="background: #3f6212; color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">+ Upload</a>
    </div>

    <form method="GET" action="/pro/architect/documents" style="margin-bottom: 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <input type="search" name="q" value="{{ $q }}" placeholder="Search title, code, type…"
               style="flex: 1; min-width: 200px; padding: 0.65rem 0.85rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
        <select name="scope" style="padding: 0.65rem 0.85rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            <option value="">All levels</option>
            <option value="client" @selected($scope === 'client')>Client</option>
            <option value="project" @selected($scope === 'project')>Project</option>
            <option value="pa" @selected($scope === 'pa')>PA</option>
        </select>
        <button type="submit" style="background: var(--primary-navy); color: white; border: none; padding: 0.65rem 1rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Search</button>
    </form>

    @if($documents->isEmpty())
        <div style="padding: 3rem; border: 2px dashed var(--border-light); border-radius: var(--radius-md); text-align: center; background: white; color: var(--text-muted);">No documents found.</div>
    @else
        <div style="display: grid; gap: 0.65rem;">
            @foreach($documents as $doc)
                <a href="/pro/architect/documents/{{ $doc->id }}" style="display: block; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.95rem 1.1rem; text-decoration: none; box-shadow: var(--shadow-sm);">
                    <div style="display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                        <div>
                            <div style="font-weight: 700; color: var(--primary-navy);">{{ $doc->title }}</div>
                            <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.2rem;">
                                {{ $doc->scopeLabel() }}
                                @if($doc->paApplication) · {{ $doc->paApplication->pa_number }}
                                @elseif($doc->project) · {{ $doc->project->name }}
                                @elseif($doc->client) · {{ $doc->client->name }}
                                @endif
                                · {{ $categories[$doc->category] ?? $doc->category }}
                            </div>
                        </div>
                        <div style="font-size: 0.82rem; font-weight: 700; color: #3f6212;">Rev {{ $doc->current_revision }} · {{ $statuses[$doc->status] ?? $doc->status }}</div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
@endsection

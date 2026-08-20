@extends('layouts.app')

@section('page_title', 'Documents')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem;">
        <div>
            <h1 style="margin: 0 0 0.25rem; color: var(--primary-navy); font-size: 1.5rem;">Documents</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">Practice filing cabinet — client, project, and PA libraries with revision history.</p>
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
            <option value="pa" @selected($scope === 'pa')>PA case</option>
        </select>
        <select name="doc_type" style="padding: 0.65rem 0.85rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            <option value="">All types</option>
            @foreach($docTypes as $key => $label)
                <option value="{{ $key }}" @selected(($docType ?? '') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" style="background: var(--primary-navy); color: white; border: none; padding: 0.65rem 1rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Search</button>
    </form>

    @if($documents->isEmpty())
        <div style="padding: 3rem; border: 2px dashed var(--border-light); border-radius: var(--radius-md); text-align: center; background: white; color: var(--text-muted);">No documents found.</div>
    @else
        @include('pro.architect.partials.document-library', [
            'documents' => $documents,
            'statuses' => $statuses,
        ])
    @endif
@endsection

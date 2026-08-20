@extends('layouts.app')

@section('page_title', $document->title)

@section('content')
    @php
        $latest = $latestRevision ?? $document->revisions->first();
        $canView = $latest && $latest->isInlineViewable();
        $viewUrl = $canView ? '/pro/architect/documents/'.$document->id.'/revisions/'.$latest->id.'/view' : null;
    @endphp
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem;">
        <div>
            <a href="/pro/architect/documents" style="color: var(--text-muted); text-decoration: none; font-weight: 600;">← Documents</a>
            @if($document->project)
                <span style="color: var(--text-muted);"> · </span>
                <a href="/pro/architect/projects/{{ $document->project->id }}" style="color: var(--text-muted); text-decoration: none; font-weight: 600;">{{ $document->project->name }}</a>
            @endif
            <h1 style="margin: 0.4rem 0 0.25rem; color: var(--primary-navy); font-size: 1.5rem;">{{ $document->title }}</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">
                {{ $document->scopeLabel() }}
                @if($document->paApplication) · <a href="/pro/architect/pa/{{ $document->paApplication->id }}" style="color: #3f6212; text-decoration: none; font-weight: 650;">{{ $document->paApplication->canonicalNumber() ?: 'PA case' }}</a>
                @elseif($document->project) · {{ $document->project->name }}
                @elseif($document->client) · {{ $document->client->name }}
                @endif
                · {{ $document->typeLabel() }}
                · Current Rev {{ $document->current_revision }}
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            @if($viewUrl)
                <a href="{{ $viewUrl }}" target="_blank" rel="noopener" style="background: #3f6212; color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">View file</a>
            @endif
            @if($latest)
                <a href="/pro/architect/documents/{{ $document->id }}/revisions/{{ $latest->id }}/download" style="background: white; border: 1px solid var(--border-light); color: var(--primary-navy); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">Download</a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">{{ session('success') }}</div>
    @endif

    @if($viewUrl)
        <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); margin-bottom: 1.25rem; overflow: hidden;">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; padding: 0.65rem 0.9rem; border-bottom: 1px solid var(--border-light);">
                <div style="font-size: 0.8rem; font-weight: 700; color: var(--primary-navy);">In-app reader · Rev {{ $latest->revision_no }}</div>
                <a href="{{ $viewUrl }}" target="_blank" rel="noopener" style="font-size: 0.75rem; font-weight: 650; color: var(--text-muted); text-decoration: none;">Open in new tab</a>
            </div>
            <iframe src="{{ $viewUrl }}" title="Document preview" style="width: 100%; height: min(70vh, 720px); border: 0; background: #f8fafc;"></iframe>
        </section>
    @endif

    <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 1.25rem;" class="arch-split">
        <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm);">
            <h2 style="margin: 0 0 0.75rem; font-size: 1.05rem; color: var(--primary-navy);">Revision history</h2>
            @forelse($document->revisions as $rev)
                <div style="display: flex; justify-content: space-between; gap: 1rem; padding: 0.7rem 0; border-bottom: 1px dashed var(--border-light);">
                    <div>
                        <div style="font-weight: 700; color: var(--primary-navy);">Rev {{ $rev->revision_no }}</div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">
                            {{ $rev->original_name }}
                            @if($rev->change_note) · {{ $rev->change_note }} @endif
                            · {{ $rev->created_at?->format('d M Y H:i') }}
                        </div>
                    </div>
                    <div style="display: flex; gap: 0.55rem; align-items: center;">
                        @if($rev->isInlineViewable())
                            <a href="/pro/architect/documents/{{ $document->id }}/revisions/{{ $rev->id }}/view" target="_blank" rel="noopener" style="font-weight: 700; color: #3f6212; text-decoration: none; font-size: 0.85rem;">View</a>
                        @endif
                        <a href="/pro/architect/documents/{{ $document->id }}/revisions/{{ $rev->id }}/download" style="font-weight: 650; color: var(--text-muted); text-decoration: none; font-size: 0.85rem;">Download</a>
                    </div>
                </div>
            @empty
                <p style="color: var(--text-muted);">No files yet.</p>
            @endforelse

            <form method="POST" action="/pro/architect/documents/{{ $document->id }}/revisions" enctype="multipart/form-data" style="margin-top: 1rem; display: grid; gap: 0.65rem; border-top: 1px solid #e2e8f0; padding-top: 0.9rem;">
                @csrf
                <div style="font-size: 0.8rem; font-weight: 700; color: var(--primary-navy);">Upload new revision</div>
                <input type="file" name="file" required>
                <input type="text" name="change_note" placeholder="What changed?" style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                <select name="status" style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    @foreach($statuses as $key => $label)
                        <option value="{{ $key }}" @selected($document->status === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" style="background: #3f6212; color: white; border: none; padding: 0.6rem 0.9rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer; width: fit-content;">Upload revision</button>
            </form>
        </section>

        <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm);">
            <h2 style="margin: 0 0 0.75rem; font-size: 1.05rem; color: var(--primary-navy);">Details</h2>
            <form method="POST" action="/pro/architect/documents/{{ $document->id }}" style="display: grid; gap: 0.65rem;">
                @csrf
                @method('PUT')
                <div>
                    <label style="display: block; font-size: 0.72rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.2rem;">Title</label>
                    <input type="text" name="title" value="{{ old('title', $document->title) }}" required style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-size: 0.72rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.2rem;">Type</label>
                    <select name="doc_type" required style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        @foreach($docTypes as $key => $label)
                            <option value="{{ $key }}" @selected(old('doc_type', array_key_exists($document->doc_type, $docTypes) ? $document->doc_type : 'other') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 0.72rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.2rem;">Doc code</label>
                    <input type="text" name="doc_code" value="{{ old('doc_code', $document->doc_code) }}" placeholder="Doc code" style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div>
                    <label style="display: block; font-size: 0.72rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.2rem;">Status</label>
                    <select name="status" style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}" @selected(old('status', $document->status) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @if($document->architect_project_id)
                    <div>
                        <label style="display: block; font-size: 0.72rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.2rem;">Attach to PA case</label>
                        <select name="architect_pa_application_id" style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                            <option value="">Project library only</option>
                            @foreach($projectPas as $pa)
                                <option value="{{ $pa->id }}" @selected((string) old('architect_pa_application_id', $document->architect_pa_application_id) === (string) $pa->id)>
                                    {{ $pa->canonicalNumber() ?: ('Case #'.$pa->id) }} · {{ $pa->statusLabel() }}
                                </option>
                            @endforeach
                        </select>
                        <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 0.25rem;">Link this file to a PA/PC/DN on the same project. Revisions stay intact.</div>
                    </div>
                @endif
                <div>
                    <label style="display: block; font-size: 0.72rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.2rem;">Notes</label>
                    <textarea name="notes" rows="3" style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ old('notes', $document->notes) }}</textarea>
                </div>
                <button type="submit" style="background: var(--primary-navy); color: white; border: none; padding: 0.6rem 0.9rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer; width: fit-content;">Save details</button>
            </form>
        </section>
    </div>

    <style>
        @media (max-width: 800px) { .arch-split { grid-template-columns: 1fr !important; } }
    </style>
@endsection

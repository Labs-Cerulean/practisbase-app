@extends('layouts.app')

@section('page_title', $document->title)

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem;">
        <div>
            <a href="/pro/architect/documents" style="color: var(--text-muted); text-decoration: none; font-weight: 600;">← Documents</a>
            <h1 style="margin: 0.4rem 0 0.25rem; color: var(--primary-navy); font-size: 1.5rem;">{{ $document->title }}</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">
                {{ $document->scopeLabel() }}
                @if($document->paApplication) · {{ $document->paApplication->pa_number }}
                @elseif($document->project) · {{ $document->project->name }}
                @elseif($document->client) · {{ $document->client->name }}
                @endif
                · {{ $categories[$document->category] ?? $document->category }}
                · Current Rev {{ $document->current_revision }}
            </p>
        </div>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">{{ session('success') }}</div>
    @endif

    <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 1.25rem;" class="arch-split">
        <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm);">
            <h2 style="margin: 0 0 0.75rem; font-size: 1.05rem; color: var(--primary-navy);">Revisions</h2>
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
                    <a href="/pro/architect/documents/{{ $document->id }}/revisions/{{ $rev->id }}/download" style="font-weight: 700; color: #3f6212; text-decoration: none; font-size: 0.85rem;">Open</a>
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
                <input type="text" name="title" value="{{ old('title', $document->title) }}" required style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                <input type="text" name="doc_type" value="{{ old('doc_type', $document->doc_type) }}" required style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                <input type="text" name="doc_code" value="{{ old('doc_code', $document->doc_code) }}" placeholder="Doc code" style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                <select name="category" style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    @foreach($categories as $key => $label)
                        <option value="{{ $key }}" @selected(old('category', $document->category) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="status" style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    @foreach($statuses as $key => $label)
                        <option value="{{ $key }}" @selected(old('status', $document->status) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <textarea name="notes" rows="3" style="width: 100%; padding: 0.55rem 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ old('notes', $document->notes) }}</textarea>
                <button type="submit" style="background: var(--primary-navy); color: white; border: none; padding: 0.6rem 0.9rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer; width: fit-content;">Save details</button>
            </form>
        </section>
    </div>

    <style>
        @media (max-width: 800px) { .arch-split { grid-template-columns: 1fr !important; } }
    </style>
@endsection

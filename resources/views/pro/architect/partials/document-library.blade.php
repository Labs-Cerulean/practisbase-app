{{-- Filing-cabinet style document rows. Expects $documents collection. --}}
@php
    $statuses = $statuses ?? \App\Models\ArchitectDocument::STATUSES;
    $emptyCopy = $emptyCopy ?? 'No files in this library yet.';
@endphp
@if($documents->isEmpty())
    <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">{{ $emptyCopy }}</p>
@else
    <div style="display: grid; gap: 0.55rem;">
        @foreach($documents as $doc)
            @php
                $revs = $doc->relationLoaded('revisions') ? $doc->revisions : $doc->revisions()->orderByDesc('revision_no')->limit(3)->get();
                $latest = $revs->first();
            @endphp
            <div style="border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.75rem 0.9rem; background: #fff;">
                <div style="display: flex; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap; align-items: flex-start;">
                    <div style="min-width: 0;">
                        <a href="/pro/architect/documents/{{ $doc->id }}" style="font-weight: 700; color: var(--primary-navy); text-decoration: none;">{{ $doc->title }}</a>
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.2rem;">
                            <span style="display: inline-block; padding: 0.1rem 0.4rem; border-radius: 4px; background: #f1f5f9; color: var(--primary-navy); font-weight: 650;">{{ $doc->typeLabel() }}</span>
                            · Rev {{ $doc->current_revision }}
                            · {{ $statuses[$doc->status] ?? $doc->status }}
                            @if($doc->doc_code) · {{ $doc->doc_code }} @endif
                            @if($doc->paApplication)
                                · <a href="/pro/architect/pa/{{ $doc->paApplication->id }}" style="color: #3f6212; text-decoration: none; font-weight: 600;">{{ $doc->paApplication->canonicalNumber() ?: 'PA case' }}</a>
                            @endif
                        </div>
                    </div>
                    <div style="display: flex; gap: 0.45rem; flex-wrap: wrap; align-items: center;">
                        @if($latest)
                            @if($latest->isInlineViewable())
                                <a href="/pro/architect/documents/{{ $doc->id }}/revisions/{{ $latest->id }}/view" target="_blank" rel="noopener" style="font-size: 0.75rem; font-weight: 700; color: #3f6212; text-decoration: none;">View</a>
                            @endif
                            <a href="/pro/architect/documents/{{ $doc->id }}/revisions/{{ $latest->id }}/download" style="font-size: 0.75rem; font-weight: 650; color: var(--text-muted); text-decoration: none;">Download</a>
                        @endif
                        <a href="/pro/architect/documents/{{ $doc->id }}" style="font-size: 0.75rem; font-weight: 650; color: var(--primary-navy); text-decoration: none;">Details</a>
                    </div>
                </div>
                @if($revs->count() > 0)
                    <div style="margin-top: 0.55rem; padding-top: 0.45rem; border-top: 1px dashed #e2e8f0; display: grid; gap: 0.25rem;">
                        @foreach($revs->take(3) as $rev)
                            <div style="display: flex; justify-content: space-between; gap: 0.5rem; flex-wrap: wrap; font-size: 0.72rem; color: var(--text-muted);">
                                <span>
                                    Rev {{ $rev->revision_no }}
                                    @if($rev->change_note) · {{ $rev->change_note }} @endif
                                    · {{ $rev->created_at?->format('d M Y') }}
                                </span>
                                <span style="display: flex; gap: 0.4rem;">
                                    @if($rev->isInlineViewable())
                                        <a href="/pro/architect/documents/{{ $doc->id }}/revisions/{{ $rev->id }}/view" target="_blank" rel="noopener" style="color: #3f6212; text-decoration: none; font-weight: 650;">View</a>
                                    @endif
                                    <a href="/pro/architect/documents/{{ $doc->id }}/revisions/{{ $rev->id }}/download" style="color: var(--text-muted); text-decoration: none;">↓</a>
                                </span>
                            </div>
                        @endforeach
                        @if($doc->current_revision > 3)
                            <a href="/pro/architect/documents/{{ $doc->id }}" style="font-size: 0.7rem; color: var(--text-muted);">All revisions →</a>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@endif

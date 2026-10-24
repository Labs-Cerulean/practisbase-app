@if(! empty($clinicDeskReady))
<details style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 0.9rem 1.1rem; margin-bottom: 1.25rem; box-shadow: var(--shadow-sm);">
    <summary style="cursor: pointer; font-weight: 700; color: var(--primary-navy);">Reference library</summary>
    <div style="margin-top: 0.75rem; display: grid; gap: 0.4rem;">
        @forelse($libraryDocuments ?? [] as $document)
            <a href="/pro/medical/library/{{ $document['id'] }}/download" target="_blank" rel="noopener" style="color: var(--primary-cerulean); font-weight: 700; text-decoration: none;">{{ $document['title'] }}</a>
        @empty
            <span style="color: var(--text-muted); font-size: 0.9rem;">No reference files yet.</span>
        @endforelse
        <a href="/pro/medical/library" style="font-size: 0.85rem; font-weight: 700; color: var(--primary-navy); text-decoration: none; border-bottom: 1px dotted var(--primary-navy); justify-self: start;">Open library</a>
    </div>
</details>
@endif

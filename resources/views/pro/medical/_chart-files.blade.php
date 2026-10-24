<div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.1rem 1.2rem; margin-bottom: 1.25rem; box-shadow: var(--shadow-sm);">
    <div style="font-weight: 700; color: var(--primary-navy); margin-bottom: 0.65rem;">Files on this chart</div>
    @if(empty($chartFiles))
        <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">No files yet. Attach a scan on a note.</p>
    @else
        <div style="display: grid; gap: 0.4rem;">
            @foreach($chartFiles as $file)
                <div style="display: flex; justify-content: space-between; gap: 0.75rem; align-items: baseline; flex-wrap: wrap;">
                    <a href="/pro/medical/patients/{{ $patient->id }}/attachments/{{ $file['id'] }}/download" target="_blank" rel="noopener" style="color: var(--primary-cerulean); font-weight: 700; text-decoration: none;">{{ $file['name'] }}</a>
                    <span style="font-size: 0.8rem; color: var(--text-muted);">
                        {{ $file['date']?->format('d M Y') }} · {{ $file['type'] }}
                    </span>
                </div>
            @endforeach
        </div>
    @endif
</div>

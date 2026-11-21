<div id="{{ $anchor }}" class="expense-file">
    <div style="display: flex; justify-content: space-between; align-items: baseline; gap: 0.5rem; margin-bottom: 0.4rem;">
        <span style="font-size: 0.75rem; font-weight: 700; color: var(--primary-navy);">{{ $label }}</span>
        <a href="{{ $url }}?download=1" style="font-size: 0.75rem; font-weight: 600; color: var(--primary-cerulean); text-decoration: none;">Download</a>
    </div>
    @if($kind === 'pdf')
        <iframe title="{{ $title }}" data-file="{{ $url }}"></iframe>
    @else
        <img alt="{{ $title }}" data-file="{{ $url }}">
    @endif
</div>

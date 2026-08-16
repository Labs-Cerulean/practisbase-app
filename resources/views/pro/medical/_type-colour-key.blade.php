{{-- Compact type colour key. Optional $includeJournal, $includeLegacy. --}}
@php
    $items = [];
    if ($includeJournal ?? false) {
        $items['journal'] = 'Patient notes';
    }
    $items['prescription'] = 'Prescription';
    $items['referral'] = 'Referral';
    $items['certificate'] = 'Certificate';
    if ($includeLegacy ?? false) {
        $items['legacy_certificate'] = 'Legacy';
    }
@endphp
<div style="display: flex; flex-wrap: wrap; gap: 0.45rem 0.85rem; align-items: center; margin: {{ $margin ?? '0 0 1rem' }};">
    <span style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--text-muted);">Key</span>
    @foreach($items as $typeKey => $label)
        @php $chrome = \App\Models\ClinicalEntry::typeChrome($typeKey); @endphp
        <span style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.8rem; color: var(--text-main); font-weight: 600;">
            <span aria-hidden="true" style="width: 10px; height: 10px; border-radius: 2px; background: {{ $chrome['accent'] }}; display: inline-block; box-shadow: inset 0 0 0 1px {{ $chrome['border'] }};"></span>
            {{ $label }}
        </span>
    @endforeach
</div>

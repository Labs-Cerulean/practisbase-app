{{-- Post-issue / issued-card share actions. Expects: $pdfUrl, $issueCode, $docLabel, optional $patientTel --}}
@php
    $pdfUrl = $pdfUrl ?? '';
    $issueCode = $issueCode ?? '';
    $docLabel = $docLabel ?? 'Document';
    $patientTel = preg_replace('/\D+/', '', (string) ($patientTel ?? ''));
    $waText = rawurlencode($docLabel.' issued ('.$issueCode.'). PDF sent separately.');
    $waHref = $patientTel !== ''
        ? 'https://wa.me/'.$patientTel.'?text='.$waText
        : 'https://wa.me/?text='.$waText;
@endphp
<div class="issued-share-actions" style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;"
     data-pdf-url="{{ $pdfUrl }}"
     data-issue-code="{{ $issueCode }}"
     data-doc-label="{{ $docLabel }}">
    @if($pdfUrl !== '')
        <a href="{{ $pdfUrl }}"
           class="share-download"
           style="display: inline-block; padding: 0.4rem 0.75rem; border: 1px solid var(--primary-cerulean); background: var(--primary-cerulean); color: white; border-radius: var(--radius-md); font-size: 0.8rem; font-weight: 700; text-decoration: none;">
            Download PDF
        </a>
        <button type="button" class="share-native"
                style="padding: 0.4rem 0.75rem; background: white; border: 1px solid var(--border-light); color: var(--primary-navy); border-radius: var(--radius-md); font-size: 0.8rem; font-weight: 700; cursor: pointer;">
            Share
        </button>
        <a href="{{ $waHref }}" target="_blank" rel="noopener"
           style="display: inline-block; padding: 0.4rem 0.75rem; background: #ecfdf5; border: 1px solid #86efac; color: #14532d; border-radius: var(--radius-md); font-size: 0.8rem; font-weight: 700; text-decoration: none;">
            WhatsApp
        </a>
    @endif
    @if($issueCode !== '')
        <button type="button" class="share-copy-code" data-code="{{ $issueCode }}"
                style="padding: 0.4rem 0.75rem; background: white; border: 1px solid var(--border-light); color: var(--primary-navy); border-radius: var(--radius-md); font-size: 0.8rem; font-weight: 700; cursor: pointer;">
            Copy code
        </button>
    @endif
</div>

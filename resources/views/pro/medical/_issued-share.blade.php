{{-- Issued document share. Expects: $pdfUrl, $issueCode, $docLabel, optional $patientTel, $patientEmail, $expanded --}}
@php
    $pdfUrl = $pdfUrl ?? '';
    $issueCode = trim((string) ($issueCode ?? ''));
    $docLabel = $docLabel ?? 'Document';
    $patientTel = preg_replace('/\D+/', '', (string) ($patientTel ?? ''));
    $patientEmail = trim((string) ($patientEmail ?? ''));
    $expanded = (bool) ($expanded ?? false);
    $mailSubject = rawurlencode($docLabel.($issueCode !== '' ? ' · '.$issueCode : ''));
    $mailBody = rawurlencode(
        $docLabel.($issueCode !== '' ? ' ('.$issueCode.')' : '')." has been issued.\n\n"
        ."Please find the PDF attached — or download it from the link your clinic shared.\n"
    );
    $mailto = $patientEmail !== ''
        ? 'mailto:'.rawurlencode($patientEmail).'?subject='.$mailSubject.'&body='.$mailBody
        : 'mailto:?subject='.$mailSubject.'&body='.$mailBody;
    $waText = rawurlencode($docLabel.($issueCode !== '' ? ' ('.$issueCode.')' : '').' issued. I am sending the PDF separately.');
    $waHref = $patientTel !== ''
        ? 'https://wa.me/'.$patientTel.'?text='.$waText
        : 'https://wa.me/?text='.$waText;
@endphp
<div class="issued-share"
     data-pdf-url="{{ $pdfUrl }}"
     data-issue-code="{{ $issueCode }}"
     data-doc-label="{{ $docLabel }}"
     data-patient-email="{{ $patientEmail }}"
     style="display: inline-flex; flex-direction: column; align-items: flex-start; gap: 0.45rem;">
    <button type="button" class="share-toggle"
            aria-expanded="{{ $expanded ? 'true' : 'false' }}"
            style="padding: 0.4rem 0.85rem; background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); font-size: 0.8rem; font-weight: 700; cursor: pointer;">
        Share
    </button>
    <div class="share-menu" @if(! $expanded) hidden @endif
         style="display: {{ $expanded ? 'flex' : 'none' }}; flex-wrap: wrap; gap: 0.4rem; align-items: center; padding: 0.55rem; background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
        @if($pdfUrl !== '')
            <a href="{{ $mailto }}" class="share-email"
               style="display: inline-block; padding: 0.35rem 0.7rem; background: white; border: 1px solid var(--border-light); color: var(--primary-navy); border-radius: var(--radius-md); font-size: 0.78rem; font-weight: 700; text-decoration: none;">
                Email{{ $patientEmail !== '' ? '' : '' }}
            </a>
            <a href="{{ $waHref }}" target="_blank" rel="noopener" class="share-whatsapp"
               style="display: inline-block; padding: 0.35rem 0.7rem; background: #ecfdf5; border: 1px solid #86efac; color: #14532d; border-radius: var(--radius-md); font-size: 0.78rem; font-weight: 700; text-decoration: none;">
                WhatsApp
            </a>
            <button type="button" class="share-messenger"
                    style="padding: 0.35rem 0.7rem; background: #eff6ff; border: 1px solid #93c5fd; color: #1e3a8a; border-radius: var(--radius-md); font-size: 0.78rem; font-weight: 700; cursor: pointer;">
                Messenger
            </button>
            <button type="button" class="share-print"
                    style="padding: 0.35rem 0.7rem; background: white; border: 1px solid var(--border-light); color: var(--primary-navy); border-radius: var(--radius-md); font-size: 0.78rem; font-weight: 700; cursor: pointer;">
                Print
            </button>
            <a href="{{ $pdfUrl }}" class="share-download"
               style="display: inline-block; padding: 0.35rem 0.7rem; background: var(--primary-navy); color: white; border-radius: var(--radius-md); font-size: 0.78rem; font-weight: 700; text-decoration: none;">
                Download
            </a>
        @endif
    </div>
</div>

@extends('layouts.app')

@section('page_title', 'Import Gynae Proformas')

@section('content')
    <div style="max-width: 880px; margin: 0 auto;" data-gynae-import>
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem;">
            <div>
                <a href="/pro/medical/patients" style="color: var(--text-muted); font-weight: 600; text-decoration: none; font-size: 0.85rem;">&larr; Patients</a>
                <h1 style="margin: 0.4rem 0 0.35rem; color: var(--primary-navy); font-size: 1.5rem;">Import gynae/obs Word proformas</h1>
                <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem; line-height: 1.45; max-width: 40rem;">
                    Parsing and encryption happen on this device. Cerulean never receives the Word file or plaintext notes — only vault ciphertext is uploaded.
                </p>
            </div>
        </div>

        <div style="background: #eff6ff; border-left: 4px solid var(--primary-cerulean); border-radius: var(--radius-md); padding: 0.85rem 1rem; margin-bottom: 1rem; color: #1e3a5f; font-size: 0.85rem; line-height: 1.45;">
            Small-batch mode: review and import up to <strong>{{ $maxBatch }}</strong> patients per run. Proformas should start with <code>Name:</code> and use the usual labels (LMP, Gynae Hx, Obs Hx, Exam, US, Plan…).
        </div>

        <div data-import-status style="display: none; padding: 0.85rem 1rem; border-radius: var(--radius-md); margin-bottom: 1rem; font-size: 0.9rem;"></div>

        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.25rem; margin-bottom: 1.25rem; box-shadow: var(--shadow-sm);">
            <label style="display: block; font-weight: 700; color: var(--primary-navy); margin-bottom: 0.5rem;">Word document (.docx)</label>
            <input type="file" data-import-file accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                   style="display: block; margin-bottom: 0.85rem; font-size: 0.9rem;">
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
                <button type="button" data-import-parse
                        style="background: var(--primary-navy); color: white; border: none; padding: 0.65rem 1rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">
                    Parse on this device
                </button>
                <span style="font-size: 0.8rem; color: var(--text-muted);">File stays in browser memory.</span>
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap; margin-bottom: 0.75rem;">
            <h2 style="margin: 0; color: var(--primary-navy); font-size: 1.1rem;">Review batch</h2>
            <div style="font-size: 0.85rem; color: var(--text-muted);">Selected: <strong data-import-selected-count>0</strong> / {{ $maxBatch }}</div>
        </div>

        <div data-import-preview style="margin-bottom: 1.25rem;">
            <p style="color: var(--text-muted);">Choose a .docx and parse it to preview detected patients here.</p>
        </div>

        <button type="button" data-import-commit disabled
                style="width: 100%; max-width: 420px; padding: 0.85rem; background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">
            Encrypt &amp; import selected
        </button>
    </div>
@endsection

@push('scripts')
    <script src="/js/medical/nacl-fast.min.js"></script>
    <script src="/js/medical/vault-crypto.js"></script>
    <script src="/js/medical/mammoth.browser.min.js"></script>
    <script src="/js/medical/gynae-proforma-import.js"></script>
@endpush

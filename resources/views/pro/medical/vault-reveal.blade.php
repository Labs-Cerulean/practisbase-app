@extends('layouts.app')

@section('page_title', 'Save Recovery Code')

@section('content')
    <div style="max-width: 720px; margin: 0 auto; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm);">
        <h1 style="color: var(--primary-navy); margin-top: 0;">Save this recovery code now</h1>
        <p style="color: #b91c1c; font-weight: 600; line-height: 1.45;">This code is shown once. It will not be displayed again. Cerulean Labs cannot recover it.</p>

        <div style="margin: 1.5rem 0; padding: 1.25rem; background: #0f172a; color: #f8fafc; border-radius: var(--radius-md); font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 1.05rem; letter-spacing: 0.06em; text-align: center; word-break: break-all;" id="recovery-code-display">
            {{ $recoveryCode }}
        </div>

        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1rem;">
            <button type="button" id="copy-recovery-code" style="padding: 0.65rem 1rem; background: var(--primary-navy); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">
                Copy code
            </button>
            <span id="copy-recovery-status" style="align-self: center; font-size: 0.85rem; color: #065f46; display: none;">Copied</span>
        </div>

        <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: var(--radius-md); padding: 0.85rem 1rem; color: #92400e; font-size: 0.85rem; line-height: 1.45; margin-bottom: 1rem;">
            <strong>Do not save this as your PractisBase website password.</strong>
            If Google / Chrome asks “Save password?”, tap away / decline.
            Store it as a <strong>Secure Note</strong> (or equivalent note) titled “PractisBase Medical Vault”.
            Login password and vault code must stay separate — password reset never unlocks this vault.
        </div>

        <p style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.45;">
            The code is long on purpose (encryption strength). Paste it from your note when unlocking — you should not memorise it.
        </p>

        <a href="/pro/medical/patients" style="display: inline-block; margin-top: 1rem; background: var(--primary-cerulean); color: white; text-decoration: none; padding: 0.85rem 1.25rem; border-radius: var(--radius-md); font-weight: 700;">
            I have saved the code — continue
        </a>
    </div>

    <script>
        (function () {
            var btn = document.getElementById('copy-recovery-code');
            var status = document.getElementById('copy-recovery-status');
            var code = @json($recoveryCode);
            if (!btn) return;
            btn.addEventListener('click', function () {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(code).then(function () {
                        status.style.display = 'inline';
                    });
                }
            });
        })();
    </script>
@endsection

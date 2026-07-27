@extends('layouts.app')

@section('page_title', 'Save recovery code')

@section('content')
    <div style="max-width: 720px; margin: 0 auto; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.5rem; box-shadow: var(--shadow-sm);">
        <h1 style="color: var(--primary-navy); margin-top: 0; font-size: 1.35rem;">Save this recovery code now</h1>
        <p style="color: #b91c1c; font-weight: 600; line-height: 1.45;">This code is shown once. It will not be displayed again. Cerulean Labs cannot recover it.</p>

        @if($errors->any())
            <div style="background: #fef2f2; color: #991b1b; padding: 0.85rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
                @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif

        @if($recoveryCode)
            <div style="margin: 1.5rem 0; padding: 1.25rem; background: #0f172a; color: #f8fafc; border-radius: var(--radius-md); font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 1.05rem; letter-spacing: 0.06em; text-align: center; word-break: break-all;" id="recovery-code-display">
                {{ $recoveryCode }}
            </div>

            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1rem;">
                <button type="button" id="copy-recovery-code" style="padding: 0.65rem 1rem; background: var(--primary-navy); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">
                    Copy code
                </button>
                <span id="copy-recovery-status" style="align-self: center; font-size: 0.85rem; color: #065f46; display: none;">Copied</span>
            </div>
        @else
            <div style="margin: 1.5rem 0; padding: 1rem; background: #fef2f2; border-left: 4px solid #ef4444; border-radius: var(--radius-md); color: #991b1b; font-size: 0.9rem; line-height: 1.45;">
                The one-time reveal is no longer on this screen. If you already copied the code into a Secure Note, confirm below. If not, Labs cannot show or reset it.
            </div>
        @endif

        <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: var(--radius-md); padding: 0.85rem 1rem; color: #92400e; font-size: 0.85rem; line-height: 1.45; margin-bottom: 1rem;">
            <strong>Do not save this as your PractisBase website password.</strong>
            Store it as a <strong>Secure Note</strong> titled “PractisBase Medical Vault”.
            Login password and vault code must stay separate — password reset never unlocks this vault.
        </div>

        <form action="/pro/medical/vault/reveal/confirm" method="POST" id="vault-reveal-form">
            @csrf
            <input type="hidden" name="read_duration_seconds" id="readDurationInput" value="0">
            <label style="display: flex; gap: 0.6rem; align-items: flex-start; margin-bottom: 1.25rem; font-size: 0.9rem; line-height: 1.45; cursor: pointer;">
                <input type="checkbox" name="confirm_code_saved" value="1" required style="margin-top: 0.2rem;">
                <span>I have saved this recovery code offline (Secure Note / printed copy). I understand Cerulean Labs cannot reset it if I lose it.</span>
            </label>
            <button type="submit" style="width: 100%; padding: 0.9rem; background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">
                Confirm saved — continue
            </button>
        </form>
    </div>

    <script>
        (function () {
            var started = Date.now();
            var form = document.getElementById('vault-reveal-form');
            var durationInput = document.getElementById('readDurationInput');
            if (form && durationInput) {
                form.addEventListener('submit', function () {
                    durationInput.value = String(Math.max(0, Math.round((Date.now() - started) / 1000)));
                });
            }

            var btn = document.getElementById('copy-recovery-code');
            var status = document.getElementById('copy-recovery-status');
            var code = @json($recoveryCode);
            if (!btn || !code) return;
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

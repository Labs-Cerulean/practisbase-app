@extends('layouts.app')

@section('page_title', 'Vault setup')

@section('content')
    <div style="max-width: 720px; margin: 0 auto;">
        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.5rem; box-shadow: var(--shadow-sm);">
            <h1 style="color: var(--primary-navy); margin-top: 0; font-size: 1.35rem;">Create medical vault</h1>
            <p style="color: var(--text-muted); line-height: 1.5;">
                Patient and clinical payloads are encrypted with a key derived from a recovery code only you hold.
                PractisBase stores a verifier hash — not the code — and cannot decrypt your vault without it.
            </p>

            @if($errors->any())
                <div style="background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
                    @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                </div>
            @endif

            <form action="/pro/medical/vault/setup" method="POST" id="vault-setup-form">
                @csrf
                <input type="hidden" name="read_duration_seconds" id="readDurationInput" value="0">
                <label style="display: flex; gap: 0.6rem; align-items: flex-start; margin-bottom: 1rem; font-size: 0.9rem; line-height: 1.45; cursor: pointer;">
                    <input type="checkbox" name="acknowledge" value="1" required style="margin-top: 0.2rem;">
                    <span>I understand that if I lose this recovery code, my patient data will be permanently unrecoverable by me or by Cerulean Labs. PractisBase cannot reset this key.</span>
                </label>
                <label style="display: flex; gap: 0.6rem; align-items: flex-start; margin-bottom: 1.5rem; font-size: 0.9rem; line-height: 1.45; cursor: pointer;">
                    <input type="checkbox" name="confirm_saved" value="1" required style="margin-top: 0.2rem;">
                    <span>I will save the recovery code offline immediately after it is shown, and I will keep weekly backups of clinical data.</span>
                </label>
                <button type="submit" style="width: 100%; padding: 0.9rem; background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">
                    Generate recovery code
                </button>
            </form>
        </div>
    </div>

    <script>
        (function () {
            var started = Date.now();
            var form = document.getElementById('vault-setup-form');
            var input = document.getElementById('readDurationInput');
            if (!form || !input) return;
            form.addEventListener('submit', function () {
                input.value = String(Math.max(0, Math.round((Date.now() - started) / 1000)));
            });
        })();
    </script>
@endsection

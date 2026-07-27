@extends('layouts.app')

@section('page_title', 'Unlock Medical Vault')

@section('content')
    <div style="max-width: 520px; margin: 0 auto; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm);">
        <h1 style="color: var(--primary-navy); margin-top: 0;">Unlock medical vault</h1>
        <p style="color: var(--text-muted); line-height: 1.45;">
            Paste your <strong>medical vault recovery code</strong> (not your PractisBase login password).
            Browsers are blocked from treating this as a site password so they cannot overwrite your login.
        </p>

        @if($backupOverdue ?? false)
            <div style="background: #fef2f2; color: #991b1b; padding: 0.85rem; border-radius: var(--radius-md); margin-bottom: 1rem; border-left: 4px solid #ef4444; font-size: 0.85rem;">
                Weekly backup is overdue (or never done). After unlock, download a backup from Patient Journals.
            </div>
        @endif

        @if(session('success'))
            <div style="background: #ecfdf5; color: #065f46; padding: 0.85rem; border-radius: var(--radius-md); margin-bottom: 1rem;">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div style="background: #fef2f2; color: #991b1b; padding: 0.85rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
                @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif

        <form action="/pro/medical/vault/unlock" method="POST" autocomplete="off" data-lpignore="true" data-1p-ignore="true" data-bwignore="true" data-form-type="other">
            @csrf
            <label for="recovery_code" style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Medical vault recovery code</label>
            <div style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">
                <input type="text"
                       name="recovery_code"
                       id="recovery_code"
                       required
                       autofocus
                       spellcheck="false"
                       autocapitalize="characters"
                       autocomplete="off"
                       inputmode="text"
                       data-lpignore="true"
                       data-1p-ignore="true"
                       data-bwignore="true"
                       data-form-type="other"
                       placeholder="XXXX-XXXX-XXXX-…"
                       class="vault-recovery-input"
                       style="flex: 1; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-family: ui-monospace, monospace; -webkit-text-security: disc;">
                <button type="button" id="toggle-recovery-visibility" style="padding: 0.55rem 0.85rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: white; font-weight: 600; cursor: pointer; font-size: 0.8rem;">Show</button>
            </div>
            <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 1rem; line-height: 1.4;">
                Store the code as a <strong>Secure Note</strong> / note in your password manager — never as the PractisBase website password. Decline any “Save password?” prompt on this screen.
            </div>
            <button type="submit" style="width: 100%; padding: 0.85rem; background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Unlock</button>
        </form>
    </div>

    <script>
        (function () {
            var input = document.getElementById('recovery_code');
            var btn = document.getElementById('toggle-recovery-visibility');
            if (!input || !btn) return;
            input.style.webkitTextSecurity = 'disc';
            btn.addEventListener('click', function () {
                var showing = input.style.webkitTextSecurity === 'none';
                input.style.webkitTextSecurity = showing ? 'disc' : 'none';
                btn.textContent = showing ? 'Show' : 'Hide';
            });
        })();
    </script>
@endsection

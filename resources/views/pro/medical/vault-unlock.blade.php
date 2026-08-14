@extends('layouts.app')

@section('page_title', 'Unlock vault')

@section('content')
    <div style="max-width: 520px; margin: 0 auto; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm);">
        <h1 style="color: var(--primary-navy); margin-top: 0;">Unlock medical vault</h1>
        <p style="color: var(--text-muted); line-height: 1.45;">
            On a trusted phone or laptop, use biometrics / device unlock (Face ID, fingerprint, Touch ID, Windows Hello).
            Otherwise paste your <strong>medical vault recovery code</strong> (not your PractisBase login password).
        </p>

        @if($backupOverdue ?? false)
            <div style="background: #fef2f2; color: #991b1b; padding: 0.85rem; border-radius: var(--radius-md); margin-bottom: 1rem; border-left: 4px solid #ef4444; font-size: 0.85rem;">
                Weekly backup is overdue (or never done). After unlock, download a backup from Patients.
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

        <div id="device-unlock-panel" style="display: none; margin-bottom: 1.25rem; padding: 1rem; background: #eff6ff; border: 1px solid #93c5fd; border-radius: var(--radius-md);">
            <div style="font-weight: 700; color: #1d4ed8; margin-bottom: 0.35rem;">Quick unlock on this device</div>
            <p style="margin: 0 0 0.75rem; font-size: 0.85rem; color: var(--text-muted); line-height: 1.4;">
                Uses Face ID, fingerprint, Touch ID, or Windows Hello when available.
            </p>
            <button type="button" id="device-unlock-btn" style="width: 100%; padding: 0.85rem; background: #1d4ed8; color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">
                Unlock with Face ID / fingerprint
            </button>
            <div id="device-unlock-error" style="display: none; margin-top: 0.65rem; color: #991b1b; font-size: 0.85rem;"></div>
        </div>

        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
            <div style="flex: 1; height: 1px; background: var(--border-light);"></div>
            <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Or recovery code</div>
            <div style="flex: 1; height: 1px; background: var(--border-light);"></div>
        </div>

        <form action="/pro/medical/vault/unlock" method="POST" id="vault-unlock-form" autocomplete="off" data-lpignore="true" data-1p-ignore="true" data-bwignore="true" data-form-type="other">
            @csrf
            <label for="recovery_code" style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Medical vault recovery code</label>
            <div style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">
                <input type="text"
                       name="recovery_code"
                       id="recovery_code"
                       required
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
                Store the code as a <strong>Secure Note</strong> in your password manager — never as the PractisBase website password. Login password stays separate from the vault.
            </div>
            <button type="submit" style="width: 100%; padding: 0.85rem; background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Unlock with recovery code</button>
        </form>
    </div>

    @include('pro.medical._vault-device-js')
    <script src="/js/medical/nacl-fast.min.js"></script>
    <script src="/js/medical/vault-crypto.js"></script>
    <script>
        (function () {
            if (window.PractisBaseVaultCrypto) {
                window.PractisBaseVaultCrypto.bindUnlockForm(document.getElementById('vault-unlock-form'));
            }

            var input = document.getElementById('recovery_code');
            var btn = document.getElementById('toggle-recovery-visibility');
            if (input && btn) {
                input.style.webkitTextSecurity = 'disc';
                btn.addEventListener('click', function () {
                    var showing = input.style.webkitTextSecurity === 'none';
                    input.style.webkitTextSecurity = showing ? 'disc' : 'none';
                    btn.textContent = showing ? 'Show' : 'Hide';
                });
            }

            var panel = document.getElementById('device-unlock-panel');
            var unlockBtn = document.getElementById('device-unlock-btn');
            var err = document.getElementById('device-unlock-error');
            if (!panel || !unlockBtn || !window.PractisVaultDevice) return;

            PractisVaultDevice.platformAvailable().then(function (ok) {
                if (!ok) return;
                return PractisVaultDevice.hasLocalWrapKey().then(function (hasKey) {
                    if (hasKey) {
                        panel.style.display = 'block';
                        if (input) input.removeAttribute('autofocus');
                        // Keep a fresh challenge ready so Face ID can start in the same tap
                        // (Safari drops user-activation if we fetch options first).
                        PractisVaultDevice.startUnlockPrefetchKeepAlive();
                    }
                });
            });

            unlockBtn.addEventListener('click', function () {
                err.style.display = 'none';
                unlockBtn.disabled = true;
                unlockBtn.textContent = 'Waiting for fingerprint / Face ID…';
                PractisVaultDevice.unlockWithDevice().then(function (result) {
                    // Pull session DEK into this browser for later client-side import encryption.
                    if (window.PractisBaseVaultCrypto) {
                        return fetch('/pro/medical/vault/client-dek', {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin'
                        }).then(function (res) {
                            return res.ok ? res.json() : null;
                        }).then(function (data) {
                            if (data && data.dek_b64) {
                                window.PractisBaseVaultCrypto.storeDek(window.PractisBaseVaultCrypto.base64ToBytes(data.dek_b64));
                            }
                            return result;
                        }).catch(function () { return result; });
                    }
                    return result;
                }).then(function (result) {
                    window.location.href = (result && result.redirect) ? result.redirect : '/pro/medical/patients';
                }).catch(function (e) {
                    err.textContent = e.message || 'Device unlock failed.';
                    err.style.display = 'block';
                    unlockBtn.disabled = false;
                    unlockBtn.textContent = 'Unlock with Face ID / fingerprint';
                    PractisVaultDevice.prefetchUnlockOptions();
                });
            });
        })();
    </script>
@endsection

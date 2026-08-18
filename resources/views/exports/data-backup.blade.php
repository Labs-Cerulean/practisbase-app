@extends('layouts.app')

@section('page_title', 'Backup')

@section('content')
    <div style="max-width: 720px; margin: 0 auto;">
        <h1 style="color: var(--primary-navy); margin: 0 0 0.5rem; font-size: 1.5rem;">Backup</h1>
        <p style="color: var(--text-muted); font-size: 0.95rem; line-height: 1.5; margin: 0 0 1.25rem;">
            Two downloads, one place. Practice data and medical vault stay separate because they unlock differently.
        </p>

        @if($errors->any())
            <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 0.85rem 1rem; border-radius: var(--radius-md); margin-bottom: 1rem; font-size: 0.9rem;">
                {{ $errors->first() }}
            </div>
        @endif

        @if(session('success'))
            <div style="background: #ecfdf5; color: #065f46; padding: 0.85rem 1rem; border-radius: var(--radius-md); margin-bottom: 1rem; font-size: 0.9rem;">
                {{ session('success') }}
            </div>
        @endif

        @if($anyOverdue ?? false)
            <div style="background: #f0f9ff; border: 1px solid #bae6fd; border-left: 4px solid var(--primary-cerulean); border-radius: var(--radius-md); padding: 0.85rem 1rem; margin-bottom: 1.25rem; color: var(--primary-navy); font-size: 0.9rem; line-height: 1.45;">
                Weekly reminder: download a fresh copy below.
            </div>
        @endif

        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); padding: 1.5rem; margin-bottom: 1rem;">
            <div style="font-weight: 700; color: var(--primary-navy); margin-bottom: 0.35rem;">1. Practice data</div>
            <p style="margin: 0 0 1rem; font-size: 0.9rem; color: var(--text-muted); line-height: 1.45;">
                Clients, invoices, payments, expenses, and tax payments.
            </p>
            <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">
                Last backup:
                <strong style="color: var(--primary-navy);">
                    {{ $lastBackupAt ? $lastBackupAt->format('d M Y H:i') : 'Never' }}
                </strong>
                @if($backupOverdue)
                    <span style="color: #b45309; font-weight: 600;"> · Due</span>
                @endif
            </div>
            <form action="/exports/backup" method="POST">
                @csrf
                <button type="submit" style="background: var(--primary-cerulean); color: white; border: none; padding: 0.75rem 1.25rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">
                    Download practice ZIP
                </button>
            </form>
        </div>

        @if($user->canAccessProPackage('med'))
            <div id="medical" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); padding: 1.5rem;">
                <div style="font-weight: 700; color: var(--primary-navy); margin-bottom: 0.35rem;">2. Medical vault</div>
                <p style="margin: 0 0 1rem; font-size: 0.9rem; color: var(--text-muted); line-height: 1.45;">
                    Decrypted patients, notes, prescriptions, and attachments.
                </p>

                @if(! $vault)
                    <a href="/pro/medical/vault/setup" style="display: inline-block; padding: 0.7rem 1.1rem; background: var(--primary-navy); color: white; border-radius: var(--radius-md); font-weight: 700; text-decoration: none;">Set up vault first</a>
                @else
                    <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">
                        Last vault backup:
                        <strong style="color: var(--primary-navy);">
                            {{ $vault->last_backup_at ? $vault->last_backup_at->format('d M Y H:i') : 'Never' }}
                        </strong>
                        @if($vaultOverdue)
                            <span style="color: #b45309; font-weight: 600;"> · Due</span>
                        @endif
                    </div>

                    @if(! $vaultUnlocked)
                        <div id="device-unlock-panel" style="display: none; margin-bottom: 1.25rem; padding: 1rem; background: #eff6ff; border: 1px solid #93c5fd; border-radius: var(--radius-md);">
                            <div style="font-weight: 700; color: #1d4ed8; margin-bottom: 0.35rem;">Quick unlock on this device</div>
                            <p style="margin: 0 0 0.75rem; font-size: 0.85rem; color: var(--text-muted); line-height: 1.4;">
                                Face ID, fingerprint, Touch ID, or Windows Hello — then download.
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
                            <input type="hidden" name="return" value="/exports/backup#medical">
                            <label for="backup_unlock_recovery_code" style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Medical vault recovery code</label>
                            <input type="text"
                                   name="recovery_code"
                                   id="backup_unlock_recovery_code"
                                   required
                                   spellcheck="false"
                                   autocomplete="off"
                                   inputmode="text"
                                   data-lpignore="true"
                                   data-1p-ignore="true"
                                   data-bwignore="true"
                                   data-form-type="other"
                                   placeholder="XXXX-XXXX-XXXX-…"
                                   style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); margin-bottom: 0.75rem; font-family: ui-monospace, monospace; -webkit-text-security: disc;">
                            <button type="submit" style="width: 100%; padding: 0.85rem; background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">
                                Unlock with recovery code
                            </button>
                        </form>
                    @else
                        <form action="/pro/medical/vault/backup" method="POST">
                            @csrf
                            <button type="submit" style="width: 100%; padding: 0.85rem; background: var(--primary-navy); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">
                                Download vault ZIP
                            </button>
                        </form>
                        <p style="margin: 0.75rem 0 0; font-size: 0.8rem; color: var(--text-muted); text-align: center;">
                            Vault is unlocked on this device — no need to paste the recovery code again.
                        </p>
                    @endif
                @endif
            </div>
        @endif
    </div>

    @if(($vault ?? null) && ! ($vaultUnlocked ?? false))
        @include('pro.medical._vault-device-js')
        <script src="/js/medical/nacl-fast.min.js"></script>
        <script src="/js/medical/vault-crypto.js"></script>
        <script>
            (function () {
                if (window.PractisBaseVaultCrypto) {
                    window.PractisBaseVaultCrypto.bindUnlockForm(document.getElementById('vault-unlock-form'));
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
                            PractisVaultDevice.startUnlockPrefetchKeepAlive();
                        }
                    });
                });

                unlockBtn.addEventListener('click', function () {
                    err.style.display = 'none';
                    unlockBtn.disabled = true;
                    unlockBtn.textContent = 'Waiting for fingerprint / Face ID…';
                    PractisVaultDevice.unlockWithDevice().then(function (result) {
                        var dekReady = Promise.resolve(result);
                        if (window.PractisBaseVaultCrypto) {
                            dekReady = fetch('/pro/medical/vault/client-dek', {
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
                        // Don't let a hung DEK fetch leave the button stuck waiting.
                        return Promise.race([
                            dekReady,
                            new Promise(function (resolve) { setTimeout(function () { resolve(result); }, 2500); })
                        ]);
                    }).then(function () {
                        // Same-path + hash alone does not reload; force a fresh Backup page.
                        window.location.replace('/exports/backup?unlocked=1#medical');
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
    @endif
@endsection

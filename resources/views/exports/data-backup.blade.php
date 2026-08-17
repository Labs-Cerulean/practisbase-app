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
                    Decrypted patients, notes, prescriptions, and attachments. Requires your recovery code.
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
                        <a href="/pro/medical/vault/unlock"
                           style="display: inline-block; padding: 0.7rem 1.1rem; background: var(--primary-navy); color: white; border-radius: var(--radius-md); font-weight: 700; text-decoration: none;">
                            Unlock vault to back up
                        </a>
                    @else
                        <form action="/pro/medical/vault/backup" method="POST" autocomplete="off" data-lpignore="true" data-1p-ignore="true" data-bwignore="true" data-form-type="other">
                            @csrf
                            <label for="backup_recovery_code" style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Recovery code</label>
                            <input type="text"
                                   name="recovery_code"
                                   id="backup_recovery_code"
                                   required
                                   spellcheck="false"
                                   autocomplete="off"
                                   inputmode="text"
                                   data-lpignore="true"
                                   data-1p-ignore="true"
                                   data-bwignore="true"
                                   data-form-type="other"
                                   placeholder="XXXX-XXXX-XXXX-…"
                                   style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); margin-bottom: 0.5rem; font-family: ui-monospace, monospace; -webkit-text-security: disc;">
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 1rem;">
                                Paste from your Secure Note. Decline any browser save-password prompt.
                            </div>
                            <button type="submit" style="width: 100%; padding: 0.85rem; background: var(--primary-navy); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">
                                Verify code &amp; download vault ZIP
                            </button>
                        </form>
                    @endif
                @endif
            </div>
        @endif
    </div>
@endsection

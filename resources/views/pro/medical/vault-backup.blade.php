@extends('layouts.app')

@section('page_title', 'Medical Vault Backup')

@section('content')
    <div style="max-width: 640px; margin: 0 auto;">
        <a href="/pro/medical/patients" style="color: var(--text-muted); font-weight: 600; text-decoration: none; font-size: 0.85rem;">&larr; Patients</a>

        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm); margin-top: 0.75rem;">
            <h1 style="color: var(--primary-navy); margin-top: 0;">Weekly medical backup</h1>
            <p style="color: var(--text-muted); line-height: 1.5;">
                Re-enter your recovery code to download a <strong>decrypted</strong> ZIP of this vault’s patients, journal entries, prescriptions, referrals, and attachments.
                Store the file offline. Cerulean Labs cannot recreate it if you lose both the ZIP and your recovery code.
            </p>

            @if($backupOverdue)
                <div style="background: #fef2f2; color: #991b1b; padding: 0.85rem 1rem; border-radius: var(--radius-md); margin-bottom: 1rem; border-left: 4px solid #ef4444; font-size: 0.9rem;">
                    Backup is overdue (or never completed). Download a fresh pack before adding more clinical data.
                </div>
            @endif

            <div style="background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.85rem 1rem; margin-bottom: 1.25rem; font-size: 0.85rem; color: var(--text-muted);">
                Last successful backup:
                <strong style="color: var(--primary-navy);">
                    {{ $vault->last_backup_at ? $vault->last_backup_at->format('d M Y H:i') : 'Never' }}
                </strong>
            </div>

            @if($errors->any())
                <div style="background: #fef2f2; color: #991b1b; padding: 0.85rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
                    @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                </div>
            @endif

            <form action="/pro/medical/vault/backup" method="POST">
                @csrf
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Recovery code</label>
                <input type="password" name="recovery_code" required autofocus placeholder="XXXX-XXXX-..."
                       style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); margin-bottom: 1rem; font-family: ui-monospace, monospace;">
                <button type="submit" style="width: 100%; padding: 0.85rem; background: var(--primary-navy); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">
                    Verify code &amp; download ZIP
                </button>
            </form>
        </div>
    </div>
@endsection

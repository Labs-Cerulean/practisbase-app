@extends('layouts.app')

@section('page_title', 'Data backup')

@section('content')
    <div style="max-width: 640px; margin: 0 auto;">
        <h1 style="color: var(--primary-navy); margin: 0 0 0.5rem; font-size: 1.5rem;">Weekly data backup</h1>
        <p style="color: var(--text-muted); font-size: 0.95rem; line-height: 1.5; margin: 0 0 1.25rem;">
            Download a ZIP of <strong>your</strong> PractisBase data only — clients, invoices, payments, expenses, and tax payments. Store it offline. Clinical vault journals are not included here.
        </p>

        @if($errors->any())
            <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 0.85rem 1rem; border-radius: var(--radius-md); margin-bottom: 1rem; font-size: 0.9rem;">
                {{ $errors->first() }}
            </div>
        @endif

        @if($backupOverdue)
            <div style="background: #f0f9ff; border: 1px solid #bae6fd; border-left: 4px solid var(--primary-cerulean); border-radius: var(--radius-md); padding: 0.85rem 1rem; margin-bottom: 1.25rem; color: var(--primary-navy); font-size: 0.9rem; line-height: 1.45;">
                Weekly reminder: download a fresh copy of your data.
            </div>
        @endif

        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); padding: 1.5rem;">
            <div style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 1.25rem;">
                Last successful backup:
                <strong style="color: var(--primary-navy);">
                    {{ $lastBackupAt ? $lastBackupAt->format('d M Y H:i') : 'Never' }}
                </strong>
            </div>

            <form action="/exports/backup" method="POST">
                @csrf
                <button type="submit" style="background: var(--primary-cerulean); color: white; border: none; padding: 0.75rem 1.25rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">
                    Download my data ZIP
                </button>
            </form>

            @if($user->canAccessProPackage('med'))
                <p style="margin: 1.25rem 0 0; font-size: 0.85rem; color: var(--text-muted); line-height: 1.45;">
                    Medical vault: use
                    <a href="/pro/medical/vault/backup" style="color: var(--primary-cerulean); font-weight: 600; text-decoration: none; border-bottom: 1px dotted var(--primary-cerulean);">Patients → Backup</a>
                    (recovery code required).
                </p>
            @endif
        </div>
    </div>
@endsection

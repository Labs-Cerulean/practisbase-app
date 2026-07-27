@extends('layouts.app')

@section('page_title', 'Unlock Medical Vault')

@section('content')
    <div style="max-width: 520px; margin: 0 auto; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm);">
        <h1 style="color: var(--primary-navy); margin-top: 0;">Unlock medical vault</h1>
        <p style="color: var(--text-muted); line-height: 1.45;">
            Enter your <strong>medical vault recovery code</strong> (not your PractisBase login password).
            It is long on purpose — save it in your password manager as a <em>separate</em> item from login.
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

        <form action="/pro/medical/vault/unlock" method="POST" autocomplete="on">
            @csrf
            {{-- Distinct "username" so password managers store vault code separately from login email/password --}}
            <input type="text"
                   name="vault_credential_label"
                   value="PractisBase Medical Vault"
                   autocomplete="username"
                   readonly
                   tabindex="-1"
                   aria-hidden="true"
                   style="position: absolute; left: -9999px; width: 1px; height: 1px; opacity: 0;">

            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Medical vault recovery code</label>
            <input type="password"
                   name="recovery_code"
                   id="recovery_code"
                   required
                   autofocus
                   placeholder="XXXX-XXXX-XXXX-…"
                   autocomplete="current-password"
                   data-1p-ignore="false"
                   style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); margin-bottom: 0.5rem; font-family: ui-monospace, monospace;">
            <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 1rem; line-height: 1.4;">
                Autofill should offer <strong>PractisBase Medical Vault</strong>, not your account login. If Google offers to replace your login password, decline and save this as a new item instead.
            </div>
            <button type="submit" style="width: 100%; padding: 0.85rem; background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Unlock</button>
        </form>
    </div>
@endsection

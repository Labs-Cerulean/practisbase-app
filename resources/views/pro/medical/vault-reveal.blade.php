@extends('layouts.app')

@section('page_title', 'Save Recovery Code')

@section('content')
    <div style="max-width: 720px; margin: 0 auto; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm);">
        <h1 style="color: var(--primary-navy); margin-top: 0;">Save this recovery code now</h1>
        <p style="color: #b91c1c; font-weight: 600; line-height: 1.45;">This code is shown once. It will not be displayed again. Cerulean Labs cannot recover it.</p>

        <div style="margin: 1.5rem 0; padding: 1.25rem; background: #0f172a; color: #f8fafc; border-radius: var(--radius-md); font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 1.15rem; letter-spacing: 0.08em; text-align: center; word-break: break-all;">
            {{ $recoveryCode }}
        </div>

        <p style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.45; margin-bottom: 1rem;">
            It is long on purpose (high entropy encryption key). You should not type it from memory — save it in your password manager as a <strong>separate item</strong> from your PractisBase login.
            You will need it every new login session to unlock journals. Password reset never unlocks this vault.
        </p>

        {{-- Helps password managers offer “save password” under a distinct vault identity --}}
        <form autocomplete="on" onsubmit="return false;" style="margin-bottom: 1.25rem; padding: 1rem; background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.5rem;">Save in password manager</div>
            <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Account name</label>
            <input type="text" value="PractisBase Medical Vault" autocomplete="username" readonly
                   style="width: 100%; padding: 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); margin-bottom: 0.65rem; background: white;">
            <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Recovery code</label>
            <input type="password" value="{{ $recoveryCode }}" autocomplete="new-password" readonly
                   style="width: 100%; padding: 0.55rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-family: ui-monospace, monospace; background: white;">
            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.5rem;">
                When your browser offers to save, accept it as <em>PractisBase Medical Vault</em> — do not overwrite your login password.
            </div>
        </form>

        <a href="/pro/medical/patients" style="display: inline-block; margin-top: 0.25rem; background: var(--primary-cerulean); color: white; text-decoration: none; padding: 0.85rem 1.25rem; border-radius: var(--radius-md); font-weight: 700;">
            I have saved the code — continue
        </a>
    </div>
@endsection

@extends('layouts.app')

@section('page_title', 'Save Recovery Code')

@section('content')
    <div style="max-width: 720px; margin: 0 auto; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm);">
        <h1 style="color: var(--primary-navy); margin-top: 0;">Save this recovery code now</h1>
        <p style="color: #b91c1c; font-weight: 600; line-height: 1.45;">This code is shown once. It will not be displayed again. Cerulean Labs cannot recover it.</p>

        <div style="margin: 1.5rem 0; padding: 1.25rem; background: #0f172a; color: #f8fafc; border-radius: var(--radius-md); font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 1.15rem; letter-spacing: 0.08em; text-align: center; word-break: break-all;">
            {{ $recoveryCode }}
        </div>

        <p style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.45;">
            Store it offline (password manager / printed sealed note). You must enter it every new login session to unlock journals.
            Password reset never unlocks this vault.
        </p>

        <a href="/pro/medical/patients" style="display: inline-block; margin-top: 1rem; background: var(--primary-cerulean); color: white; text-decoration: none; padding: 0.85rem 1.25rem; border-radius: var(--radius-md); font-weight: 700;">
            I have saved the code — continue
        </a>
    </div>
@endsection

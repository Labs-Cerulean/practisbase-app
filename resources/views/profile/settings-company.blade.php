@extends('layouts.app')

@section('page_title', 'Settings')

@section('content')
    <div style="max-width: 650px; margin: 0 auto;">
        <h1 style="font-size: 1.5rem; color: var(--primary-navy); margin-bottom: 0.5rem;">Settings</h1>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0 0 1.5rem; line-height: 1.45;">
            This account runs <strong>Cerulean Labs Limited</strong> company books.
            VAT, IBAN, and letterhead live on the company profile — not sole-trader tax setup.
        </p>

        @if(session('success'))
            <div style="background: #d1fae5; border: 1px solid #10b981; color: #047857; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; font-weight: 500;">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div style="background: #fef2f2; border: 1px solid #f87171; color: #b91c1c; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; font-size: 0.85rem;">
                <ul style="margin: 0; padding-left: 1.5rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: var(--radius-lg); padding: 1.1rem 1.25rem; margin-bottom: 1.5rem;">
            <div style="font-weight: 700; color: #1e3a8a; margin-bottom: 0.35rem;">{{ $profile->legal_name }}</div>
            <div style="font-size: 0.85rem; color: #1e40af; line-height: 1.45;">
                {{ $profile->registration_number }} · Art 10 · {{ ucfirst($profile->vat_filing_frequency) }} VAT
                · First period {{ $periodLabel }}
            </div>
            <a href="/company/profile" style="display: inline-block; margin-top: 0.75rem; font-size: 0.85rem; font-weight: 700; color: #1e3a8a; text-decoration: none;">Open company profile (logo · VAT · IBAN · payments) →</a>
        </div>

        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.75rem; box-shadow: var(--shadow-sm); margin-bottom: 1.5rem;">
            <h3 style="color: var(--primary-navy); margin-top: 0; margin-bottom: 1.25rem; border-bottom: 1px solid var(--border-light); padding-bottom: 0.5rem;">Your login</h3>
            <form action="/settings/profile" method="POST">
                @csrf
                @method('PUT')
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Name</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div style="margin-bottom: 1.25rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <button type="submit" style="background: var(--primary-cerulean); color: white; border: none; padding: 0.7rem 1.25rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Save login details</button>
            </form>
        </div>

        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.75rem; box-shadow: var(--shadow-sm); margin-bottom: 1.5rem;">
            <h3 style="color: var(--primary-navy); margin-top: 0; margin-bottom: 1.25rem; border-bottom: 1px solid var(--border-light); padding-bottom: 0.5rem;">Security</h3>
            <form action="/settings/password" method="POST">
                @csrf
                @method('PUT')
                <div style="margin-bottom: 1.25rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Current password</label>
                    <input type="password" name="current_password" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">New password</label>
                        <input type="password" name="password" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem;">Confirm</label>
                        <input type="password" name="password_confirmation" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                    </div>
                </div>
                <button type="submit" style="background: var(--primary-navy); color: white; border: none; padding: 0.7rem 1.25rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Update password</button>
            </form>
        </div>

        <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: var(--radius-lg); padding: 1rem 1.15rem; font-size: 0.85rem; color: #78350f; line-height: 1.45;">
            Sole-trader Tax &amp; VAT, Class 2 SSC, TA22, and personal invoice settings are hidden on this account on purpose.
            Use only the company desk for Cerulean Labs Ltd.
        </div>
    </div>
@endsection

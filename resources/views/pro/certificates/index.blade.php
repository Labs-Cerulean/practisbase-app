@extends('layouts.app')

@section('page_title', 'Certificates & Declarations')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; gap: 1rem;">
        <div>
            <h1 style="margin: 0 0 0.25rem; color: var(--primary-navy); font-size: 1.5rem;">Certificates &amp; declarations</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">Shared across Pro Medical, Architect, and Engineer — photo and expiry optional.</p>
        </div>
        <a href="/pro/certificates/create" style="background: var(--primary-cerulean); color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">+ Add</a>
    </div>
    @if(session('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">{{ session('success') }}</div>
    @endif
    @if($certs->isEmpty())
        <div style="padding: 3rem; border: 2px dashed var(--border-light); border-radius: var(--radius-md); text-align: center; background: white;">
            <p style="color: var(--text-muted);">No certificates or declarations logged yet.</p>
        </div>
    @else
        <div style="display: grid; gap: 0.75rem;">
            @foreach($certs as $cert)
                <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 1rem 1.25rem; box-shadow: var(--shadow-sm);">
                    <div style="display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                        <div>
                            <div style="font-weight: 700; color: var(--primary-navy);">{{ $cert->title }}</div>
                            <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.25rem;">
                                {{ $kinds[$cert->kind] ?? $cert->kind }}
                                · {{ $cert->subject_name ?: '—' }}
                                · issued {{ $cert->issued_on->format('d M Y') }}
                                @if($cert->expires_on)
                                    · expires {{ $cert->expires_on->format('d M Y') }}
                                    @if($cert->isExpired())
                                        <span style="color: #dc2626; font-weight: 700;"> · EXPIRED</span>
                                    @endif
                                @endif
                            </div>
                        </div>
                        @if($cert->photo_path)
                            <a href="/pro/certificates/{{ $cert->id }}/photo" style="color: var(--primary-cerulean); font-weight: 600; font-size: 0.85rem; text-decoration: none;">Photo</a>
                        @endif
                    </div>
                    @if($cert->notes)
                        <div style="margin-top: 0.5rem; font-size: 0.9rem; white-space: pre-wrap;">{{ $cert->notes }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
@endsection

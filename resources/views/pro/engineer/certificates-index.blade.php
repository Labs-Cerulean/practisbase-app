@extends('layouts.app')

@section('page_title', 'Field certificates')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem;">
        <div>
            <h1 style="margin: 0 0 0.25rem; color: var(--primary-navy); font-size: 1.5rem;">Certificates — detailed mode</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">Field certificates with attributes and checklists. <a href="/pro/certificates?mode=simple" style="color: var(--primary-cerulean); font-weight: 700;">← Back to simple certificates</a></p>
        </div>
        <a href="/pro/engineer/certificates/create{{ $projectId ? '?project_id='.$projectId : '' }}" style="background: var(--primary-cerulean); color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">+ Certificate</a>
    </div>

    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1rem;">
        <a href="/pro/certificates?mode=simple" style="padding: 0.45rem 0.85rem; border-radius: var(--radius-md); text-decoration: none; font-size: 0.85rem; font-weight: 700; background: white; color: var(--primary-navy); border: 1px solid var(--border-light);">Simple</a>
        <span style="padding: 0.45rem 0.85rem; border-radius: var(--radius-md); font-size: 0.85rem; font-weight: 700; background: var(--primary-navy); color: white;">Detailed mode</span>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">{{ session('success') }}</div>
    @endif

    @if($certs->isEmpty())
        <div style="padding: 3rem; border: 2px dashed var(--border-light); border-radius: var(--radius-md); text-align: center; background: white;">
            <p style="color: var(--text-muted); margin: 0 0 1rem;">No field certificates yet.</p>
            <a href="/pro/engineer/certificates/create{{ $projectId ? '?project_id='.$projectId : '' }}" style="color: var(--primary-cerulean); font-weight: 700; text-decoration: none;">Choose a project &amp; build a certificate</a>
        </div>
    @else
        <div style="display: grid; gap: 0.65rem;">
            @foreach($certs as $cert)
                <a href="/pro/engineer/certificates/{{ $cert->id }}" style="display: block; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.95rem 1.1rem; text-decoration: none; box-shadow: var(--shadow-sm);">
                    <div style="display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                        <div>
                            <div style="font-weight: 700; color: var(--primary-navy);">{{ $cert->title }}</div>
                            <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.2rem;">
                                @if($cert->certificate_number) {{ $cert->certificate_number }} · @endif
                                {{ $cert->project->name ?? 'No project' }}
                                @if($cert->site_address) · {{ \Illuminate\Support\Str::limit($cert->site_address, 40) }} @endif
                            </div>
                        </div>
                        <div style="font-size: 0.82rem; font-weight: 700; color: {{ $cert->isStamped() ? 'var(--primary-cerulean)' : '#92400e' }};">
                            @if($cert->isStamped())
                                Issued {{ $cert->issue_code }}
                            @else
                                Draft
                            @endif
                            @if($cert->isExpired()) · Expired @elseif($cert->expires_on) · Exp {{ $cert->expires_on->format('d M Y') }} @endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
@endsection

@extends('layouts.app')

@section('page_title', 'Certificates')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; gap: 1rem;">
        <div>
            <h1 style="margin: 0 0 0.25rem; color: var(--primary-navy); font-size: 1.35rem;">Certificates</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">Shared across Pro packages. Drafts stay editable until Stamp &amp; issue. Issued copies get a unique code + date on the PDF.</p>
        </div>
        <a href="/pro/certificates/create" style="background: var(--primary-cerulean); color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">+ Add</a>
    </div>
    @if(session('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div style="background: #fef2f2; color: #991b1b; padding: 0.85rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1rem 1.15rem; margin-bottom: 1rem; box-shadow: var(--shadow-sm);">
        <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.35rem;">Verify issue code</div>
        <form action="/pro/certificates/lookup" method="POST" style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
            @csrf
            <input type="text" name="issue_code" required placeholder="e.g. CT-7F3K-9M2P" maxlength="32"
                   style="flex: 1; min-width: 180px; padding: 0.65rem 0.85rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-family: ui-monospace, monospace; letter-spacing: 0.04em; text-transform: uppercase;">
            <button type="submit" style="background: var(--primary-navy); color: white; border: none; padding: 0.65rem 1rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Look up</button>
        </form>
    </div>

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
                                · dated {{ $cert->issued_on->format('d M Y') }}
                                @if($cert->expires_on)
                                    · expires {{ $cert->expires_on->format('d M Y') }}
                                    @if($cert->isExpired())
                                        <span style="color: #dc2626; font-weight: 700;"> · EXPIRED</span>
                                    @endif
                                @endif
                                ·
                                @if($cert->isStamped())
                                    <span style="color: #065f46; font-weight: 700;">Issued {{ $cert->stamped_at->format('d M Y H:i') }}</span>
                                    @if($cert->issue_code)
                                        · <span style="font-family: ui-monospace, monospace; letter-spacing: 0.04em; color: var(--primary-navy); font-weight: 700;">{{ $cert->issue_code }}</span>
                                    @endif
                                @else
                                    <span style="color: #b45309; font-weight: 700;">Draft</span>
                                @endif
                            </div>
                        </div>
                        <div style="display: flex; gap: 0.5rem; align-items: flex-start; flex-wrap: wrap;">
                            @if($cert->isEditable())
                                <a href="/pro/certificates/{{ $cert->id }}/edit" style="color: var(--primary-navy); font-weight: 600; font-size: 0.85rem; text-decoration: none;">Edit</a>
                                <form action="/pro/certificates/{{ $cert->id }}/stamp" method="POST" style="margin: 0;"
                                      onsubmit="return confirm('Stamp & issue this certificate? It cannot be edited afterwards.');">
                                    @csrf
                                    <button type="submit" style="background: #334155; color: white; border: none; padding: 0.35rem 0.7rem; border-radius: var(--radius-md); font-size: 0.8rem; font-weight: 700; cursor: pointer;">Stamp &amp; issue</button>
                                </form>
                            @endif
                            @if($cert->isStamped())
                                <a href="/pro/certificates/{{ $cert->id }}/pdf" style="color: var(--primary-navy); font-weight: 700; font-size: 0.85rem; text-decoration: none; border-bottom: 1px dotted var(--primary-navy);">Download PDF</a>
                            @endif
                            @if($cert->photo_path)
                                <a href="/pro/certificates/{{ $cert->id }}/photo" style="color: var(--primary-cerulean); font-weight: 600; font-size: 0.85rem; text-decoration: none;">Photo</a>
                            @endif
                        </div>
                    </div>
                    @if($cert->notes)
                        <div style="margin-top: 0.5rem; font-size: 0.9rem; white-space: pre-wrap;">{{ $cert->notes }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
@endsection

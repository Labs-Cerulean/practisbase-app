@extends('layouts.app')

@section('page_title', 'Patient Journals')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem;">
        <div>
            <h1 style="font-size: 1.5rem; color: var(--primary-navy); margin: 0 0 0.25rem;">Patient directory</h1>
            <p style="color: var(--text-muted); margin: 0; font-size: 0.9rem;">Encrypted clinical identity store — separate from billing Clients.</p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <form action="/pro/medical/vault/lock" method="POST">
                @csrf
                <button type="submit" style="background: white; border: 1px solid var(--border-light); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; cursor: pointer;">Lock vault</button>
            </form>
            <a href="/pro/medical/patients/create" style="background: var(--primary-cerulean); color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">+ Patient</a>
        </div>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">{{ session('success') }}</div>
    @endif

    <div style="margin-bottom: 1rem; padding: 0.75rem 1rem; background: #fffbeb; border-left: 4px solid #f59e0b; border-radius: var(--radius-md); color: #92400e; font-size: 0.85rem;">
        Not for real patient production data until Phase 6 legal go-live gate. Weekly backup export with code prompt ships next.
    </div>

    @if($rows->isEmpty())
        <div style="padding: 3rem; border: 2px dashed var(--border-light); border-radius: var(--radius-md); text-align: center; background: white;">
            <p style="color: var(--text-muted);">No patients in this vault yet.</p>
            <a href="/pro/medical/patients/create" style="color: var(--primary-cerulean); font-weight: 600;">Add first patient &rarr;</a>
        </div>
    @else
        <div style="display: grid; gap: 0.75rem;">
            @foreach($rows as $row)
                <a href="/pro/medical/patients/{{ $row['model']->id }}" style="display: flex; justify-content: space-between; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 1rem 1.25rem; text-decoration: none; box-shadow: var(--shadow-sm);">
                    <div>
                        <div style="font-weight: 700; color: var(--primary-navy);">{{ $row['display_name'] }}</div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">{{ $row['public_ref'] }}</div>
                    </div>
                    <div style="color: var(--primary-cerulean); font-weight: 600; font-size: 0.85rem;">Open</div>
                </a>
            @endforeach
        </div>
    @endif
@endsection

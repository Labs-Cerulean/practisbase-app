@extends('layouts.app')

@section('page_title', 'Document Stamper')

@section('content')
    <div style="max-width: 720px; margin: 0 auto;">
        <h1 style="color: var(--primary-navy); margin-top: 0;">Document Stamper</h1>
        <p style="color: var(--text-muted); line-height: 1.45;">Generate a professional stamp / declaration sheet with your warrant and logo. Attach it to filings or keep with project packs. Overlay-on-existing-PDF arrives when FPDI is evaluated.</p>

        @if($errors->any())
            <div style="background: #fef2f2; color: #991b1b; padding: 0.85rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
                @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif

        <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm);">
            <form action="/pro/architect/stamper" method="POST">
                @csrf
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Document title</label>
                    <input type="text" name="document_title" value="{{ old('document_title') }}" required placeholder="e.g. Method Statement cover" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Project reference</label>
                    <input type="text" name="project_reference" value="{{ old('project_reference') }}" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Stamp date</label>
                    <input type="date" name="stamp_date" value="{{ old('stamp_date', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
                </div>
                <div style="margin-bottom: 1.25rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Declaration text</label>
                    <textarea name="declaration" rows="4" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);" placeholder="Optional — default professional declaration used if blank.">{{ old('declaration') }}</textarea>
                </div>
                <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">
                    Signing as <strong>{{ $user->name }}</strong>
                    @if($user->warrant_number) · Warrant {{ $user->warrant_number }} @endif
                </div>
                <button type="submit" style="width: 100%; padding: 0.85rem; background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Download stamp PDF</button>
            </form>
        </div>
    </div>
@endsection

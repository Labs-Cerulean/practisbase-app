@extends('layouts.app')

@section('page_title', 'Edit Clinical Entry')

@section('content')
    <div style="max-width: 720px; margin: 0 auto; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; margin-bottom: 1.25rem;">
            <h2 style="margin: 0; color: var(--primary-navy);">Edit {{ $types[$entry->entry_type] ?? 'entry' }}</h2>
            <a href="/pro/medical/patients/{{ $patient->id }}" style="color: var(--text-muted); font-weight: 600; text-decoration: none;">Cancel</a>
        </div>
        @if($entry->isStampable())
            <div style="background: #fffbeb; color: #92400e; padding: 0.75rem 1rem; border-radius: var(--radius-md); margin-bottom: 1rem; font-size: 0.85rem;">
                Draft document — still editable. After <strong>Stamp &amp; issue</strong> it locks permanently.
            </div>
        @endif
        @if($errors->any())
            <div style="background: #fef2f2; color: #991b1b; padding: 0.85rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
                @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif
        <form action="/pro/medical/patients/{{ $patient->id }}/entries/{{ $entry->id }}" method="POST">
            @csrf
            @method('PUT')
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Type</label>
                <input type="text" value="{{ $types[$entry->entry_type] ?? $entry->entry_type }}" disabled style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: #f8fafc; color: var(--text-muted);">
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Date</label>
                <input type="date" name="entry_date" value="{{ old('entry_date', $entry->entry_date->format('Y-m-d')) }}" max="{{ date('Y-m-d') }}" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Title</label>
                <input type="text" name="title" value="{{ old('title', $payload['title'] ?? '') }}" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Body (encrypted at rest)</label>
                <textarea name="body" rows="8" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">{{ old('body', $payload['body'] ?? '') }}</textarea>
            </div>
            <button type="submit" style="width: 100%; padding: 0.85rem; background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Save changes</button>
        </form>
    </div>
@endsection

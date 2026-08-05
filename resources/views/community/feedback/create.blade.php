@extends('layouts.app')

@section('page_title', 'New feedback')

@section('content')
    <div style="max-width: 640px;">
        <h1 style="font-size: 1.5rem; color: var(--primary-navy); margin: 0 0 0.25rem;">New community note</h1>
        <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0 0 1.5rem;">
            Keep it practical. Do not include patient names, ID numbers, or clinical details — use a separate secure channel for those.
        </p>

        @if($errors->any())
            <div style="background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; border: 1px solid #fecaca;">
                @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif

        <form method="POST" action="/community/feedback" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); padding: 1.5rem;">
            @csrf
            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.45rem; color: var(--primary-navy); font-size: 0.9rem;">Type</label>
                <select name="category" required style="width: 100%; padding: 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font: inherit;">
                    @foreach($categories as $key => $label)
                        <option value="{{ $key }}" @selected(old('category', 'suggestion') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.45rem; color: var(--primary-navy); font-size: 0.9rem;">Subject</label>
                <input type="text" name="subject" value="{{ old('subject') }}" required maxlength="200" placeholder="Short title" style="width: 100%; padding: 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font: inherit;">
            </div>
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.45rem; color: var(--primary-navy); font-size: 0.9rem;">Details</label>
                <textarea name="body" required maxlength="5000" rows="8" placeholder="What happened, what you expected, or what you wish existed…" style="width: 100%; padding: 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font: inherit; resize: vertical;">{{ old('body') }}</textarea>
            </div>
            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
                <button type="submit" style="background: var(--primary-cerulean); color: white; border: none; padding: 0.7rem 1.25rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.95rem; cursor: pointer;">Send to community desk</button>
                <a href="/community/feedback" style="color: var(--text-muted); font-weight: 600; text-decoration: none;">Cancel</a>
            </div>
        </form>
    </div>
@endsection

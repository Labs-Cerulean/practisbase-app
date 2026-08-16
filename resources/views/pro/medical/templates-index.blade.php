@extends('layouts.app')

@section('page_title', 'Note templates')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem;">
        <div>
            <a href="/pro/medical/patients" style="color: var(--text-muted); font-weight: 600; text-decoration: none; font-size: 0.85rem;">&larr; Patients</a>
            <h1 style="margin: 0.4rem 0 0.35rem; color: var(--primary-navy); font-size: 1.45rem;">Note templates</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem; max-width: 36rem; line-height: 1.45;">
                Form layouts for patient notes. Field values stay encrypted in each note.
            </p>
        </div>
        <a href="/pro/medical/templates/create" style="background: var(--primary-cerulean); color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 700; text-decoration: none;">+ New template</a>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 0.85rem 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div style="background: #fef2f2; color: #991b1b; padding: 0.85rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.15rem; margin-bottom: 1.25rem; box-shadow: var(--shadow-sm);">
        <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.65rem;">Starters (built-in)</div>
        <div style="display: grid; gap: 0.65rem;">
            @foreach($builtins as $key => $label)
                <div style="display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; align-items: center; padding: 0.75rem; background: #f8fafc; border-radius: var(--radius-md); border: 1px solid var(--border-light);">
                    <div>
                        <div style="font-weight: 700; color: var(--primary-navy);">{{ $label }}</div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">
                            {{ collect($builtinFields[$key] ?? [])->pluck('label')->implode(' · ') }}
                            @if($defaultKey === $key)
                                · <span style="color: #065f46; font-weight: 700;">Default</span>
                            @endif
                        </div>
                    </div>
                    <a href="/pro/medical/templates/create?from={{ urlencode($key) }}"
                       style="padding: 0.45rem 0.85rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); color: var(--primary-navy); font-weight: 700; text-decoration: none; font-size: 0.85rem;">
                        Duplicate &amp; customise
                    </a>
                </div>
            @endforeach
        </div>
    </div>

    <h2 style="margin: 0 0 0.75rem; color: var(--primary-navy); font-size: 1.1rem;">Your templates</h2>
    @if($templates->isEmpty())
        <div style="padding: 2rem; border: 2px dashed var(--border-light); border-radius: var(--radius-md); text-align: center; background: white; color: var(--text-muted);">
            No custom templates yet. Duplicate a starter or create one from scratch.
        </div>
    @else
        <div style="display: grid; gap: 0.75rem;">
            @foreach($templates as $template)
                <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 1rem; box-shadow: var(--shadow-sm);">
                    <div style="display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; align-items: flex-start;">
                        <div>
                            <div style="font-weight: 700; color: var(--primary-navy);">{{ $template->name }}</div>
                            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">
                                {{ collect($template->normalizedFields())->pluck('label')->implode(' · ') }}
                                @if($defaultKey === $template->catalogueKey())
                                    · <span style="color: #065f46; font-weight: 700;">Default</span>
                                @endif
                            </div>
                        </div>
                        <div style="display: flex; gap: 0.45rem; flex-wrap: wrap;">
                            <a href="/pro/medical/templates/{{ $template->id }}/edit"
                               style="padding: 0.4rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); color: var(--primary-navy); font-weight: 700; text-decoration: none; font-size: 0.8rem;">Edit</a>
                            <a href="/pro/medical/templates/create?from={{ urlencode($template->catalogueKey()) }}"
                               style="padding: 0.4rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); color: var(--primary-navy); font-weight: 700; text-decoration: none; font-size: 0.8rem;">Duplicate</a>
                            <form action="/pro/medical/templates/{{ $template->id }}" method="POST" onsubmit="return confirm('Delete this template? Past notes keep their saved fields.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="padding: 0.4rem 0.75rem; border: 1px solid #fecaca; background: #fef2f2; color: #991b1b; border-radius: var(--radius-md); font-weight: 700; cursor: pointer; font-size: 0.8rem;">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <p style="margin-top: 1.25rem; font-size: 0.85rem; color: var(--text-muted);">
        Set your default template in <a href="/settings#practice" style="color: var(--primary-cerulean); font-weight: 600;">Settings</a>.
    </p>
@endsection

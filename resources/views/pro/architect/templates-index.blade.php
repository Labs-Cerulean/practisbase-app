@extends('layouts.app')

@section('page_title', 'BCA templates')

@section('content')
    <div style="margin-bottom: 1.25rem;">
        <h1 style="margin: 0 0 0.25rem; color: var(--primary-navy); font-size: 1.5rem;">BCA templates and architect declarations</h1>
        <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem; max-width: 44rem;">
            Choose a template first. Fillable templates open a form matched to that document, with cascading client, project and PA selectors.
            Official blanks stay available for download.
            BCA registers:
            <a href="{{ $registerUrls['contractor'] }}" target="_blank" rel="noopener">Contractors</a>,
            <a href="{{ $registerUrls['sto'] }}" target="_blank" rel="noopener">STOs</a>,
            <a href="{{ $registerUrls['mason'] }}" target="_blank" rel="noopener">Masons</a>.
        </p>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">{{ session('success') }}</div>
    @endif

    @foreach($groups as $group => $templates)
        <h2 style="margin: 0 0 0.65rem; font-size: 1.1rem; color: var(--primary-navy);">{{ $group }}</h2>
        <div style="display: grid; gap: 0.75rem; margin-bottom: 1.5rem;">
            @foreach($templates as $tpl)
                <article style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.1rem 1.2rem; box-shadow: var(--shadow-sm);">
                    <div style="display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; align-items: flex-start;">
                        <div style="flex: 1; min-width: 220px;">
                            <div style="font-weight: 700; color: var(--primary-navy);">{{ $tpl['title'] }}</div>
                            <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.25rem; line-height: 1.45;">{{ $tpl['description'] }}</div>
                        </div>
                        <div style="display: flex; gap: 0.45rem; flex-wrap: wrap;">
                            @if($tpl['blank_file'])
                                <a href="/pro/architect/templates/{{ $tpl['key'] }}/blank" style="background: white; border: 1px solid var(--border-light); color: var(--primary-navy); padding: 0.5rem 0.85rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none; font-size: 0.85rem;">Download blank</a>
                            @endif
                            @if($tpl['fillable'])
                                <a href="/pro/architect/templates/{{ $tpl['key'] }}/fill" style="background: #3f6212; color: white; padding: 0.5rem 0.85rem; border-radius: var(--radius-md); font-weight: 700; text-decoration: none; font-size: 0.85rem;">Fill this template</a>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endforeach
@endsection

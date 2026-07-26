@extends('layouts.app')

@section('page_title', 'Engineering Projects')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; gap: 1rem;">
        <div>
            <h1 style="margin: 0 0 0.25rem; color: var(--primary-navy); font-size: 1.5rem;">Engineering projects</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">Project register by discipline and phase. EMS/BMS document templates still deferred pending domain expert.</p>
        </div>
        <a href="/pro/engineer/projects/create" style="background: var(--primary-cerulean); color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">+ Project</a>
    </div>
    @if(session('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">{{ session('success') }}</div>
    @endif
    @if($projects->isEmpty())
        <div style="padding: 3rem; border: 2px dashed var(--border-light); border-radius: var(--radius-md); text-align: center; background: white;">
            <p style="color: var(--text-muted);">No engineering projects yet.</p>
        </div>
    @else
        <div style="display: grid; gap: 0.75rem;">
            @foreach($projects as $project)
                <div style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 1rem 1.25rem; box-shadow: var(--shadow-sm);">
                    <div style="font-weight: 700; color: var(--primary-navy);">{{ $project->name }}</div>
                    <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.25rem;">
                        {{ $disciplines[$project->discipline] ?? $project->discipline }}
                        · {{ $phases[$project->phase] ?? $project->phase }}
                        @if($project->reference_code) · {{ $project->reference_code }} @endif
                    </div>
                    @if($project->notes)
                        <div style="margin-top: 0.5rem; font-size: 0.9rem; white-space: pre-wrap;">{{ $project->notes }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
@endsection

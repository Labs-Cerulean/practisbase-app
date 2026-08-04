@extends('layouts.app')

@section('page_title', 'Condition reports')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem;">
        <div>
            <h1 style="margin: 0 0 0.25rem; color: var(--primary-navy); font-size: 1.5rem;">Condition reports</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">Cross-project register. New reports start from a project so details and refs auto-fill.</p>
        </div>
        <a href="/pro/architect/condition-reports/create{{ $projectId ? '?project_id='.$projectId : '' }}" style="background: #3f6212; color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">+ Condition report</a>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">{{ session('success') }}</div>
    @endif

    @if($reports->isEmpty())
        <div style="padding: 3rem; border: 2px dashed var(--border-light); border-radius: var(--radius-md); text-align: center; background: white;">
            <p style="color: var(--text-muted); margin: 0 0 1rem;">No condition reports yet.</p>
            <a href="/pro/architect/condition-reports/create{{ $projectId ? '?project_id='.$projectId : '' }}" style="color: #3f6212; font-weight: 700; text-decoration: none;">Choose a project &amp; build a report</a>
        </div>
    @else
        <div style="display: grid; gap: 0.65rem;">
            @foreach($reports as $report)
                <a href="/pro/architect/condition-reports/{{ $report->id }}" style="display: block; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.95rem 1.1rem; text-decoration: none; box-shadow: var(--shadow-sm);">
                    <div style="display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                        <div>
                            <div style="font-weight: 700; color: var(--primary-navy);">{{ $report->title }}</div>
                            <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.2rem;">
                                {{ $report->typeLabel() }}
                                @if($report->report_number) · {{ $report->report_number }} @endif
                                · {{ $report->project->name ?? 'No project' }}
                                @if($report->inspected_address) · {{ \Illuminate\Support\Str::limit($report->inspected_address, 40) }} @endif
                            </div>
                        </div>
                        <div style="font-size: 0.82rem; font-weight: 700; color: {{ $report->isStamped() ? '#3f6212' : '#92400e' }};">
                            @if($report->isStamped())
                                Issued {{ $report->issue_code }}
                            @else
                                Draft
                            @endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
@endsection

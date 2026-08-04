@extends('layouts.app')

@section('page_title', $report->title)

@section('content')
    @include('pro.engineer._field-styles')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem;">
        <div>
            <a href="/pro/architect/condition-reports" style="color: var(--text-muted); text-decoration: none; font-weight: 600;">← Condition reports</a>
            <h1 style="margin: 0.4rem 0 0.25rem; color: var(--primary-navy); font-size: 1.5rem;">{{ $report->title }}</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">
                {{ $report->typeLabel() }}
                @if($report->report_number) · {{ $report->report_number }} @endif
                · {{ $report->isStamped() ? 'Issued '.$report->issue_code : 'Draft' }}
                @if($report->project) · {{ $report->project->name }} @endif
            </p>
        </div>
        <div class="eng-desktop-actions" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            @if($report->isEditable())
                <a href="/pro/architect/condition-reports/{{ $report->id }}/edit" style="background: white; border: 1px solid var(--border-light); color: var(--primary-navy); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">Edit</a>
                <form method="POST" action="/pro/architect/condition-reports/{{ $report->id }}/stamp" onsubmit="return confirm('Stamp & issue this condition report? It will lock and cannot be edited.');">
                    @csrf
                    <button type="submit" style="background: #3f6212; color: white; border: none; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Stamp & issue</button>
                </form>
            @else
                <a href="/pro/architect/condition-reports/{{ $report->id }}/pdf" style="background: #3f6212; color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">Download PDF</a>
            @endif
        </div>
    </div>

    <div class="eng-sticky-actions eng-mobile-actions">
        @if($report->isEditable())
            <a href="/pro/architect/condition-reports/{{ $report->id }}/edit" style="background: white; border: 1px solid var(--border-light); color: var(--primary-navy); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">Edit</a>
            <form method="POST" action="/pro/architect/condition-reports/{{ $report->id }}/stamp" onsubmit="return confirm('Stamp & issue this condition report? It will lock and cannot be edited.');" style="flex: 1 1 auto; display: flex;">
                @csrf
                <button type="submit" style="flex: 1; background: #3f6212; color: white; border: none; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Stamp & issue</button>
            </form>
        @else
            <a href="/pro/architect/condition-reports/{{ $report->id }}/pdf" style="flex: 1; background: #3f6212; color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">Download PDF</a>
        @endif
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div style="background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <div style="display: grid; gap: 1rem; max-width: 860px;">
        <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm);">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.75rem; font-size: 0.88rem;">
                <div><div style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700;">INSPECTED</div>{{ optional($report->inspected_on)->format('d M Y') ?: '—' }}</div>
                <div><div style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700;">ISSUED</div>{{ $report->issued_on->format('d M Y') }}</div>
                @if($report->paApplication)
                    <div><div style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700;">PA</div>{{ $report->paApplication->displayLabel() }}</div>
                @endif
            </div>
            <div style="margin-top: 1rem; padding-top: 0.85rem; border-top: 1px solid #e2e8f0; font-size: 0.9rem; color: var(--primary-navy); display: grid; gap: 0.35rem;">
                @if($report->client_name)<div><strong>{{ $report->client_name }}</strong></div>@endif
                @if($report->client_address)<div style="color: var(--text-muted); font-size: 0.85rem;">{{ $report->client_address }}</div>@endif
                @if($report->project_description)<div style="margin-top: 0.35rem; white-space: pre-wrap;">{{ $report->project_description }}</div>@endif
                @if($report->development_address)<div>Development: {{ $report->development_address }}</div>@endif
                @if($report->inspected_address)<div>Inspected: {{ $report->inspected_address }}</div>@endif
            </div>
        </section>

        @foreach($payload['sections'] as $section)
            <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm);">
                @if($section['heading'])
                    <h2 style="margin: 0 0 0.55rem; font-size: 1.05rem; color: var(--primary-navy);">{{ $section['heading'] }}</h2>
                @endif
                <div style="white-space: pre-wrap; font-size: 0.92rem; line-height: 1.55; color: var(--primary-navy);">{{ $section['body'] ?: '—' }}</div>
            </section>
        @endforeach

        @if($payload['sketch_ref'])
            <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm);">
                <h2 style="margin: 0 0 0.55rem; font-size: 1.05rem; color: var(--primary-navy);">6. Sketch plan</h2>
                <div style="font-size: 0.92rem; color: var(--primary-navy);">{{ $payload['sketch_ref'] }}</div>
            </section>
        @endif

        @if(count($payload['defects']))
            <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm);">
                <h2 style="margin: 0 0 0.75rem; font-size: 1.05rem; color: var(--primary-navy);">{{ $payload['defects_heading'] ?: 'Observed defects' }}</h2>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                        <thead>
                            <tr style="text-align: left; color: var(--text-muted); font-size: 0.72rem;">
                                <th style="padding: 0.35rem 0.4rem; border-bottom: 1px solid var(--border-light);">Location</th>
                                <th style="padding: 0.35rem 0.4rem; border-bottom: 1px solid var(--border-light);">Defect</th>
                                <th style="padding: 0.35rem 0.4rem; border-bottom: 1px solid var(--border-light);">Photo</th>
                                <th style="padding: 0.35rem 0.4rem; border-bottom: 1px solid var(--border-light);">Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payload['defects'] as $row)
                                <tr>
                                    <td style="padding: 0.4rem; border-bottom: 1px dashed var(--border-light);">{{ $row['location'] }}</td>
                                    <td style="padding: 0.4rem; border-bottom: 1px dashed var(--border-light); font-weight: 650;">{{ $row['defect'] }}</td>
                                    <td style="padding: 0.4rem; border-bottom: 1px dashed var(--border-light);">{{ $row['photo_ref'] }}</td>
                                    <td style="padding: 0.4rem; border-bottom: 1px dashed var(--border-light); color: var(--text-muted);">{{ $row['notes'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        @if($report->photos->isNotEmpty())
            <section style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.2rem; box-shadow: var(--shadow-sm);">
                <h2 style="margin: 0 0 0.75rem; font-size: 1.05rem; color: var(--primary-navy);">Photos / annexes</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 0.65rem;">
                    @foreach($report->photos as $photo)
                        <a href="/pro/architect/condition-reports/{{ $report->id }}/photos/{{ $photo->id }}" target="_blank" style="display: block; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.5rem; text-decoration: none; font-size: 0.78rem; color: #3f6212; font-weight: 600;">
                            Photo {{ $loop->iteration }}@if($photo->caption) — {{ $photo->caption }}@endif
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection

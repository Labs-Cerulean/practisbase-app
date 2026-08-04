<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $report->title }}</title>
    <style>
        body { font-family: DejaVu Sans, Helvetica, Arial, sans-serif; color: #0f172a; font-size: 11px; margin: 0; padding: 24px 28px; line-height: 1.45; }
        .practice { font-size: 15px; font-weight: bold; margin: 0; }
        .practice-sub { font-size: 9px; color: #64748b; margin-top: 1px; }
        h1 { font-size: 16px; margin: 14px 0 6px; border-bottom: 2px solid #0f172a; padding-bottom: 5px; }
        .meta { color: #64748b; font-size: 9px; margin-bottom: 10px; }
        .banner { background: #0f172a; color: #fff; padding: 7px 10px; font-size: 10px; font-weight: bold; margin-bottom: 12px; }
        .grid { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .grid td { vertical-align: top; padding: 3px 6px 3px 0; }
        .label { font-size: 8px; text-transform: uppercase; color: #64748b; font-weight: bold; letter-spacing: 0.03em; }
        .value { font-size: 11px; }
        h2 { font-size: 12px; margin: 14px 0 6px; color: #0f172a; }
        table.defects { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.defects td, table.defects th { border-bottom: 1px solid #e2e8f0; padding: 4px 6px; text-align: left; font-size: 10px; }
        table.defects th { font-size: 8px; text-transform: uppercase; color: #64748b; }
        .section-body { white-space: pre-wrap; font-size: 10px; margin: 0; }
        .auth-box { margin-top: 16px; border: 2px solid #0f172a; padding: 10px 12px; background: #f8fafc; }
        .auth-code { font-size: 16px; font-weight: bold; letter-spacing: 0.06em; font-family: DejaVu Sans Mono, monospace; }
        .sign { margin-top: 22px; }
        .sign-line { border-top: 1px solid #94a3b8; width: 240px; margin-top: 36px; padding-top: 5px; font-size: 9px; color: #64748b; }
        .footer { margin-top: 16px; font-size: 8px; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 8px; white-space: pre-wrap; }
    </style>
</head>
<body>
    @if($user->logoDataUri())
        <img src="{{ $user->logoDataUri() }}" style="max-height: 52px; max-width: 160px; margin-bottom: 8px;">
    @endif
    <div class="practice">{{ $user->name }}</div>
    @if($user->postnominalsLine())
        <div class="practice-sub">{{ $user->postnominalsLine() }}</div>
    @endif
    @if($user->warrant_type || $user->warrant_number)
        <div class="practice-sub">Warrant: {{ trim(($user->warrant_type ?? '').' '.($user->warrant_number ?? '')) }}</div>
    @endif
    @if($user->email)
        <div class="practice-sub">{{ $user->email }}</div>
    @endif

    <h1>{{ $report->title }}</h1>
    <div class="banner">Architect condition report · Seventh Schedule · issued copy</div>
    <div class="meta">
        @if($report->report_number) Ref. {{ $report->report_number }} · @endif
        Document date {{ $report->issued_on->format('d M Y') }}
        @if($report->inspected_on) · Inspected {{ $report->inspected_on->format('d M Y') }} @endif
        · Issue code {{ $report->issue_code }}
    </div>

    <table class="grid">
        <tr>
            <td style="width: 50%;">
                <div class="label">Client / applicant</div>
                <div class="value"><strong>{{ $report->client_name ?: '—' }}</strong></div>
                @if($report->client_address)<div class="value">{{ $report->client_address }}</div>@endif
                @if($report->project_description)
                    <div class="label" style="margin-top: 8px;">Project description</div>
                    <div class="value" style="white-space: pre-wrap;">{{ $report->project_description }}</div>
                @endif
            </td>
            <td style="width: 50%;">
                <div class="label">Development / site</div>
                <div class="value">{{ $report->development_address ?: '—' }}</div>
                <div class="label" style="margin-top: 8px;">Inspected property</div>
                <div class="value">{{ $report->inspected_address ?: '—' }}</div>
                @if($report->paApplication)
                    <div class="label" style="margin-top: 8px;">PA</div>
                    <div class="value">{{ $report->paApplication->displayLabel() }}</div>
                @endif
                @if($report->project)
                    <div class="label" style="margin-top: 8px;">Project</div>
                    <div class="value">{{ $report->project->name }}</div>
                @endif
            </td>
        </tr>
    </table>

    @foreach($payload['sections'] as $section)
        @if($section['heading'])
            <h2>{{ $section['heading'] }}</h2>
        @endif
        <p class="section-body">{{ $section['body'] }}</p>
    @endforeach

    @if($payload['sketch_ref'])
        <h2>6. Sketch plan of property</h2>
        <p class="section-body">{{ $payload['sketch_ref'] }}</p>
    @endif

    @if(count($payload['defects']))
        @php
            $photosByRow = $report->photos->groupBy(fn ($p) => (string) ($p->linked_row_id ?? ''));
        @endphp
        <h2>{{ $payload['defects_heading'] ?: '7. List of observed defects' }}</h2>
        <table class="defects">
            <tr>
                <th style="width: 12%;">Id</th>
                <th style="width: 20%;">Location</th>
                <th style="width: 28%;">Defect</th>
                <th style="width: 14%;">Photo</th>
                <th>Notes</th>
            </tr>
            @foreach($payload['defects'] as $row)
                @php
                    $linked = $photosByRow->get((string) ($row['id'] ?? ''), collect());
                    $photoLabels = $linked->map(function ($photo) use ($report) {
                        $n = $report->photos->search(fn ($p) => $p->id === $photo->id);
                        return 'P'.(($n === false ? 0 : $n) + 1);
                    })->implode(', ');
                @endphp
                <tr>
                    <td>{{ $row['id'] ?? '' }}</td>
                    <td>{{ $row['location'] }}</td>
                    <td>{{ $row['defect'] }}</td>
                    <td>{{ $photoLabels ?: ($row['photo_ref'] ?? '') }}</td>
                    <td>{{ $row['notes'] }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <div class="auth-box">
        <div class="label">Authenticity mark</div>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 55%;">
                    <div class="label">Unique issue code</div>
                    <div class="auth-code">{{ $report->issue_code }}</div>
                </td>
                <td>
                    <div class="label">Stamped</div>
                    <div style="font-size: 13px; font-weight: bold;">{{ optional($report->stamped_at)->format('d M Y H:i') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="sign">
        @if($user->clinicalStampDataUri())
            <img src="{{ $user->clinicalStampDataUri() }}" style="max-height: 70px; max-width: 160px;">
        @endif
        <div class="sign-line">
            {{ $user->name }}@if($user->postnominalsLine()), {{ $user->postnominalsLine() }}@endif
            @if($user->warrant_number)<br>Warrant No. {{ $user->warrant_number }}@endif
        </div>
    </div>

    @if($payload['legal_footer'])
        <div class="footer">{{ $payload['legal_footer'] }}</div>
    @endif
</body>
</html>

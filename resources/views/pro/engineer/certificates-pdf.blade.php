<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $certificate->title }}</title>
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
        table.attrs, table.check { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.attrs td, table.check td, table.check th { border-bottom: 1px solid #e2e8f0; padding: 4px 6px; text-align: left; font-size: 10px; }
        table.check th { font-size: 8px; text-transform: uppercase; color: #64748b; }
        .highlight { background: #0f172a; color: #fff; padding: 10px 12px; margin: 10px 0; }
        .highlight .hl { font-size: 8px; text-transform: uppercase; letter-spacing: 0.04em; opacity: 0.85; }
        .highlight .hv { font-size: 14px; font-weight: bold; margin-top: 2px; }
        .section-body { white-space: pre-wrap; font-size: 10px; margin: 0; }
        .auth-box { margin-top: 16px; border: 2px solid #0f172a; padding: 10px 12px; background: #f8fafc; }
        .auth-code { font-size: 16px; font-weight: bold; letter-spacing: 0.06em; font-family: DejaVu Sans Mono, monospace; }
        .sign { margin-top: 22px; }
        .sign-line { border-top: 1px solid #94a3b8; width: 240px; margin-top: 36px; padding-top: 5px; font-size: 9px; color: #64748b; }
        .footer { margin-top: 16px; font-size: 8px; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 8px; white-space: pre-wrap; }
    </style>
</head>
<body>
    @if($user->logoDataUriForPdf())
        <img src="{{ $user->logoDataUriForPdf() }}" style="max-height: 52px; max-width: 160px; margin-bottom: 8px;">
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

    <h1>{{ $certificate->title }}</h1>
    <div class="banner">Engineer field certificate · issued copy</div>
    <div class="meta">
        @if($certificate->certificate_number) No. {{ $certificate->certificate_number }} · @endif
        Document date {{ $certificate->issued_on->format('d M Y') }}
        @if($certificate->inspected_on) · Inspected {{ $certificate->inspected_on->format('d M Y') }} @endif
        @if($certificate->expires_on) · Expires {{ $certificate->expires_on->format('d M Y') }} @endif
        @if($certificate->next_inspection_on) · Next inspection {{ $certificate->next_inspection_on->format('d M Y') }} @endif
        · Issue code {{ $certificate->issue_code }}
    </div>

    <table class="grid">
        <tr>
            <td style="width: 50%;">
                <div class="label">Certificate holder</div>
                <div class="value"><strong>{{ $certificate->holder_name ?: '—' }}</strong></div>
                @if($certificate->holder_address)<div class="value">{{ $certificate->holder_address }}</div>@endif
                @if($certificate->contact_person || $certificate->contact_phone)
                    <div class="value">{{ $certificate->contact_person }} {{ $certificate->contact_phone }}</div>
                @endif
            </td>
            <td style="width: 50%;">
                <div class="label">Site / work location</div>
                <div class="value">{{ $certificate->site_address ?: '—' }}</div>
                @if($certificate->outcome)
                    <div class="label" style="margin-top: 8px;">Outcome</div>
                    <div class="value"><strong>{{ $certificate->outcome }}</strong></div>
                @endif
                @if($certificate->project)
                    <div class="label" style="margin-top: 8px;">Project</div>
                    <div class="value">{{ $certificate->project->name }}</div>
                @endif
            </td>
        </tr>
    </table>

    @if(count($payload['attributes']))
        <h2>{{ $payload['subject_heading'] ?: 'Subject' }}</h2>
        <table class="attrs">
            @foreach($payload['attributes'] as $row)
                <tr>
                    <td style="width: 38%; color: #64748b;">{{ $row['label'] }}</td>
                    <td>{{ $row['value'] }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @if($payload['highlight_label'] || $payload['highlight_value'])
        <div class="highlight">
            <div class="hl">{{ $payload['highlight_label'] }}</div>
            <div class="hv">{{ $payload['highlight_value'] }}</div>
        </div>
    @endif

    @if(count($payload['checklist']))
        <h2>{{ $payload['checklist_heading'] ?: 'Inspection checklist' }}</h2>
        <table class="check">
            <tr>
                <th style="width: 46%;">Item</th>
                <th style="width: 18%;">Outcome</th>
                <th>Comments</th>
            </tr>
            @foreach($payload['checklist'] as $row)
                <tr>
                    <td>{{ $row['item'] }}</td>
                    <td>{{ $row['outcome'] }}</td>
                    <td>{{ $row['comments'] }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @foreach($payload['sections'] as $section)
        @if($section['heading'])
            <h2>{{ $section['heading'] }}</h2>
        @endif
        <p class="section-body">{{ $section['body'] }}</p>
    @endforeach

    <div class="auth-box">
        <div class="label">Authenticity mark</div>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 55%;">
                    <div class="label">Unique issue code</div>
                    <div class="auth-code">{{ $certificate->issue_code }}</div>
                </td>
                <td>
                    <div class="label">Stamped</div>
                    <div style="font-size: 13px; font-weight: bold;">{{ optional($certificate->stamped_at)->format('d M Y H:i') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="sign">
        @if($user->clinicalStampDataUriForPdf())
            <img src="{{ $user->clinicalStampDataUriForPdf() }}" style="max-height: 70px; max-width: 160px;">
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

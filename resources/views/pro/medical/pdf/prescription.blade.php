<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $documentTitle }}</title>
    <style>
        @page { margin: 18mm 16mm 16mm 16mm; }
        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            color: #111827;
            font-size: 12px;
            margin: 0;
            padding: 0;
            line-height: 1.35;
        }
        .pad-header {
            text-align: center;
            margin-bottom: 22px;
        }
        .pad-logo {
            max-height: 48px;
            max-width: 140px;
            margin-bottom: 8px;
        }
        .pad-name {
            font-size: 18px;
            font-weight: bold;
            color: #0f172a;
            margin: 0 0 3px;
            letter-spacing: 0.01em;
        }
        .pad-line {
            font-size: 11px;
            color: #1e293b;
            margin: 1px 0;
        }
        .pad-muted {
            font-size: 10px;
            color: #475569;
            margin: 1px 0;
        }
        .patient-row {
            margin: 18px 0 8px;
            width: 100%;
            border-collapse: collapse;
        }
        .patient-row td {
            vertical-align: bottom;
            padding: 0;
        }
        .field-label {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
            white-space: nowrap;
            padding-right: 6px;
        }
        .field-value {
            border-bottom: 1px solid #334155;
            padding: 0 4px 2px;
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
            min-height: 18px;
        }
        .rx-wrap {
            margin-top: 14px;
            min-height: 320px;
        }
        .rx-table {
            width: 100%;
            border-collapse: collapse;
        }
        .rx-glyph {
            width: 52px;
            vertical-align: top;
            padding-top: 2px;
        }
        .rx-symbol {
            font-family: Times-Roman, "Times New Roman", serif;
            font-size: 34px;
            font-weight: bold;
            font-style: italic;
            color: #0f172a;
            line-height: 1;
            letter-spacing: -0.02em;
        }
        .rx-content {
            vertical-align: top;
            padding-left: 8px;
        }
        .rx-item {
            margin-bottom: 16px;
            page-break-inside: avoid;
        }
        .rx-num {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
            display: inline;
        }
        .rx-text {
            font-size: 13px;
            color: #0f172a;
            line-height: 1.45;
        }
        .rx-detail {
            font-size: 11px;
            color: #334155;
            margin-top: 3px;
            padding-left: 16px;
        }
        .notes {
            margin-top: 10px;
            font-size: 11px;
            color: #334155;
            white-space: pre-wrap;
            line-height: 1.45;
        }
        .sign-block {
            margin-top: 36px;
            width: 100%;
            border-collapse: collapse;
        }
        .sign-block td {
            vertical-align: bottom;
        }
        .sign-space {
            height: 54px;
        }
        .sign-rule {
            border-top: 1px solid #334155;
            width: 220px;
            margin-left: auto;
            padding-top: 4px;
            font-size: 10px;
            color: #475569;
            text-align: right;
        }
        .prescriber-hint {
            font-size: 9px;
            color: #64748b;
            text-align: right;
            margin-top: 4px;
        }
        .pad-footer {
            margin-top: 28px;
            border-top: 1.5px solid #0f172a;
            padding-top: 10px;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }
        .footer-table td {
            vertical-align: middle;
            padding: 0;
        }
        .check {
            display: inline-block;
            width: 11px;
            height: 11px;
            border: 1.2px solid #0f172a;
            margin-right: 5px;
            text-align: center;
            font-size: 9px;
            line-height: 10px;
            vertical-align: middle;
        }
        .check-on {
            background: #0f172a;
            color: #fff;
            font-weight: bold;
        }
        .check-label {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
            margin-right: 16px;
        }
        .auth-quiet {
            margin-top: 10px;
            font-size: 8px;
            color: #64748b;
            line-height: 1.4;
        }
        .auth-quiet strong {
            color: #334155;
            font-family: DejaVu Sans Mono, monospace;
            letter-spacing: 0.04em;
        }
        .dob-note {
            font-size: 9px;
            color: #64748b;
            margin-top: 4px;
        }
    </style>
</head>
<body>
    @php
        $medicines = \App\Models\ClinicalEntry::medicinesFromPayload($entryPayload ?? []);
        $hasStructured = ! empty($entryPayload['medicines']) && is_array($entryPayload['medicines']);
        $generalNotes = $hasStructured ? trim((string) ($entryPayload['body'] ?? '')) : '';
        $dispenseMode = strtolower(trim((string) ($entryPayload['dispense_mode'] ?? 'single')));
        if (! in_array($dispenseMode, ['single', 'repeat'], true)) {
            $dispenseMode = 'single';
        }
        $patientName = trim((string) ($patientPayload['display_name'] ?? 'Patient'));
        $regNo = trim((string) ($user->warrant_number ?? ''));
        $warrantType = trim((string) ($user->warrant_type ?? ''));
        $clinicPhone = trim((string) ($user->clinic_phone ?? ''));
        $clinicAddress = trim((string) ($user->clinic_address ?? ''));
        $stampUri = $user->clinicalStampDataUri();
    @endphp

    <div class="pad-header">
        @if($user->logoDataUri())
            <img class="pad-logo" src="{{ $user->logoDataUri() }}" alt="">
        @endif
        <div class="pad-name">{{ $user->name }}</div>
        @if($user->postnominalsLine())
            <div class="pad-line">{{ $user->postnominalsLine() }}</div>
        @endif
        @if($user->profession)
            <div class="pad-muted">{{ $user->profession }}</div>
        @endif
        @if($regNo !== '')
            <div class="pad-line">
                Medical Reg Nº: {{ $regNo }}
                @if($warrantType !== '')
                    <span class="pad-muted">({{ $warrantType }})</span>
                @endif
            </div>
        @elseif($warrantType !== '')
            <div class="pad-line">{{ $warrantType }}</div>
        @endif
        @if($user->email)
            <div class="pad-muted">{{ $user->email }}</div>
        @endif
        @if($clinicPhone !== '')
            <div class="pad-muted">Tel: {{ $clinicPhone }}</div>
        @endif
        @if($clinicAddress !== '')
            <div class="pad-muted" style="white-space: pre-line;">{{ $clinicAddress }}</div>
        @endif
    </div>

    <table class="patient-row">
        <tr>
            <td style="width: 62%;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td class="field-label">Patient's Name:</td>
                        <td class="field-value" style="width: 100%;">{{ $patientName }}</td>
                    </tr>
                </table>
            </td>
            <td style="width: 8%;"></td>
            <td style="width: 30%;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td class="field-label">Date:</td>
                        <td class="field-value" style="width: 100%;">{{ $entry->entry_date->format('d/m/Y') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    @if(!empty($patientPayload['date_of_birth']))
        <div class="dob-note">DOB: {{ \Illuminate\Support\Carbon::parse($patientPayload['date_of_birth'])->format('d/m/Y') }}</div>
    @endif

    <div class="rx-wrap">
        <table class="rx-table">
            <tr>
                <td class="rx-glyph">
                    <div class="rx-symbol">Rx</div>
                </td>
                <td class="rx-content">
                    @forelse($medicines as $i => $med)
                        <div class="rx-item">
                            <div class="rx-text">
                                <span class="rx-num">{{ $i + 1 }}.</span>
                                {{ $med['name'] }}
                                @if($med['strength'] !== '')
                                    {{ $med['strength'] }}
                                @endif
                                @if($med['dose'] !== '')
                                    {{ $med['dose'] }}
                                @endif
                            </div>
                            @if($med['quantity'] !== '' || $med['instructions'] !== '')
                                <div class="rx-detail">
                                    @if($med['quantity'] !== '')
                                        Qty: {{ $med['quantity'] }}
                                    @endif
                                    @if($med['quantity'] !== '' && $med['instructions'] !== '')
                                        ·
                                    @endif
                                    @if($med['instructions'] !== '')
                                        {{ $med['instructions'] }}
                                    @endif
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="rx-text" style="white-space: pre-wrap;">{{ $entryPayload['body'] ?? '' }}</div>
                    @endforelse

                    @if($generalNotes !== '')
                        <div class="notes">{{ $generalNotes }}</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <table class="sign-block">
        <tr>
            <td style="width: 45%;"></td>
            <td style="width: 55%;">
                @if($stampUri)
                    <div style="text-align: right; margin-bottom: 4px;">
                        <img src="{{ $stampUri }}" alt="Clinical stamp" style="max-height: 95px; max-width: 240px;">
                    </div>
                @else
                    <div class="sign-space"></div>
                @endif
                <div class="sign-rule">Signature</div>
                <div class="prescriber-hint">
                    {{ $user->name }}
                    @if($user->postnominalsLine())
                        · {{ $user->postnominalsLine() }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="pad-footer">
        <table class="footer-table">
            <tr>
                <td style="width: 55%;">
                    <span class="check {{ $dispenseMode === 'single' ? 'check-on' : '' }}">{{ $dispenseMode === 'single' ? '✓' : '' }}</span>
                    <span class="check-label">Single</span>
                    <span class="check {{ $dispenseMode === 'repeat' ? 'check-on' : '' }}">{{ $dispenseMode === 'repeat' ? '✓' : '' }}</span>
                    <span class="check-label">Repeat</span>
                </td>
                <td style="width: 45%; text-align: right; font-size: 9px; color: #64748b;">
                    Ref {{ $patient->public_ref }}
                </td>
            </tr>
        </table>
        <div class="auth-quiet">
            Issued {{ $entry->issued_at->format('d M Y H:i') }}
            · Issue code <strong>{{ $entry->issue_code }}</strong>
            · Verify with the issuing practice. Professional aid only — not a government-certified form.
        </div>
    </div>
</body>
</html>

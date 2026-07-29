{{-- Middle-ground Rx pad: classic letterhead + serious pharmacist authenticity. --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $documentTitle }}</title>
    <style>
        @page { margin: 16mm 15mm 14mm 15mm; }
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
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 2px solid #0f172a;
        }
        .pad-logo {
            max-height: 44px;
            max-width: 140px;
            margin-bottom: 6px;
        }
        .pad-name {
            font-size: 17px;
            font-weight: bold;
            color: #0f172a;
            margin: 0 0 2px;
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
        .doc-banner {
            margin: 10px 0 14px;
            background: #0f172a;
            color: #fff;
            padding: 7px 12px;
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            text-align: center;
        }
        .patient-row {
            margin: 4px 0 6px;
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
        .dob-note {
            font-size: 9px;
            color: #64748b;
            margin-top: 4px;
            margin-bottom: 8px;
        }
        .rx-wrap {
            margin-top: 10px;
            min-height: 260px;
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
            font-size: 32px;
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
            margin-bottom: 14px;
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
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            font-size: 11px;
            color: #334155;
            white-space: pre-wrap;
            line-height: 1.45;
        }
        .notes-label {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: bold;
            color: #64748b;
            margin-bottom: 4px;
        }
        .sign-block {
            margin-top: 28px;
            width: 100%;
            border-collapse: collapse;
        }
        .sign-block td {
            vertical-align: bottom;
        }
        .sign-space {
            height: 48px;
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
            margin-top: 20px;
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
        .auth-box {
            margin-top: 12px;
            border: 2px solid #0f172a;
            padding: 10px 12px;
            background: #f8fafc;
            page-break-inside: avoid;
        }
        .auth-label {
            font-size: 9px;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 0.06em;
            color: #0f172a;
            margin-bottom: 8px;
        }
        .auth-caption {
            font-size: 8px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: bold;
        }
        .auth-code {
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 0.08em;
            color: #0f172a;
            font-family: DejaVu Sans Mono, monospace;
            margin-top: 2px;
        }
        .auth-date {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 2px;
        }
        .auth-time {
            font-size: 11px;
            color: #475569;
        }
        .pharm-guide {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px solid #cbd5e1;
            font-size: 9px;
            color: #334155;
            line-height: 1.45;
        }
        .pharm-guide strong {
            color: #0f172a;
        }
        .legal-foot {
            margin-top: 8px;
            font-size: 8px;
            color: #64748b;
            line-height: 1.4;
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

    <div class="doc-banner">Prescription · for the pharmacist · quote unique issue code when verifying</div>

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
        <div class="dob-note">DOB: {{ \Illuminate\Support\Carbon::parse($patientPayload['date_of_birth'])->format('d/m/Y') }} · Practice ref {{ $patient->public_ref }} · Issue code {{ $entry->issue_code }}</div>
    @else
        <div class="dob-note">Practice ref {{ $patient->public_ref }} · Issue code {{ $entry->issue_code }}</div>
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
                        <div class="notes">
                            <div class="notes-label">Notes for pharmacist / patient</div>
                            {{ $generalNotes }}
                        </div>
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
                        <img src="{{ $stampUri }}" alt="Clinical stamp" style="max-height: 90px; max-width: 230px;">
                    </div>
                @else
                    <div class="sign-space"></div>
                @endif
                <div class="sign-rule">Prescriber signature / stamp</div>
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
                <td style="width: 100%;">
                    <span class="check {{ $dispenseMode === 'single' ? 'check-on' : '' }}">{{ $dispenseMode === 'single' ? '✓' : '' }}</span>
                    <span class="check-label">Single</span>
                    <span class="check {{ $dispenseMode === 'repeat' ? 'check-on' : '' }}">{{ $dispenseMode === 'repeat' ? '✓' : '' }}</span>
                    <span class="check-label">Repeat</span>
                </td>
            </tr>
        </table>

        <div class="auth-box">
            <div class="auth-label">Pharmacist authenticity mark</div>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: 58%; vertical-align: top; padding-right: 12px;">
                        <div class="auth-caption">Unique issue code</div>
                        <div class="auth-code">{{ $entry->issue_code }}</div>
                    </td>
                    <td style="width: 42%; vertical-align: top;">
                        <div class="auth-caption">Issued on</div>
                        <div class="auth-date">{{ $entry->issued_at->format('d M Y') }}</div>
                        <div class="auth-time">{{ $entry->issued_at->format('H:i') }}</div>
                    </td>
                </tr>
            </table>
            <div class="pharm-guide">
                <strong>For the pharmacist:</strong>
                This code identifies a single issued original from the practice named above.
                Quote <strong>{{ $entry->issue_code }}</strong> when verifying with the issuing practice.
                Do not accept photocopies or reprints that reuse the same code without confirmation.
                Dispense as marked (Single / Repeat). Professional aid only — not a government-certified form.
            </div>
        </div>

        <div class="legal-foot">
            Generated by PractisBase after vault unlock. Verify the unique issue code with the issuing practice before dispensing if authenticity is in doubt.
        </div>
    </div>
</body>
</html>

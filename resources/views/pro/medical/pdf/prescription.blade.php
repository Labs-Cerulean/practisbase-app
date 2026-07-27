<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $documentTitle }}</title>
    <style>
        body { font-family: DejaVu Sans, Helvetica, Arial, sans-serif; color: #1e293b; font-size: 12px; margin: 0; padding: 28px 32px; }
        .practice { font-size: 16px; font-weight: bold; color: #0f172a; margin: 0; }
        .practice-sub { font-size: 10px; color: #64748b; margin-top: 2px; }
        h1 { color: #0f172a; font-size: 20px; margin: 18px 0 6px; border-bottom: 2px solid #0f172a; padding-bottom: 6px; }
        .meta { color: #64748b; margin-bottom: 14px; font-size: 11px; }
        .rx-banner { background: #0f172a; color: #fff; padding: 8px 12px; font-size: 11px; font-weight: bold; letter-spacing: 0.04em; text-transform: uppercase; margin-bottom: 14px; }
        .section { margin-top: 14px; }
        .label { font-size: 9px; text-transform: uppercase; color: #64748b; font-weight: bold; margin-bottom: 3px; letter-spacing: 0.03em; }
        .patient-name { font-size: 15px; font-weight: bold; color: #0f172a; }
        .rx-body { border: 1.5px solid #0f172a; padding: 14px 16px; margin-top: 10px; }
        .rx-item { border-bottom: 1px solid #e2e8f0; padding: 10px 0; }
        .rx-item:last-child { border-bottom: none; padding-bottom: 0; }
        .rx-item:first-child { padding-top: 0; }
        .rx-num { font-size: 10px; color: #64748b; font-weight: bold; }
        .rx-name { font-size: 13px; font-weight: bold; color: #0f172a; margin-top: 2px; }
        .rx-line { font-size: 11px; color: #334155; margin-top: 3px; }
        .notes { margin-top: 12px; padding-top: 10px; border-top: 1px dashed #cbd5e1; }
        .body-text { line-height: 1.55; white-space: pre-wrap; font-size: 12px; }
        .auth-box { margin-top: 22px; border: 2px solid #0f172a; padding: 12px 14px; background: #f8fafc; }
        .auth-label { font-size: 9px; text-transform: uppercase; font-weight: bold; letter-spacing: 0.06em; color: #0f172a; margin-bottom: 8px; }
        .auth-caption { font-size: 8px; text-transform: uppercase; color: #64748b; font-weight: bold; }
        .auth-code { font-size: 18px; font-weight: bold; letter-spacing: 0.08em; color: #0f172a; font-family: DejaVu Sans Mono, monospace; margin-top: 2px; }
        .auth-date { font-size: 16px; font-weight: bold; color: #0f172a; margin-top: 2px; }
        .auth-time { font-size: 11px; color: #475569; }
        .auth-note { margin-top: 10px; font-size: 9px; color: #475569; line-height: 1.4; }
        .sign { margin-top: 28px; }
        .sign-line { border-top: 1px solid #94a3b8; width: 220px; margin-top: 40px; padding-top: 6px; font-size: 10px; color: #64748b; }
        .footer { margin-top: 28px; font-size: 8px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 8px; }
    </style>
</head>
<body>
    @php
        $medicines = \App\Models\ClinicalEntry::medicinesFromPayload($entryPayload ?? []);
        $hasStructured = ! empty($entryPayload['medicines']) && is_array($entryPayload['medicines']);
        $generalNotes = $hasStructured ? trim((string) ($entryPayload['body'] ?? '')) : '';
    @endphp
    @include('pro.medical.pdf._letterhead')

    <div class="rx-banner">Prescription · for pharmacy / patient copy · {{ count($medicines) }} {{ count($medicines) === 1 ? 'medicine' : 'medicines' }}</div>

    <div class="meta">
        Document date {{ $entry->entry_date->format('d M Y') }}
        · Patient ref {{ $patient->public_ref }}
        · Issue code {{ $entry->issue_code }}
    </div>

    <div class="section">
        <div class="label">Patient</div>
        <div class="patient-name">{{ $patientPayload['display_name'] ?? 'Patient' }}</div>
        @if(!empty($patientPayload['date_of_birth']))
            <div style="margin-top: 4px;">Date of birth: {{ \Illuminate\Support\Carbon::parse($patientPayload['date_of_birth'])->format('d M Y') }}</div>
        @endif
    </div>

    <div class="rx-body">
        <div class="label">Medicines</div>
        @forelse($medicines as $i => $med)
            <div class="rx-item">
                <div class="rx-num">{{ $i + 1 }}.</div>
                <div class="rx-name">
                    {{ $med['name'] }}
                    @if($med['strength'] !== '')
                        <span style="font-weight: normal; color: #475569;"> · {{ $med['strength'] }}</span>
                    @endif
                </div>
                @if($med['dose'] !== '')
                    <div class="rx-line"><strong>Dose:</strong> {{ $med['dose'] }}</div>
                @endif
                @if($med['quantity'] !== '')
                    <div class="rx-line"><strong>Qty:</strong> {{ $med['quantity'] }}</div>
                @endif
                @if($med['instructions'] !== '')
                    <div class="rx-line"><strong>Directions:</strong> {{ $med['instructions'] }}</div>
                @endif
            </div>
        @empty
            <div class="body-text">{{ $entryPayload['body'] ?? '' }}</div>
        @endforelse

        @if($generalNotes !== '')
            <div class="notes">
                <div class="label">General notes</div>
                <div class="body-text">{{ $generalNotes }}</div>
            </div>
        @endif
    </div>

    @include('pro.medical.pdf._authenticity')

    <div class="sign">
        <div class="label">Prescriber</div>
        <div style="font-size: 13px; font-weight: bold; color: #0f172a;">{{ $user->name }}</div>
        @if($user->postnominalsLine())
            <div style="font-size: 11px; color: #64748b;">{{ $user->postnominalsLine() }}</div>
        @endif
        <div class="sign-line">Signature / stamp</div>
    </div>

    <div class="footer">
        Generated by PractisBase after vault unlock. Professional aid only — not a government-certified form and not a substitute for clinical duty of care.
        Quote the unique issue code when querying authenticity with the issuing practice.
    </div>
</body>
</html>

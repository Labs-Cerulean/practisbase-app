<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $certificate->title }}</title>
    <style>
        body { font-family: DejaVu Sans, Helvetica, Arial, sans-serif; color: #1e293b; font-size: 12px; margin: 0; padding: 28px 32px; }
        .practice { font-size: 16px; font-weight: bold; color: #0f172a; margin: 0; }
        .practice-sub { font-size: 10px; color: #64748b; margin-top: 2px; }
        h1 { color: #0f172a; font-size: 20px; margin: 18px 0 6px; border-bottom: 2px solid #0f172a; padding-bottom: 6px; }
        .meta { color: #64748b; margin-bottom: 14px; font-size: 11px; }
        .banner { background: #334155; color: #fff; padding: 8px 12px; font-size: 11px; font-weight: bold; letter-spacing: 0.04em; text-transform: uppercase; margin-bottom: 14px; }
        .label { font-size: 9px; text-transform: uppercase; color: #64748b; font-weight: bold; margin-bottom: 3px; letter-spacing: 0.03em; }
        .box { border: 1.5px solid #0f172a; padding: 18px; margin-top: 10px; }
        .title { font-size: 15px; font-weight: bold; color: #0f172a; margin-bottom: 12px; }
        .body-text { line-height: 1.55; white-space: pre-wrap; }
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
    @if($user->logoDataUri())
        <img src="{{ $user->logoDataUri() }}" style="max-height: 56px; max-width: 170px; margin-bottom: 12px;">
    @endif
    <div class="practice">{{ $user->name }}</div>
    @if($user->profession)
        <div class="practice-sub">{{ $user->profession }}</div>
    @endif
    @if($user->warrant_type || $user->warrant_number)
        <div class="practice-sub">Warrant: {{ trim(($user->warrant_type ?? '') . ' ' . ($user->warrant_number ?? '')) }}</div>
    @endif
    @if($user->vat_number)
        <div class="practice-sub">VAT: {{ $user->vat_number }}</div>
    @endif

    <h1>{{ $kinds[$certificate->kind] ?? 'Certificate' }}</h1>

    <div class="banner">Certificate / declaration · issued copy</div>

    <div class="meta">
        Document date {{ $certificate->issued_on->format('d M Y') }}
        @if($certificate->expires_on)
            · Expires {{ $certificate->expires_on->format('d M Y') }}
        @endif
        · Issue code {{ $certificate->issue_code }}
    </div>

    <div class="box">
        <div class="title">{{ $certificate->title }}</div>
        @if($certificate->subject_name)
            <div class="label">Subject</div>
            <div style="font-size: 14px; font-weight: bold; margin-bottom: 12px;">{{ $certificate->subject_name }}</div>
        @endif
        @if($certificate->notes)
            <div class="label">Details</div>
            <div class="body-text">{{ $certificate->notes }}</div>
        @endif
    </div>

    <div class="auth-box">
        <div class="auth-label">Authenticity mark</div>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 55%; vertical-align: top; padding-right: 12px;">
                    <div class="auth-caption">Unique issue code</div>
                    <div class="auth-code">{{ $certificate->issue_code }}</div>
                </td>
                <td style="width: 45%; vertical-align: top;">
                    <div class="auth-caption">Issued on</div>
                    <div class="auth-date">{{ $certificate->stamped_at->format('d M Y') }}</div>
                    <div class="auth-time">{{ $certificate->stamped_at->format('H:i') }}</div>
                </td>
            </tr>
        </table>
        <div class="auth-note">
            This code identifies a single issued original. Photocopies, reprints, or reuse of the same code outside the issuing practice should be verified against the practitioner register.
        </div>
    </div>

    <div class="sign">
        <div class="label">Issued by</div>
        <div style="font-size: 13px; font-weight: bold; color: #0f172a;">{{ $user->name }}</div>
        <div class="sign-line">Signature / stamp</div>
    </div>

    <div class="footer">
        Generated by PractisBase. Professional aid only — not a government-certified form.
        Quote the unique issue code when verifying authenticity with the issuing practice.
    </div>
</body>
</html>

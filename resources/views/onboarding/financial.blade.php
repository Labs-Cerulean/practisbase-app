<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fiscal Setup | PractisBase</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
    <style>
        * { box-sizing: border-box; }
        body {
            background: var(--bg-canvas);
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            padding: 1rem;
            margin: 0;
        }
        .card {
            background: white;
            padding: 1.35rem 1.25rem 1.75rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            width: 100%;
            max-width: 720px;
            border: 1px solid var(--border-light);
        }
        @media (min-width: 640px) {
            body { padding: 1.75rem; align-items: center; }
            .card { padding: 2rem 2.25rem 2.25rem; }
        }
        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 0.4rem; color: var(--primary-navy); font-size: 0.92rem; }
        .form-select, .form-input {
            width: 100%;
            padding: 0.7rem 0.8rem;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            font-family: inherit;
            font-size: 1rem;
            background: white;
        }
        .warning-box {
            background: #fffbeb;
            border: 1px solid #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 0.75rem 0.85rem;
            border-radius: var(--radius-md);
            color: #92400e;
            font-size: 0.8rem;
            margin-top: 0.5rem;
            line-height: 1.4;
            display: none;
        }
        .hint-box {
            background: #fffbeb;
            border-left: 3px solid #f59e0b;
            padding: 0.55rem 0.75rem;
            border-radius: var(--radius-md);
            color: #92400e;
            font-size: 0.78rem;
            line-height: 1.4;
            margin-top: 0.55rem;
        }
        .btn-submit {
            width: 100%;
            padding: 0.9rem;
            background: var(--primary-cerulean);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            margin-top: 0.5rem;
        }
        .btn-submit:hover { background: var(--primary-cerulean-hover); }
        .resume-note { font-size: 0.78rem; color: var(--text-muted); line-height: 1.4; margin: 0 0 1.25rem; }
    </style>
    @include('partials.pwa-head')
</head>
<body>
@php
    $isMedical = $user->profession === 'Medical Professional';
    $defaultVat = old('vat_status', $isMedical ? 'exempt' : '');
@endphp

    <div class="card">
        <h2 style="color: var(--primary-navy); margin: 0 0 0.4rem; font-size: 1.35rem;">Fiscal &amp; Compliance Setup</h2>
        <p style="color: var(--text-muted); margin: 0 0 0.5rem; line-height: 1.45; font-size: 0.92rem;">We need your sole trader tax setup in Malta for ledger and TA22 calculations.</p>
        <p class="resume-note">Your account is already saved. If you leave, sign in again to continue from here.</p>

        @if($user->beta_invite_code_id)
            <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: var(--radius-md); padding: 0.85rem 1rem; margin-bottom: 1.25rem; color: #065f46; font-size: 0.88rem; line-height: 1.45;">
                Access code active: <strong>{{ $user->profession }}</strong> · Full Pro unlocked.
            </div>
        @endif

        @if ($errors->any())
            <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.25rem; font-size: 0.85rem;">
                @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif

        <form action="/onboarding/financial" method="POST">
            @csrf

            @if($user->beta_invite_code_id)
                <div class="form-group">
                    <label>Warrant type (optional)</label>
                    <input type="text" name="warrant_type" class="form-input" value="{{ old('warrant_type', $user->warrant_type) }}" placeholder="Main body or international body">
                </div>
                <div class="form-group">
                    <label>Warrant number (optional)</label>
                    <input type="text" name="warrant_number" class="form-input" value="{{ old('warrant_number', $user->warrant_number) }}">
                </div>
                <hr style="border: none; border-top: 1px solid var(--border-light); margin: 0.5rem 0 1.25rem;">
            @endif

            <div class="form-group">
                <label>1. How do you work?</label>
                <select name="employment_type" id="employmentType" class="form-select" onchange="handleEmploymentChange()" required>
                    <option value="">Select an option...</option>
                    <option value="full_time" @selected(old('employment_type') === 'full_time')>This is my main work (full-time self-employed)</option>
                    <option value="part_time" @selected(old('employment_type') === 'part_time')>I also have a main job (part-time self-employed)</option>
                </select>

                <div id="partTimeWarning" class="warning-box">
                    <strong>SSC note:</strong> We assume your main employer deducts National Insurance. If not, calculate Class 2 yourself for each role.
                </div>
            </div>

            <div class="form-group" id="dobGroup" style="display: none;">
                <label>Date of birth (for SSC caps)</label>
                <input type="date" name="date_of_birth" id="dobInput" class="form-input" max="{{ now()->subYears(18)->format('Y-m-d') }}" value="{{ old('date_of_birth') }}">
            </div>

            <hr style="border: none; border-top: 1px solid var(--border-light); margin: 1.5rem 0;">

            <div class="form-group">
                <label>2. Do you charge VAT?</label>
                <select name="vat_status" id="vatStatus" class="form-select" onchange="handleVatChange()" required>
                    <option value="">Select how you handle VAT...</option>
                    <option value="article_11" @selected($defaultVat === 'article_11')>No VAT yet — under €35k / year (Article 11)</option>
                    <option value="article_10" @selected($defaultVat === 'article_10')>I charge 18% VAT (Article 10)</option>
                    <option value="exempt" @selected($defaultVat === 'exempt')>Exempt work (e.g. therapeutic medical)</option>
                </select>

                @if($isMedical)
                    <div class="hint-box">
                        Medical default is Fifth Schedule exempt for therapeutic care. Switch to Article 10 or 11 if you also do taxable work (cosmetic, consultancy, medico-legal).
                    </div>
                @endif
            </div>

            <div class="form-group" id="vatNumberGroup" style="display: none;">
                <label>VAT number <span style="font-weight: 500; color: var(--text-muted);">(optional)</span></label>
                <input type="text" name="vat_number" id="vatNumberInput" class="form-input" placeholder="MT..." value="{{ old('vat_number') }}">
                <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.3rem; line-height: 1.4;">
                    Optional for now. Required later when you issue an Article 10 invoice.
                </div>
            </div>

            <button type="submit" class="btn-submit">Continue to plans</button>
        </form>

        <p style="margin: 1.35rem 0 0; font-size: 0.78rem; color: var(--text-muted); line-height: 1.45; text-align: center;">
            PractisBase is for Maltese sole traders, not Ltd companies.
        </p>
    </div>

    <script>
        function handleEmploymentChange() {
            const empType = document.getElementById('employmentType').value;
            const dobGroup = document.getElementById('dobGroup');
            const dobInput = document.getElementById('dobInput');
            const warning = document.getElementById('partTimeWarning');

            if (empType === 'full_time') {
                dobGroup.style.display = 'block';
                dobInput.required = true;
                warning.style.display = 'none';
            } else if (empType === 'part_time') {
                dobGroup.style.display = 'none';
                dobInput.required = false;
                dobInput.value = '';
                warning.style.display = 'block';
            } else {
                dobGroup.style.display = 'none';
                dobInput.required = false;
                warning.style.display = 'none';
            }
        }

        function handleVatChange() {
            const vatStatus = document.getElementById('vatStatus').value;
            const vatGroup = document.getElementById('vatNumberGroup');
            const vatInput = document.getElementById('vatNumberInput');

            if (vatStatus === 'article_10' || vatStatus === 'article_11') {
                vatGroup.style.display = 'block';
            } else {
                vatGroup.style.display = 'none';
                vatInput.value = '';
            }
        }

        handleEmploymentChange();
        handleVatChange();
    </script>
</body>
</html>

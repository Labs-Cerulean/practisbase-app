<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fiscal Setup | PractisBase</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
    <style>
        body { background: var(--bg-canvas); display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 2rem; }
        .card { background: white; padding: 2.5rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); width: 100%; max-width: 600px; border: 1px solid var(--border-light); }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--primary-navy); }
        .form-select, .form-input { width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-family: inherit; font-size: 1rem; }
        .warning-box { background: #fffbeb; border: 1px solid #fef3c7; border-left: 4px solid #f59e0b; padding: 1rem; border-radius: var(--radius-md); color: #92400e; font-size: 0.9rem; margin-top: 0.5rem; line-height: 1.4; display: none; }
        .btn-submit { width: 100%; padding: 1rem; background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; font-size: 1.05rem; cursor: pointer; margin-top: 1rem; transition: 0.2s; }
        .btn-submit:hover { background: var(--primary-cerulean-hover); }
    </style>
</head>
<body>

    <div class="card">
        <h2 style="color: var(--primary-navy); margin-top: 0; margin-bottom: 0.5rem;">Fiscal & Compliance Setup</h2>
        <p style="color: var(--text-muted); margin-bottom: 1rem; line-height: 1.4;">To automate your Ledger and TA22 calculations correctly, we need your <strong>sole-trader</strong> tax structure in Malta.</p>
        <div style="background: #eff6ff; border-left: 4px solid #2563eb; padding: 0.85rem 1rem; border-radius: var(--radius-md); color: #1e3a8a; font-size: 0.85rem; line-height: 1.45; margin-bottom: 1.75rem;">
            PractisBase models <strong>self-employed sole traders</strong> (full-time or part-time). It does <strong>not</strong> produce limited company (Ltd) accounts, corporate tax, or company VAT-group filings.
        </div>

        @if($user->beta_invite_code_id)
            <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: var(--radius-md); padding: 0.85rem 1rem; margin-bottom: 1.5rem; color: #065f46; font-size: 0.88rem; line-height: 1.45;">
                Beta invite active: <strong>{{ $user->profession }}</strong> · Full Pro unlocked. Profession is locked to your invite.
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
                    <input type="text" name="warrant_type" class="form-input" value="{{ old('warrant_type', $user->warrant_type) }}" placeholder="e.g. Warrant of Perit / Inġinier">
                </div>
                <div class="form-group">
                    <label>Warrant number (optional)</label>
                    <input type="text" name="warrant_number" class="form-input" value="{{ old('warrant_number', $user->warrant_number) }}">
                </div>
                <hr style="border: none; border-top: 1px solid var(--border-light); margin: 1.5rem 0;">
            @endif

            <div class="form-group">
                <label>1. How do you work?</label>
                <select name="employment_type" id="employmentType" class="form-select" onchange="handleEmploymentChange()" required>
                    <option value="">Select an option...</option>
                    <option value="full_time">This practice is my main work (full-time self-employed)</option>
                    <option value="part_time">I also have a main job (part-time self-employed)</option>
                </select>

                <div id="partTimeWarning" class="warning-box">
                    <strong>Note on Social Security (SSC):</strong> PractisBase assumes your primary employer is deducting your National Insurance. If they are not, you need to manually calculate the required contribution from each employment.
                </div>
            </div>

            <div class="form-group" id="dobGroup" style="display: none;">
                <label>Date of Birth (Required for SSC Caps)</label>
                <input type="date" name="date_of_birth" id="dobInput" class="form-input" max="{{ now()->subYears(18)->format('Y-m-d') }}">
            </div>

            <hr style="border: none; border-top: 1px solid var(--border-light); margin: 2rem 0;">

            <div class="form-group">
                <label>2. Do you charge VAT?</label>
                
                <select name="vat_status" id="vatStatus" class="form-select" onchange="handleVatChange()" required>
                    <option value="">Select how you handle VAT...</option>
                    <option value="article_11">No VAT yet — billed under €35k / year (Article 11)</option>
                    <option value="article_10">I charge 18% VAT (Article 10)</option>
                    <option value="exempt">Exempt work (e.g. therapeutic medical — Fifth Schedule)</option>
                </select>

                @if($user->profession === 'Medical Professional')
                    <div style="margin-top: 1rem; padding: 1rem; background: #fffbeb; border: 1px solid #fef3c7; border-left: 4px solid #f59e0b; border-radius: var(--radius-md); color: #92400e; font-size: 0.85rem; line-height: 1.5;">
                        <strong>⚠️ Note on Medical Exemptions (Fifth Schedule):</strong><br>
                        Under Maltese VAT Law, the medical exemption applies <strong>strictly to therapeutic care</strong> provided by professionals warranted under the Health Care Professions Act. Non-therapeutic services (e.g., purely cosmetic procedures, corporate consultancy, medico-legal reports) may be subject to standard 18% VAT. If you provide taxable services, you must register under Article 10 or 11.
                    </div>
                @endif
            </div>

            <div class="form-group" id="vatNumberGroup" style="display: none;">
                <label>VAT Number <span style="font-weight: 500; color: var(--text-muted);">(optional)</span></label>
                <input type="text" name="vat_number" id="vatNumberInput" class="form-input" placeholder="MT...">
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.3rem; line-height: 1.4;">
                    Skip if you do not have an MT number yet. Add it later in Settings — we only require it when you issue an Article 10 invoice or charge 18% VAT.
                </div>
            </div>

            <button type="submit" class="btn-submit">Continue to Plans &rarr;</button>
        </form>
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
            const vatStatus = document.getElementById('vatStatus');
            const vatGroup = document.getElementById('vatNumberGroup');
            const vatInput = document.getElementById('vatNumberInput');

            if (vatStatus.value === 'article_10' || vatStatus.value === 'article_11') {
                vatGroup.style.display = 'block';
                vatInput.required = false;
            } else {
                vatGroup.style.display = 'none';
                vatInput.required = false;
                vatInput.value = '';
            }
        }
    </script>
</body>
</html>
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
        <p style="color: var(--text-muted); margin-bottom: 2rem; line-height: 1.4;">To automate your Ledger and TA22 calculations correctly, we need to understand your current tax structure in Malta.</p>

        <form action="/onboarding/financial" method="POST">
            @csrf

            <div class="form-group">
                <label>1. Is this your only source of employment?</label>
                <select name="employment_type" id="employmentType" class="form-select" onchange="handleEmploymentChange()" required>
                    <option value="">Select an option...</option>
                    <option value="full_time">Yes - I am Full-Time Self-Employed</option>
                    <option value="part_time">No - I am an employee elsewhere (Part-Time Self-Employed)</option>
                </select>

                <div id="partTimeWarning" class="warning-box">
                    <strong>Note on Social Security (SSC):</strong> PractisBase assumes your primary employer is deducting your National Insurance. If they are not, you need to manually calculate the required contribution from each employment.
                </div>
            </div>

            <div class="form-group" id="dobGroup" style="display: none;">
                <label>Date of Birth (Required for SSC Caps)</label>
                <input type="date" name="date_of_birth" id="dobInput" class="form-input">
            </div>

            <hr style="border: none; border-top: 1px solid var(--border-light); margin: 2rem 0;">

            <div class="form-group">
                <label>2. What is your VAT Registration Status?</label>
                
                @if($user->profession === 'Medical Professional')
                    <div style="background: #e0f2fe; border: 1px solid #bae6fd; padding: 1rem; border-radius: var(--radius-md); color: #0369a1; font-size: 0.95rem; line-height: 1.4;">
                        <strong>Medical Exemption applied.</strong> As a medical professional, your services fall under the Fifth Schedule (Exempt Without Credit). You do not charge VAT.
                    </div>
                    <input type="hidden" name="vat_status" value="exempt">
                @else
                    <select name="vat_status" id="vatStatus" class="form-select" onchange="handleVatChange()" required>
                        <option value="">Select your VAT Article...</option>
                        <option value="article_11">Article 11 (Exempt - Annual revenue UNDER €35,000)</option>
                        <option value="article_10">Article 10 (Standard - Annual revenue OVER €35,000)</option>
                        <option value="exempt">VAT Exempt (Fifth Schedule - E.g. Education, Insurance)</option>
                    </select>
                @endif
            </div>

            <div class="form-group" id="vatNumberGroup" style="display: none;">
                <label>VAT Number</label>
                <input type="text" name="vat_number" id="vatNumberInput" class="form-input" placeholder="MT...">
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.3rem;">Required for Article 10 and 11 registrations.</div>
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
            // If Medical, the select doesn't exist, so we abort to prevent JS errors
            if (!vatStatus) return; 

            const vatGroup = document.getElementById('vatNumberGroup');
            const vatInput = document.getElementById('vatNumberInput');

            if (vatStatus.value === 'article_10' || vatStatus.value === 'article_11') {
                vatGroup.style.display = 'block';
                vatInput.required = true;
            } else {
                vatGroup.style.display = 'none';
                vatInput.required = false;
                vatInput.value = '';
            }
        }
    </script>
</body>
</html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register | PractisBase</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
    <style>
        body { background-color: var(--bg-canvas); display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 2rem; }
        .auth-card { background: var(--bg-surface); padding: 2.5rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); width: 100%; max-width: 650px; }
        .auth-header { text-align: center; margin-bottom: 2rem; }
        
        .auth-header img { width: 100%; max-width: 200px; height: auto; margin-bottom: 1rem; object-fit: contain; }
        
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--primary-navy); font-size: 0.9rem; }
        .form-group input { width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-family: inherit; font-size: 0.95rem; }

        /* Error Alert Box */
        .alert-error {
            background-color: #fef2f2;
            border: 1px solid #f87171;
            color: #b91c1c;
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
        }
        .alert-error ul {
            margin: 0;
            padding-left: 1.5rem;
        }
        .alert-error li {
            margin-bottom: 0.25rem;
        }
        
        /* The Enhanced Legal Scroll Box */
        .legal-box { height: 250px; overflow-y: scroll; border: 1px solid var(--border-light); background: #f8fafc; padding: 1.25rem; border-radius: var(--radius-md); font-size: 0.8rem; color: #475569; margin-bottom: 1.5rem; line-height: 1.6; }
        .legal-box h4 { color: var(--primary-navy); margin-top: 1.5rem; margin-bottom: 0.5rem; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .legal-box h4:first-child { margin-top: 0; }
        .legal-box p { margin-bottom: 0.75rem; }
        .legal-box strong { color: var(--primary-navy); }
        
        /* New Scroll Instruction Banner */
        .scroll-instruction { background-color: rgba(2, 132, 199, 0.08); color: var(--primary-cerulean); padding: 0.75rem 1rem; border-radius: var(--radius-md); font-size: 0.85rem; font-weight: 600; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem; border: 1px solid rgba(2, 132, 199, 0.2); }
        
        .checkbox-group { display: flex; align-items: flex-start; gap: 0.75rem; margin-bottom: 2rem; background: rgba(2, 132, 199, 0.05); padding: 1rem; border-radius: var(--radius-md); border: 1px solid rgba(2, 132, 199, 0.2); }
        .checkbox-group input { margin-top: 0.25rem; cursor: pointer; transform: scale(1.1); }
        
        .btn-submit { width: 100%; padding: 0.85rem; background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); font-weight: 600; font-size: 1rem; cursor: pointer; transition: all 0.2s; }
        .btn-submit:disabled { background: var(--border-light); color: var(--text-muted); cursor: not-allowed; }
        .btn-submit:not(:disabled):hover { background: var(--primary-cerulean-hover); }
    </style>
</head>
<body>

    <div class="auth-card">
        <div class="auth-header">
            <img src="/images/logo.png" alt="PractisBase">
            <h2 style="color: var(--primary-navy); margin-bottom: 0.25rem;">Create Your Account</h2>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">For Maltese <strong>self-employed sole traders</strong> and professional practices — not limited companies.</p>
            <p style="color: var(--text-muted); font-size: 0.8rem; margin: 0.5rem 0 0; line-height: 1.4;">If you trade through a Ltd, partnership company, or VAT group, use a company accounting tool instead. PractisBase will not calculate corporate tax or company accounts.</p>
        </div>

        @if ($errors->any())
            <div class="alert-error">
                <strong>Whoops! Please fix the following:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form action="/register-submit" method="POST" id="registerForm">
            @csrf
            <div class="form-group">
                <label>Full Name / Practice Name</label>
                <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g., Perit John Borg">
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" value="{{ old('email') }}" required placeholder="john@example.com">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>

            <label style="display: block; font-weight: 700; margin-bottom: 0.5rem; color: var(--primary-navy); font-size: 0.9rem;">Master Service Agreement &amp; Privacy Policy</label>
            <p style="margin: 0 0 0.75rem; font-size: 0.8rem; color: var(--text-muted); line-height: 1.45;">
                Full documents:
                <a href="/msa" target="_blank" rel="noopener" style="color: var(--primary-cerulean); font-weight: 600;">MSA</a>
                ·
                <a href="/privacy" target="_blank" rel="noopener" style="color: var(--primary-cerulean); font-weight: 600;">Privacy Policy</a>
            </p>
            <div class="scroll-instruction">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M19 12l-7 7-7-7"/></svg>
                Please scroll to the bottom of the agreement to enable the accept button.
            </div>

            <div class="legal-box" id="legalScrollBox">
                <h4>1. Acceptance of Terms</h4>
                <p>By registering for, accessing, or using PractisBase (&ldquo;The Service&rdquo;), you agree to be bound by this Master Service Agreement and the Privacy Policy. You register as a natural person operating a professional practice (including under a trading name). PractisBase records the IP address and timestamp of your acceptance at registration. Full text: <a href="/msa" target="_blank" rel="noopener">/msa</a> and <a href="/privacy" target="_blank" rel="noopener">/privacy</a>.</p>

                <h4>2. Intended Users &amp; Entity Scope</h4>
                <p><strong>PractisBase is built exclusively for Maltese self-employed sole traders and self-employed professionals</strong> (full-time or part-time), including medical, architectural, engineering, and similar practices that bill in their own name.</p>
                <p><strong>The Service is strictly not designed for limited liability companies (Ltds), public companies, corporate partnerships as separate legal persons, VAT groups, or employers running payroll/FSS for staff.</strong> Using PractisBase for company books, corporate income tax, or company VAT group reporting is expressly outside the intended scope; any figures generated in that context are fundamentally invalid.</p>

                <h4>3. No Professional Advice</h4>
                <p>The Service provides administrative, templating, and data management tools only. <strong>PractisBase does not provide medical, architectural, engineering, financial, or legal advice.</strong> The User assumes full, exclusive liability for all clinical diagnoses, structural calculations, certifications, prescriptions, and professional actions facilitated through The Service.</p>

                <h4>4. Absolute Tax &amp; Accounting Disclaimers</h4>
                <p>While The Service provides ledger management, VAT aids, and tax form automation for common sole-trader situations in Malta, <strong>PractisBase is a software tool, not an accounting firm or a licensed tax advisor.</strong> You assume sole and absolute responsibility for all financial data, tax calculations, omissions, and submissions to the Malta Tax and Customs Administration (MTCA). Consult a CPA or recognised tax advisor before filing.</p>

                <h4>5. Medical Vault &amp; Profession Modules</h4>
                <p><strong>Medical Vault:</strong> Client-side cryptography. PractisBase does not possess your decryption keys. If you lose your password and recovery codes, clinical data is irreversibly lost. <strong>Studio &amp; Technical desks:</strong> templates and stampables are aids only; you remain responsible for regulatory and warrant compliance (e.g. KTP, Chamber of Engineers).</p>

                <h4>6. Data Processing &amp; GDPR</h4>
                <p>You are the exclusive Data Controller for client and patient information uploaded. PractisBase acts solely as Data Processor. You warrant you have obtained all necessary legal consents. See the <a href="/privacy" target="_blank" rel="noopener">Privacy Policy</a>.</p>

                <h4>7. Limitation of Liability</h4>
                <p>To the maximum extent permitted by law, Cerulean Labs Limited shall not be liable for indirect, incidental, special, consequential, or punitive damages, including loss of profits, data, goodwill, malpractice claims, tax penalties, or regulatory fines. Aggregate liability shall not exceed amounts paid to PractisBase in the twelve (12) months preceding the claim. <strong>Free Tier or Beta Trial liability is strictly limited to €0.00.</strong></p>

                <h4>8. Indemnification</h4>
                <p>You agree to defend and indemnify Cerulean Labs Limited against claims arising from your use of The Service, your breach of these Terms, third-party rights violations, or your professional / tax submissions.</p>

                <h4>9. Service Availability &amp; Data Backups</h4>
                <p>The Service is provided &ldquo;AS IS&rdquo; and &ldquo;AS AVAILABLE&rdquo;. You must maintain independent backups. The standard export tool covers text and ledger data only — not physical file uploads stored in the cloud.</p>

                <h4>10. Governing Law</h4>
                <p>These Terms are governed by the laws of the Republic of Malta. Disputes are subject to the exclusive jurisdiction of the Courts of Malta.</p>
            </div>

            <div class="checkbox-group" style="margin-bottom: 1rem;">
                <input type="checkbox" id="confirmSoleTrader" name="confirm_sole_trader" value="1" {{ old('confirm_sole_trader') ? 'checked' : '' }} required>
                <label for="confirmSoleTrader" style="font-size: 0.9rem; color: var(--text-main); font-weight: 500; line-height: 1.4;">
                    I confirm I am registering as a <strong>self-employed sole trader</strong> (or professional practice in my own name), <strong>not</strong> as a limited company (Ltd) or other corporate entity.
                </label>
            </div>

            <div class="checkbox-group">
                <input type="checkbox" id="acceptTerms" name="accept_terms" disabled>
                <label for="acceptTerms" style="font-size: 0.9rem; color: var(--text-main); font-weight: 500; line-height: 1.4;">
                    I have read and agree to the Master Service Agreement and Privacy Policy, including the sole-trader scope and tax disclaimers.
                </label>
            </div>

            <input type="hidden" name="read_duration_seconds" id="readDurationInput" value="0">

            <button type="submit" class="btn-submit" id="submitBtn" disabled>Create Professional Account</button>
        </form>
    </div>

    <script>
        const pageLoadTime = Date.now();
        const legalBox = document.getElementById('legalScrollBox');
        const checkbox = document.getElementById('acceptTerms');
        const submitBtn = document.getElementById('submitBtn');
        const readDurationInput = document.getElementById('readDurationInput');

        legalBox.addEventListener('scroll', function() {
            if (legalBox.scrollTop + legalBox.clientHeight >= legalBox.scrollHeight - 10) {
                checkbox.disabled = false;
            }
        });

        checkbox.addEventListener('change', function() {
            submitBtn.disabled = !this.checked;
            if(this.checked) {
                const timeAccepted = Date.now();
                const secondsSpent = Math.floor((timeAccepted - pageLoadTime) / 1000);
                readDurationInput.value = secondsSpent;
            }
        });
    </script>
</body>
</html>
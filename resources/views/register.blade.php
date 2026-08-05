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
                <p>By registering for, accessing, or using PractisBase ("The Service"), you agree to be bound by these Terms of Service. You register as a natural person operating a professional practice (including under a trading name). You confirm that you are authorised to accept these Terms for that practice.</p>

                <h4>2. Intended Users &amp; Entity Scope</h4>
                <p><strong>PractisBase is built for Maltese self-employed sole traders and self-employed professionals</strong> (full-time or part-time), including medical, architectural, engineering, and similar practices that bill in their own name.</p>
                <p><strong>The Service is not designed for limited liability companies (Ltd), public companies, corporate partnerships as separate legal persons, VAT groups, or employers running payroll/FSS for staff.</strong> Those entities should use dedicated company accounting software and professional advisors. Using PractisBase for company books, corporate income tax, or company VAT group reporting is outside the intended scope; any figures generated in that context are not a substitute for company accounts.</p>
                <p>A “practice name” on your profile is a trading name for your sole-trader activity. It does not convert The Service into a company accounting system.</p>

                <h4>3. No Professional Advice</h4>
                <p>The Service provides administrative, templating, and data management tools only. <strong>PractisBase does not provide medical, architectural, engineering, financial, or legal advice.</strong> The User assumes full, exclusive liability for all clinical diagnoses, structural calculations, certifications, prescriptions, and professional actions facilitated through The Service. The Service is not a substitute for professional judgment.</p>

                <h4>4. Tax &amp; Accounting Disclaimers</h4>
                <p>While The Service provides ledger management, VAT aids, and tax form automation for <strong>self-employed / sole-trader</strong> situations in Malta (such as progressive income tax, TA22 where applicable, Class 2 SSC estimates, and Article 10/11 VAT monitoring), <strong>PractisBase is not an accounting firm or a licensed tax advisor.</strong> The User assumes sole responsibility for the accuracy of all financial data, tax calculations, omissions, and submissions. The Service does not replace the need for professional financial counsel. You are strictly advised to consult a certified public accountant (CPA) or recognised tax advisor prior to submitting any tax or VAT returns to the Commissioner for Revenue.</p>
                <p>Tax tools reflect common sole-trader permutations (including part-time self-employment with primary employment). Unusual mid-year regime changes, reduced VAT rates, EU/cross-border special schemes, and situations outside Maltese sole-trader rules may require manual adjustment and advisor review.</p>

                <h4>5. Data Processing &amp; GDPR Compliance</h4>
                <p>In accordance with the EU General Data Protection Regulation (GDPR) and the Data Protection Act (Cap. 586 of the Laws of Malta), the User acts as the exclusive "Data Controller" for all client and patient information uploaded. PractisBase acts solely as the "Data Processor." We claim no ownership over your client data. You warrant that you have obtained all necessary legal consents from your clients/patients to store their sensitive data digitally within The Service.</p>

                <h4>6. Limitation of Liability</h4>
                <p>To the maximum extent permitted by applicable law, PractisBase, its founders, and affiliates shall not be liable for any indirect, incidental, special, consequential, or punitive damages, including without limitation: loss of profits, loss of data, loss of goodwill, malpractice claims, tax penalties, or regulatory fines. <strong>In no event shall our aggregate liability exceed the total amounts paid by you to PractisBase in the twelve (12) months immediately preceding the event giving rise to the claim.</strong> If you are utilizing the "Free Tier", our total liability is limited to zero Euros (€0.00).</p>

                <h4>7. Indemnification</h4>
                <p>You agree to defend, indemnify, and hold harmless PractisBase and its employees from and against any claims, damages, obligations, losses, liabilities, costs, or debt (including but not limited to attorney's fees) arising from: (a) your use of and access to The Service; (b) your violation of any term of this Agreement; (c) your violation of any third-party right, including without limitation any privacy or intellectual property right; or (d) any claim that your content, professional actions, or financial submissions caused damage to a third party or resulted in regulatory action.</p>

                <h4>8. Service Availability &amp; Data Backups</h4>
                <p>The Service is provided on an "AS IS" and "AS AVAILABLE" basis. While we utilize enterprise-grade infrastructure, we do not guarantee absolute immunity from data loss. <strong>Users are provided with a "Data Export" tool and are strictly required to maintain their own independent backups of their client and financial records.</strong> Please note: The standard export tool downloads text and ledger data only; it does not include physical file uploads (e.g., PDFs, receipt images, or architectural documents) stored in the cloud.</p>

                <h4>9. Governing Law &amp; Jurisdiction</h4>
                <p>These Terms shall be governed and construed in accordance with the laws of the Republic of Malta, without regard to its conflict of law provisions. Any dispute arising out of or in connection with these Terms shall be subject to the exclusive jurisdiction of the Courts of Malta.</p>
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
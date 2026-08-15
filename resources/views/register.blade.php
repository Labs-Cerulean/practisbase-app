<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register | PractisBase</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
    <style>
        * { box-sizing: border-box; }
        body {
            background-color: var(--bg-canvas);
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            padding: 1rem;
            margin: 0;
        }
        .auth-card {
            background: var(--bg-surface);
            padding: 1.35rem 1.25rem 1.75rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            width: 100%;
            max-width: 720px;
        }
        @media (min-width: 640px) {
            body { padding: 1.75rem; align-items: center; }
            .auth-card { padding: 2rem 2.25rem 2.25rem; }
        }

        .auth-header { text-align: center; margin-bottom: 1.5rem; }
        .auth-header img {
            width: auto;
            max-width: 160px;
            height: auto;
            margin: 0 auto 0.75rem;
            display: block;
            object-fit: contain;
        }
        .auth-header h2 {
            color: var(--primary-navy);
            margin: 0 0 0.35rem;
            font-size: 1.35rem;
        }
        .auth-header p {
            color: var(--text-muted);
            font-size: 0.88rem;
            margin: 0;
            line-height: 1.45;
        }

        .form-group { margin-bottom: 1.1rem; }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.4rem;
            color: var(--primary-navy);
            font-size: 0.88rem;
        }
        .form-group input {
            width: 100%;
            padding: 0.7rem 0.8rem;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            font-family: inherit;
            font-size: 1rem;
        }
        .field-hint {
            margin: 0.35rem 0 0;
            font-size: 0.78rem;
            color: var(--text-muted);
            line-height: 1.35;
        }

        .alert-error {
            background-color: #fef2f2;
            border: 1px solid #f87171;
            color: #b91c1c;
            padding: 0.85rem 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.25rem;
            font-size: 0.85rem;
        }
        .alert-error ul { margin: 0; padding-left: 1.25rem; }
        .alert-error li { margin-bottom: 0.2rem; }

        .legal-box {
            height: 280px;
            overflow-y: scroll;
            border: 1px solid var(--border-light);
            background: #f8fafc;
            padding: 1rem;
            border-radius: var(--radius-md);
            font-size: 0.8rem;
            color: #475569;
            margin-bottom: 1.25rem;
            line-height: 1.55;
        }
        .legal-box h2 {
            color: var(--primary-navy);
            margin-top: 1.25rem;
            margin-bottom: 0.45rem;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .legal-box h2:first-child { margin-top: 0; }
        .legal-box p { margin-bottom: 0.65rem; }
        .legal-box strong { color: var(--primary-navy); }

        .scroll-instruction {
            background-color: rgba(2, 132, 199, 0.08);
            color: var(--primary-cerulean);
            padding: 0.65rem 0.85rem;
            border-radius: var(--radius-md);
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid rgba(2, 132, 199, 0.2);
        }

        .checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 0.65rem;
            margin-bottom: 0.75rem;
            background: rgba(2, 132, 199, 0.05);
            padding: 0.85rem;
            border-radius: var(--radius-md);
            border: 1px solid rgba(2, 132, 199, 0.2);
        }
        .checkbox-group:last-of-type { margin-bottom: 1.5rem; }
        .checkbox-group input { margin-top: 0.2rem; cursor: pointer; transform: scale(1.1); flex-shrink: 0; }
        .checkbox-group label {
            font-size: 0.85rem;
            color: var(--text-main);
            font-weight: 500;
            line-height: 1.4;
        }

        .btn-submit {
            width: 100%;
            padding: 0.85rem;
            background: var(--primary-cerulean);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-submit:disabled { background: var(--border-light); color: var(--text-muted); cursor: not-allowed; }
        .btn-submit:not(:disabled):hover { background: var(--primary-cerulean-hover); }
    </style>
</head>
<body>

    <div class="auth-card">
        <div class="auth-header">
            <img src="/images/logo.png" alt="PractisBase">
            <h2>Create Your Account</h2>
            <p>For Maltese <strong>self employed sole traders</strong> and professional practices, not limited companies.</p>
            <p style="margin-top: 0.4rem; font-size: 0.8rem;">If you trade through a Ltd, partnership, or VAT group, use a company accounting tool instead.</p>
        </div>

        @if ($errors->any())
            <div class="alert-error">
                <strong>Please fix the following:</strong>
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
                <label>Full name / practice name</label>
                <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. Perit John Borg">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required placeholder="john@example.com">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <label>Access / promo code <span style="font-weight: 500; color: var(--text-muted);">(optional)</span></label>
                <input type="text" name="promo_code" value="{{ old('promo_code', request('promo_code', request('code'))) }}" maxlength="40" placeholder="e.g. FOUNDING-50" autocomplete="off" style="text-transform: uppercase; letter-spacing: 0.04em; font-weight: 600;">
                <p class="field-hint">Leave blank to start on Free.</p>
            </div>
            <div class="form-group">
                <label>Referral code <span style="font-weight: 500; color: var(--text-muted);">(optional)</span></label>
                <input type="text" name="ref" value="{{ old('ref', request('ref')) }}" maxlength="40" placeholder="Friend's code" autocomplete="off" style="text-transform: uppercase; letter-spacing: 0.04em; font-weight: 600;">
            </div>

            <label style="display: block; font-weight: 700; margin-bottom: 0.4rem; color: var(--primary-navy); font-size: 0.88rem;">Master Service Agreement &amp; Privacy Policy</label>
            <p style="margin: 0 0 0.65rem; font-size: 0.78rem; color: var(--text-muted); line-height: 1.4;">
                Full text at
                <a href="/msa" target="_blank" rel="noopener" style="color: var(--primary-cerulean); font-weight: 600;">/msa</a>
                and
                <a href="/privacy" target="_blank" rel="noopener" style="color: var(--primary-cerulean); font-weight: 600;">/privacy</a>.
            </p>
            <div class="scroll-instruction">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M19 12l-7 7-7-7"/></svg>
                Scroll to the bottom to enable accept.
            </div>

            <div class="legal-box" id="legalScrollBox">
                <p style="margin-top: 0;"><strong>PractisBase Master Service Agreement (R02)</strong> — Effective 5 August 2026 · Cerulean Labs Limited</p>
                @include('legal.partials.msa-body')
                <h2>Privacy Policy</h2>
                <p>
                    By accepting, you also agree to the PractisBase Privacy Policy (R01), including the controller/processor split,
                    Medical Vault rules, sub-processors (Railway, Cloudflare, Google Workspace, Stripe when live), and your GDPR rights.
                    The full Privacy Policy is at <a href="/privacy" target="_blank" rel="noopener" style="color: var(--primary-cerulean); font-weight: 600;">/privacy</a>.
                </p>
            </div>

            <div class="checkbox-group">
                <input type="checkbox" id="confirmAgeAdult" name="confirm_age_adult" value="1" {{ old('confirm_age_adult') ? 'checked' : '' }} required>
                <label for="confirmAgeAdult">
                    I confirm I am <strong>18 or older</strong>.
                </label>
            </div>

            <div class="checkbox-group">
                <input type="checkbox" id="confirmSoleTrader" name="confirm_sole_trader" value="1" {{ old('confirm_sole_trader') ? 'checked' : '' }} required>
                <label for="confirmSoleTrader">
                    I am registering as a <strong>self employed sole trader</strong>, not a limited company.
                </label>
            </div>

            <div class="checkbox-group">
                <input type="checkbox" id="acceptTerms" name="accept_terms" disabled>
                <label for="acceptTerms">
                    I agree to the Master Service Agreement and Privacy Policy.
                </label>
            </div>

            <input type="hidden" name="read_duration_seconds" id="readDurationInput" value="0">

            <button type="submit" class="btn-submit" id="submitBtn" disabled>Create account</button>
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
            if (this.checked) {
                const timeAccepted = Date.now();
                const secondsSpent = Math.floor((timeAccepted - pageLoadTime) / 1000);
                readDurationInput.value = secondsSpent;
            }
        });
    </script>
</body>
</html>

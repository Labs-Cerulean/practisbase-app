<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Choose Plan | PractisBase</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
    <style>
        body { background-color: var(--bg-canvas); overflow-x: hidden; }
        .pricing-container { max-width: 1100px; margin: 2rem auto; padding: 2rem var(--space-lg); }
        .pricing-header { text-align: center; margin-bottom: 2rem; }
        .step-indicator { font-size: 0.85rem; color: var(--primary-cerulean); font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.5rem; }
        
        .pricing-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; }
        .pricing-card { background: var(--bg-surface); border-radius: var(--radius-lg); padding: 1.75rem; box-shadow: var(--shadow-md); border: 1px solid var(--border-light); display: flex; flex-direction: column; }
        .pricing-card.popular { border: 2px solid var(--primary-cerulean); transform: scale(1.02); position: relative; }
        .popular-badge { position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: var(--primary-cerulean); color: white; padding: 0.2rem 0.75rem; border-radius: 20px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; }
        
        .tier-name { font-size: 1.15rem; font-weight: 700; color: var(--primary-navy); margin-bottom: 0.25rem; }
        .tier-price { font-size: 2.25rem; font-weight: 700; color: var(--primary-navy); margin-bottom: 1rem; }
        .tier-price span { font-size: 0.9rem; color: var(--text-muted); font-weight: 400; }
        
        .feature-list { list-style: none; padding: 0; margin-bottom: 1.5rem; flex-grow: 1; font-size: 0.9rem; }
        .feature-list li { margin-bottom: 0.6rem; color: var(--text-main); display: flex; align-items: flex-start; gap: 0.5rem; line-height: 1.3; }
        .feature-list li::before { content: '✓'; color: var(--primary-cerulean); font-weight: bold; }
        .feature-list li.group-header { margin-top: 1rem; margin-bottom: 0.3rem; color: var(--primary-cerulean); font-weight: 700; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .feature-list li.group-header::before { display: none; }
        
        .btn-tier { width: 100%; padding: 0.65rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.95rem; transition: all 0.2s ease; cursor: pointer; text-align: center; border: none; }
        .btn-outline { background: transparent; border: 1px solid var(--primary-cerulean); color: var(--primary-cerulean); }
        .btn-outline:hover { background: rgba(2, 132, 199, 0.05); }
        .btn-solid { background: var(--primary-cerulean); color: white; border: 1px solid var(--primary-cerulean); }
        .btn-solid:hover { background: var(--primary-cerulean-hover); }
        
        /* DEV BYPASS BANNER */
        .dev-banner { background: #fef3c7; color: #b45309; text-align: center; padding: 0.75rem; border-radius: var(--radius-md); font-size: 0.85rem; font-weight: 600; margin-bottom: 2rem; border: 1px solid #fde68a; }
    </style>
</head>
<body>

    <main class="pricing-container">
        <div class="pricing-header">
            <div class="step-indicator">Step 3 of 3</div>
            <h2 style="color: var(--primary-navy); font-size: 2.25rem; margin-bottom: 0.5rem;">Select Your Tier</h2>
            <p style="color: var(--text-muted); font-size: 1rem;">[TESTING MODE: No credit card required. Click a tier to finalize setup.]</p>
        </div>

        <div class="dev-banner">
            🚧 DEV BYPASS ACTIVE: Selecting a paid tier will instantly upgrade your account for testing purposes. Stripe integration is currently bypassed.
        </div>

        <div class="pricing-grid">
            
            <div class="pricing-card">
                <div class="tier-name">Free</div>
                <div class="tier-price">€0<span>/mo</span></div>
                <ul class="feature-list">
                    <li><strong>Up to 5 Clients</strong></li>
                    <li>Basic Invoices & Ledger</li>
                    <li>Summary Dashboard</li>
                </ul>
                <form action="/onboarding/plans-submit" method="POST" style="margin-top: auto;">
                    @csrf
                    <input type="hidden" name="tier" value="free">
                    <button type="submit" class="btn-tier btn-outline">Select Free</button>
                </form>
            </div>

            <div class="pricing-card popular">
                <div class="popular-badge">Most Popular</div>
                <div class="tier-name">Standard</div>
                <div class="tier-price">€15.99<span>/mo</span></div>
                <ul class="feature-list">
                    <li><strong>Unlimited Clients</strong></li>
                    <li>Custom Branding & Logo</li>
                    <li>Expense Tracking & Receipts</li>
                    <li><strong>Automated TA22 Form</strong></li>
                </ul>
                <form action="/onboarding/plans-submit" method="POST" style="margin-top: auto;">
                    @csrf
                    <input type="hidden" name="tier" value="standard">
                    <button type="submit" class="btn-tier btn-solid">Select Standard</button>
                </form>
            </div>

            <div class="pricing-card" style="border-top: 4px solid #059669;">
                <div class="tier-name" style="color: #059669;">Pro Medical ⚕️</div>
                <div class="tier-price">€49.99<span>/mo</span></div>
                <ul class="feature-list">
                    <li>All Standard Features</li>
                    <li class="group-header" style="color: #059669;">Medical Tools:</li>
                    <li>Secure Patient Journals</li>
                    <li>Digital Prescriptions</li>
                    <li>Referral Letters</li>
                </ul>
                <form action="/onboarding/plans-submit" method="POST" style="margin-top: auto;">
                    @csrf
                    <input type="hidden" name="tier" value="pro-med">
                    <button type="submit" class="btn-tier btn-outline" style="border-color: #059669; color: #059669;">Select Pro Med</button>
                </form>
            </div>

            <div class="pricing-card" style="border-top: 4px solid var(--primary-navy);">
                <div class="tier-name" style="color: var(--primary-navy);">Pro Architect 📐</div>
                <div class="tier-price">€49.99<span>/mo</span></div>
                <ul class="feature-list">
                    <li>All Standard Features</li>
                    <li class="group-header" style="color: var(--primary-navy);">Architect Tools:</li>
                    <li>Architect DMS</li>
                    <li>Document Stamper</li>
                    <li>Project Phase Tracking</li>
                </ul>
                <form action="/onboarding/plans-submit" method="POST" style="margin-top: auto;">
                    @csrf
                    <input type="hidden" name="tier" value="pro-arch">
                    <button type="submit" class="btn-tier btn-outline" style="border-color: var(--primary-navy); color: var(--primary-navy);">Select Pro Arch</button>
                </form>
            </div>

            <div class="pricing-card" style="border-top: 4px solid #d97706;">
                <div class="tier-name" style="color: #d97706;">Pro Engineer ⚙️</div>
                <div class="tier-price">€49.99<span>/mo</span></div>
                <ul class="feature-list">
                    <li>All Standard Features</li>
                    <li class="group-header" style="color: #d97706;">Engineering Tools:</li>
                    <li>EMS / BMS Templates</li>
                    <li>Certification Generator</li>
                    <li>Technical Specs Export</li>
                </ul>
                <form action="/onboarding/plans-submit" method="POST" style="margin-top: auto;">
                    @csrf
                    <input type="hidden" name="tier" value="pro-eng">
                    <button type="submit" class="btn-tier btn-outline" style="border-color: #d97706; color: #d97706;">Select Pro Eng</button>
                </form>
            </div>

        </div>
    </main>

</body>
</html>
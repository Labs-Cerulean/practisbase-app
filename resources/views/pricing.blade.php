<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pricing & Tiers | PractisBase</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="/css/style.css">
    
    <style>
        body { background-color: var(--bg-canvas); overflow-x: hidden; }
        .public-header { 
            padding: var(--space-md) 5%; 
            background: #fff; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            box-shadow: var(--shadow-sm); 
        }
        
        .pricing-container { max-width: 1200px; margin: 2rem auto; padding: 0 var(--space-lg); }
        .pricing-header { text-align: center; margin-bottom: 2.5rem; }
        .pricing-header h1 { color: var(--primary-navy); font-size: 2.25rem; margin-bottom: 0.5rem; }
        .pricing-header p { color: var(--text-muted); font-size: 1.05rem; }
        
        .pricing-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); 
            gap: 1.5rem; 
        }
        .pricing-card { 
            background: var(--bg-surface); 
            border-radius: var(--radius-lg); 
            padding: 1.75rem; 
            box-shadow: var(--shadow-md); 
            border: 1px solid var(--border-light); 
            display: flex; 
            flex-direction: column; 
        }
        .pricing-card.popular { 
            border: 2px solid var(--primary-cerulean); 
            transform: scale(1.02); 
            position: relative; 
        }
        .popular-badge { 
            position: absolute; 
            top: -12px; 
            left: 50%; 
            transform: translateX(-50%); 
            background: var(--primary-cerulean); 
            color: white; 
            padding: 0.2rem 0.75rem; 
            border-radius: 20px; 
            font-size: 0.7rem; 
            font-weight: 700; 
            text-transform: uppercase; 
        }
        
        .tier-name { font-size: 1.15rem; font-weight: 700; color: var(--primary-navy); margin-bottom: 0.25rem; }
        .tier-price { font-size: 2.25rem; font-weight: 700; color: var(--primary-navy); margin-bottom: 0.35rem; }
        .tier-price span { font-size: 0.9rem; color: var(--text-muted); font-weight: 400; }
        .tier-blurb { font-size: 0.8rem; color: var(--text-muted); margin: 0 0 1rem; line-height: 1.4; min-height: 2.4em; }
        
        .feature-list { list-style: none; padding: 0; margin-bottom: 1.5rem; flex-grow: 1; font-size: 0.9rem; }
        .feature-list li { margin-bottom: 0.6rem; color: var(--text-main); display: flex; align-items: flex-start; gap: 0.5rem; line-height: 1.3; }
        .feature-list li::before { content: '✓'; color: var(--primary-cerulean); font-weight: bold; }
        
        .feature-list li.group-header { margin-top: 1rem; margin-bottom: 0.3rem; font-weight: 700; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .feature-list li.group-header::before { display: none; }
        
        .btn-tier { 
            display: inline-block; 
            text-align: center; 
            width: 100%; 
            padding: 0.75rem; 
            border-radius: var(--radius-md); 
            font-weight: 600; 
            font-size: 0.95rem;
            transition: all 0.2s ease; 
            cursor: pointer;
            text-decoration: none;
            margin-top: auto;
        }
        .btn-outline { background: transparent; border: 1px solid var(--primary-cerulean); color: var(--primary-cerulean); }
        .btn-outline:hover { background: rgba(2, 132, 199, 0.05); }
        .btn-solid { background: var(--primary-cerulean); color: white; border: 1px solid var(--primary-cerulean); }
        .btn-solid:hover { background: var(--primary-cerulean-hover); }
        .section-label { font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--text-muted); margin: 2rem 0 0.85rem; }
    </style>
</head>
<body>

    <header class="public-header">
        <div style="display: flex; align-items: center;">
            <img src="/images/logo.png" alt="PractisBase" style="width: 100%; max-width: 160px; height: auto; object-fit: contain;">
        </div>
        <div style="display: flex; align-items: center; gap: 1.5rem;">
            <a href="/login" style="color: var(--primary-navy); font-weight: 600; font-size: 0.9rem;">
                Already a member? Sign in
            </a>
            <a href="/register" class="btn-tier btn-solid" style="padding: 0.5rem 1.25rem; width: auto; font-size: 0.9rem; margin-top: 0;">
                Join the Professional's Club
            </a>
        </div>
    </header>

    <main class="pricing-container">
        <div class="pricing-header">
            <h1>Simple, Professional Pricing</h1>
            <p>Built exclusively for Maltese <strong>self-employed sole traders</strong> — not Ltd companies. Start with practice tools or accounts; grow into the full system.</p>
            <p style="margin-top: 0.75rem; font-size: 0.95rem; color: #1e3a8a; background: #eff6ff; display: inline-block; padding: 0.5rem 0.9rem; border-radius: 8px; border: 1px solid #bfdbfe;">
                Closed beta — card billing is not live yet. Invited testers get plan access without Stripe.
            </p>
        </div>

        <div class="section-label">Accounts</div>
        <div class="pricing-grid">
            <div class="pricing-card">
                <div class="tier-name">Free</div>
                <div class="tier-price">€0<span>/mo</span></div>
                <p class="tier-blurb">Basic invoicing to get started.</p>
                <ul class="feature-list">
                    <li><strong>Up to 5 Clients</strong> (lifetime)</li>
                    <li>Invoices &amp; RFPs</li>
                    <li>Overview dashboard</li>
                </ul>
                <a href="/register" class="btn-tier btn-outline">Start for Free</a>
            </div>

            <div class="pricing-card popular">
                <div class="popular-badge">Accounts</div>
                <div class="tier-name">Standard</div>
                <div class="tier-price">€{{ \App\Support\TierPolicy::PRICE_STANDARD }}<span>/mo</span></div>
                <p class="tier-blurb">Sole-trader Tax &amp; VAT — no profession tools.</p>
                <ul class="feature-list">
                    <li><strong>Unlimited Clients</strong></li>
                    <li>Tax &amp; VAT report</li>
                    <li>Expenses &amp; receipts</li>
                    <li>Accountant pack</li>
                    <li>Custom branding</li>
                </ul>
                <a href="/register" class="btn-tier btn-solid">Upgrade to Standard</a>
            </div>
        </div>

        <div class="section-label">Practice tools (profession-matched)</div>
        <div class="pricing-grid">
            <div class="pricing-card" style="border-top: 4px solid #059669;">
                <div class="tier-name" style="color: #059669;">Practice Medical</div>
                <div class="tier-price">€{{ \App\Support\TierPolicy::PRICE_PRACTICE }}<span>/mo</span></div>
                <p class="tier-blurb">Clinical tools + Free invoicing. Add Tax &amp; VAT later.</p>
                <ul class="feature-list">
                    <li>Free financial layer (5 clients)</li>
                    <li class="group-header" style="color: #059669;">Medical tools:</li>
                    <li>Secure patient journals</li>
                    <li>Prescriptions &amp; referrals</li>
                    <li>Clinical stampables</li>
                </ul>
                <a href="/register" class="btn-tier btn-outline" style="border-color: #059669; color: #059669;">Select Practice Med</a>
            </div>

            <div class="pricing-card" style="border-top: 4px solid var(--primary-navy);">
                <div class="tier-name" style="color: var(--primary-navy);">Practice Architect</div>
                <div class="tier-price">€{{ \App\Support\TierPolicy::PRICE_PRACTICE }}<span>/mo</span></div>
                <p class="tier-blurb">Project tools + Free invoicing. Add Tax &amp; VAT later.</p>
                <ul class="feature-list">
                    <li>Free financial layer (5 clients)</li>
                    <li class="group-header" style="color: var(--primary-navy);">Architect tools:</li>
                    <li>Architect DMS</li>
                    <li>Document stamper</li>
                    <li>Project phase tracking</li>
                </ul>
                <a href="/register" class="btn-tier btn-outline" style="border-color: var(--primary-navy); color: var(--primary-navy);">Select Practice Arch</a>
            </div>

            <div class="pricing-card" style="border-top: 4px solid #d97706;">
                <div class="tier-name" style="color: #d97706;">Practice Engineer</div>
                <div class="tier-price">€{{ \App\Support\TierPolicy::PRICE_PRACTICE }}<span>/mo</span></div>
                <p class="tier-blurb">Engineering tools + Free invoicing. Add Tax &amp; VAT later.</p>
                <ul class="feature-list">
                    <li>Free financial layer (5 clients)</li>
                    <li class="group-header" style="color: #d97706;">Engineering tools:</li>
                    <li>Projects &amp; certificates</li>
                    <li>Technical exports</li>
                </ul>
                <a href="/register" class="btn-tier btn-outline" style="border-color: #d97706; color: #d97706;">Select Practice Eng</a>
            </div>
        </div>

        <div class="section-label">Full Pro — practice + Standard accounts</div>
        <div class="pricing-grid">
            <div class="pricing-card" style="border-top: 4px solid #059669;">
                <div class="tier-name" style="color: #059669;">Pro Medical</div>
                <div class="tier-price">€{{ \App\Support\TierPolicy::PRICE_PRO }}<span>/mo</span></div>
                <p class="tier-blurb">All Standard financial features + medical tools.</p>
                <ul class="feature-list">
                    <li>Unlimited clients &amp; Tax &amp; VAT</li>
                    <li class="group-header" style="color: #059669;">Medical tools:</li>
                    <li>Patients, prescriptions, referrals</li>
                </ul>
                <a href="/register" class="btn-tier btn-outline" style="border-color: #059669; color: #059669;">Select Pro Med</a>
            </div>

            <div class="pricing-card" style="border-top: 4px solid var(--primary-navy);">
                <div class="tier-name" style="color: var(--primary-navy);">Pro Architect</div>
                <div class="tier-price">€{{ \App\Support\TierPolicy::PRICE_PRO }}<span>/mo</span></div>
                <p class="tier-blurb">All Standard financial features + architect tools.</p>
                <ul class="feature-list">
                    <li>Unlimited clients &amp; Tax &amp; VAT</li>
                    <li class="group-header" style="color: var(--primary-navy);">Architect tools:</li>
                    <li>DMS, stamper, projects</li>
                </ul>
                <a href="/register" class="btn-tier btn-outline" style="border-color: var(--primary-navy); color: var(--primary-navy);">Select Pro Arch</a>
            </div>

            <div class="pricing-card" style="border-top: 4px solid #d97706;">
                <div class="tier-name" style="color: #d97706;">Pro Engineer</div>
                <div class="tier-price">€{{ \App\Support\TierPolicy::PRICE_PRO }}<span>/mo</span></div>
                <p class="tier-blurb">All Standard financial features + engineering tools.</p>
                <ul class="feature-list">
                    <li>Unlimited clients &amp; Tax &amp; VAT</li>
                    <li class="group-header" style="color: #d97706;">Engineering tools:</li>
                    <li>Projects &amp; certificates</li>
                </ul>
                <a href="/register" class="btn-tier btn-outline" style="border-color: #d97706; color: #d97706;">Select Pro Eng</a>
            </div>
        </div>
    </main>

</body>
</html>

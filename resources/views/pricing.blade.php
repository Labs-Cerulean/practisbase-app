<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PractisBase | Pricing for Maltese sole traders</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">

    <style>
        :root {
            --pb-ink: #0f2744;
            --pb-sea: #0284c7;
            --pb-sea-deep: #0369a1;
            --pb-mist: #e8f4fb;
            --pb-sand: #f4f7fb;
            --pb-line: #d5e2ef;
            --pb-mute: #5b6b7c;
            --path-accounts: #0284c7;
            --path-med: #0f766e;
            --path-med-soft: #ccfbf1;
            --path-arch: #3f6212;
            --path-arch-soft: #ecfccb;
            --path-eng: #0c4a6e;
            --path-eng-soft: #e0f2fe;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "DM Sans", ui-sans-serif, system-ui, sans-serif;
            color: var(--pb-ink);
            background:
                radial-gradient(1200px 600px at 10% -10%, rgba(2, 132, 199, 0.18), transparent 55%),
                radial-gradient(900px 500px at 100% 0%, rgba(15, 39, 68, 0.12), transparent 50%),
                linear-gradient(180deg, #f7fbfe 0%, var(--pb-sand) 42%, #eef3f8 100%);
            min-height: 100vh;
        }

        .site-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.1rem clamp(1.25rem, 4vw, 3rem);
            max-width: 1120px;
            margin: 0 auto;
        }
        .site-header img { height: 36px; width: auto; }
        .header-actions { display: flex; align-items: center; gap: 0.85rem; }
        .header-actions a { font-weight: 600; font-size: 0.9rem; text-decoration: none; color: var(--pb-ink); }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.7rem 1.15rem;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.9rem;
            text-decoration: none;
            border: 1px solid transparent;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }
        .btn:hover { transform: translateY(-1px); }
        .btn-primary {
            background: var(--pb-sea);
            color: #fff;
            box-shadow: 0 10px 24px rgba(2, 132, 199, 0.25);
        }
        .btn-primary:hover { background: var(--pb-sea-deep); }
        .btn-ghost {
            background: rgba(255, 255, 255, 0.7);
            border-color: var(--pb-line);
            color: var(--pb-ink);
        }

        .hero {
            max-width: 1120px;
            margin: 0 auto;
            padding: 1.5rem clamp(1.25rem, 4vw, 3rem) 2.5rem;
            text-align: center;
        }
        .hero-brand {
            font-family: Fraunces, Georgia, serif;
            font-size: clamp(2.4rem, 6vw, 3.6rem);
            font-weight: 700;
            letter-spacing: -0.03em;
            color: var(--pb-ink);
            margin: 0 0 0.75rem;
            line-height: 1.05;
        }
        .hero h1 {
            font-family: Fraunces, Georgia, serif;
            font-size: clamp(1.35rem, 3vw, 1.85rem);
            font-weight: 600;
            margin: 0 0 0.75rem;
            color: var(--pb-ink);
            line-height: 1.25;
        }
        .hero p {
            margin: 0 auto;
            max-width: 36rem;
            color: var(--pb-mute);
            font-size: 1.05rem;
            line-height: 1.55;
        }
        .hero-cta { margin-top: 1.5rem; display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap; }
        .beta-chip {
            display: inline-block;
            margin-top: 1.25rem;
            font-size: 0.8rem;
            font-weight: 600;
            color: #1e3a8a;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            padding: 0.45rem 0.85rem;
            border-radius: 999px;
        }

        .section {
            max-width: 1120px;
            margin: 0 auto;
            padding: 0 clamp(1.25rem, 4vw, 3rem) 3rem;
        }
        .section-title {
            font-family: Fraunces, Georgia, serif;
            font-size: 1.45rem;
            margin: 0 0 0.35rem;
            color: var(--pb-ink);
        }
        .section-sub {
            margin: 0 0 1.35rem;
            color: var(--pb-mute);
            font-size: 0.95rem;
            line-height: 1.45;
            max-width: 40rem;
        }

        .ladder {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1rem;
        }
        @media (max-width: 960px) {
            .ladder { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 560px) {
            .ladder { grid-template-columns: 1fr; }
        }

        .plan {
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid var(--pb-line);
            border-radius: 16px;
            padding: 1.35rem 1.25rem 1.25rem;
            display: flex;
            flex-direction: column;
            min-height: 100%;
            box-shadow: 0 12px 30px rgba(15, 39, 68, 0.06);
            position: relative;
            overflow: hidden;
        }
        .plan.featured {
            border-color: rgba(2, 132, 199, 0.55);
            box-shadow: 0 16px 36px rgba(2, 132, 199, 0.14);
            background: linear-gradient(180deg, #fff 0%, #f3faff 100%);
        }
        .plan.bargain {
            border-color: rgba(5, 150, 105, 0.45);
            background: linear-gradient(180deg, #fff 0%, #f3fbf7 100%);
        }
        .plan-badge {
            position: absolute;
            top: 0.85rem;
            right: 0.85rem;
            font-size: 0.65rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            padding: 0.25rem 0.5rem;
            border-radius: 999px;
            background: var(--pb-mist);
            color: var(--pb-sea-deep);
        }
        .plan.bargain .plan-badge {
            background: #d1fae5;
            color: #047857;
        }
        .plan-name {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--pb-mute);
            margin: 0 0 0.35rem;
        }
        .plan-price {
            font-family: Fraunces, Georgia, serif;
            font-size: 2.35rem;
            font-weight: 700;
            margin: 0;
            letter-spacing: -0.02em;
            color: var(--pb-ink);
        }
        .plan-vat {
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--pb-mute);
            margin: 0.15rem 0 0;
        }
        .vat-note {
            margin: 1.25rem 0 0;
            padding: 0.85rem 1rem;
            background: rgba(255, 255, 255, 0.75);
            border: 1px solid var(--pb-line);
            border-radius: 12px;
            color: var(--pb-mute);
            font-size: 0.88rem;
            line-height: 1.45;
            max-width: 40rem;
        }
        .amount-vat {
            display: block;
            font-size: 0.68rem;
            font-weight: 700;
            color: var(--pb-mute);
            margin-top: 0.15rem;
            text-align: right;
        }
        .plan-price span {
            font-family: "DM Sans", sans-serif;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--pb-mute);
        }
        .plan-blurb {
            margin: 0.55rem 0 1rem;
            font-size: 0.88rem;
            color: var(--pb-mute);
            line-height: 1.45;
            min-height: 2.6em;
        }
        .plan ul {
            list-style: none;
            margin: 0 0 1.25rem;
            padding: 0;
            flex: 1;
        }
        .plan li {
            display: flex;
            gap: 0.45rem;
            align-items: flex-start;
            font-size: 0.88rem;
            line-height: 1.4;
            margin-bottom: 0.5rem;
            color: var(--pb-ink);
        }
        .plan li::before {
            content: "";
            width: 0.55rem;
            height: 0.55rem;
            margin-top: 0.35rem;
            border-radius: 999px;
            background: var(--pb-sea);
            flex-shrink: 0;
        }
        .save-note {
            font-size: 0.78rem;
            font-weight: 700;
            color: #047857;
            margin: -0.35rem 0 0.85rem;
        }

        .accounts-path {
            margin-top: 2.5rem;
            padding: 1.5rem 1.5rem 1.35rem;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.82);
            border: 1px solid var(--pb-line);
            border-left: 5px solid var(--path-accounts);
        }
        .accounts-path .section-title { font-size: 1.2rem; }
        .step-row {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.85rem;
            margin-top: 1rem;
        }
        @media (max-width: 720px) {
            .step-row { grid-template-columns: 1fr; }
        }
        .step {
            background: #fff;
            border: 1px solid var(--pb-line);
            border-radius: 12px;
            padding: 1rem 1.05rem;
        }
        .step-num {
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--path-accounts);
            margin: 0 0 0.35rem;
        }
        .step h3 {
            margin: 0 0 0.35rem;
            font-size: 1rem;
            color: var(--pb-ink);
        }
        .step p {
            margin: 0;
            font-size: 0.88rem;
            color: var(--pb-mute);
            line-height: 1.45;
        }

        .paths-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }
        @media (max-width: 900px) {
            .paths-grid { grid-template-columns: 1fr; }
        }

        .prof-path {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid var(--pb-line);
            border-radius: 16px;
            padding: 1.25rem 1.2rem 1.2rem;
            display: flex;
            flex-direction: column;
            min-height: 100%;
            box-shadow: 0 12px 30px rgba(15, 39, 68, 0.06);
            border-top: 5px solid var(--path-accent);
        }
        .prof-path.med { --path-accent: var(--path-med); --path-soft: var(--path-med-soft); }
        .prof-path.arch { --path-accent: var(--path-arch); --path-soft: var(--path-arch-soft); }
        .prof-path.eng { --path-accent: var(--path-eng); --path-soft: var(--path-eng-soft); }

        .prof-label {
            display: inline-block;
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: var(--path-accent);
            background: var(--path-soft);
            padding: 0.3rem 0.55rem;
            border-radius: 6px;
            margin-bottom: 0.65rem;
        }
        .prof-path h3 {
            font-family: Fraunces, Georgia, serif;
            font-size: 1.25rem;
            margin: 0 0 0.35rem;
            color: var(--pb-ink);
        }
        .prof-path .prof-lead {
            margin: 0 0 1rem;
            font-size: 0.9rem;
            color: var(--pb-mute);
            line-height: 1.45;
        }
        .prof-path .tools-label {
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--path-accent);
            margin: 0 0 0.45rem;
        }
        .prof-path ul {
            list-style: none;
            margin: 0 0 1.15rem;
            padding: 0;
            flex: 1;
        }
        .prof-path li {
            display: flex;
            gap: 0.45rem;
            align-items: flex-start;
            font-size: 0.88rem;
            line-height: 1.4;
            margin-bottom: 0.45rem;
            color: var(--pb-ink);
        }
        .prof-path li::before {
            content: "";
            width: 0.55rem;
            height: 0.55rem;
            margin-top: 0.35rem;
            border-radius: 999px;
            background: var(--path-accent);
            flex-shrink: 0;
        }
        .prof-prices {
            display: grid;
            gap: 0.55rem;
            margin-top: auto;
        }
        .prof-price-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
            padding: 0.7rem 0.85rem;
            border-radius: 10px;
            border: 1px solid var(--pb-line);
            background: #fff;
            text-decoration: none;
            color: inherit;
            transition: border-color 0.2s ease, transform 0.2s ease;
        }
        .prof-price-row:hover {
            border-color: var(--path-accent);
            transform: translateY(-1px);
        }
        .prof-price-row.pro {
            background: var(--path-soft);
            border-color: var(--path-accent);
        }
        .prof-price-row strong {
            display: block;
            font-size: 0.82rem;
            color: var(--pb-ink);
        }
        .prof-price-row span.hint {
            display: block;
            font-size: 0.72rem;
            color: var(--pb-mute);
            margin-top: 0.1rem;
        }
        .prof-price-row .amount {
            font-family: Fraunces, Georgia, serif;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--path-accent);
            white-space: nowrap;
        }
        .prof-price-row .amount small {
            font-family: "DM Sans", sans-serif;
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--pb-mute);
        }

        .site-footer {
            text-align: center;
            padding: 1rem 1.5rem 2.5rem;
            color: var(--pb-mute);
            font-size: 0.8rem;
        }

        @media (prefers-reduced-motion: no-preference) {
            .hero-brand, .hero h1, .hero p, .hero-cta, .beta-chip {
                animation: rise 0.7s ease both;
            }
            .hero h1 { animation-delay: 0.08s; }
            .hero p { animation-delay: 0.14s; }
            .hero-cta { animation-delay: 0.2s; }
            .beta-chip { animation-delay: 0.26s; }
            .ladder .plan, .prof-path, .accounts-path {
                animation: rise 0.65s ease both;
            }
            .ladder .plan:nth-child(1) { animation-delay: 0.05s; }
            .ladder .plan:nth-child(2) { animation-delay: 0.12s; }
            .ladder .plan:nth-child(3) { animation-delay: 0.19s; }
            .ladder .plan:nth-child(4) { animation-delay: 0.26s; }
            .prof-path.med { animation-delay: 0.08s; }
            .prof-path.arch { animation-delay: 0.16s; }
            .prof-path.eng { animation-delay: 0.24s; }
        }
        @keyframes rise {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
@php
    $std = \App\Support\TierPolicy::PRICE_STANDARD;
    $prac = \App\Support\TierPolicy::PRICE_PRACTICE;
    $pro = \App\Support\TierPolicy::PRICE_PRO;
    $save = \App\Support\TierPolicy::bundleSavingsEuro();
    $vatSuffix = \App\Support\TierPolicy::priceVatSuffix();
@endphp

    <header class="site-header">
        <a href="/" style="display: inline-flex; align-items: center; text-decoration: none;">
            <img src="/images/logo.png" alt="PractisBase">
        </a>
        <div class="header-actions">
            <a href="/">Product</a>
            <a href="/login">Sign in</a>
            <a href="/register" class="btn btn-primary">Register</a>
        </div>
    </header>

    <section class="hero">
        <p class="hero-brand">Pricing</p>
        <h1>Clear plans for Maltese sole traders.</h1>
        <p>Start lean on Free, add Standard accounts or Practice tools, or take Full Pro for both. Sole traders only — not Ltd companies.</p>
        <div class="hero-cta">
            <a href="#plans" class="btn btn-primary">Compare plans</a>
            <a href="/register" class="btn btn-ghost">Register</a>
        </div>
        <div class="beta-chip">No card charge yet. Access codes unlock Full Pro for free.</div>
    </section>

    <section class="section" id="plans">
        <h2 class="section-title">The ladder</h2>
        <p class="section-sub">Four clear steps. Full Pro is priced as a bundle: about €{{ $save }}/mo less than buying Standard and Practice separately.</p>
        <div class="ladder">
            <article class="plan">
                <div class="plan-name">Free</div>
                <div class="plan-price">€0<span>/mo</span></div>
                <p class="plan-blurb">Try invoicing with a hard client cap.</p>
                <ul>
                    <li>Up to 5 lifetime clients</li>
                    <li>Invoices and RFPs</li>
                    <li>Overview dashboard</li>
                </ul>
                <a href="/register" class="btn btn-ghost">Start free</a>
            </article>

            <article class="plan featured">
                <span class="plan-badge">Accounts</span>
                <div class="plan-name">Standard</div>
                <div class="plan-price">€{{ $std }}<span>/mo</span></div>
                <p class="plan-vat">{{ $vatSuffix }}</p>
                <p class="plan-blurb">Sole trader Tax and VAT. No profession tools.</p>
                <ul>
                    <li>Unlimited clients</li>
                    <li>Tax and VAT report</li>
                    <li>Expenses and accountant pack</li>
                    <li>Custom branding</li>
                </ul>
                <a href="/register" class="btn btn-primary">Choose Standard</a>
            </article>

            <article class="plan">
                <span class="plan-badge">Profession</span>
                <div class="plan-name">Practice</div>
                <div class="plan-price">€{{ $prac }}<span>/mo</span></div>
                <p class="plan-vat">{{ $vatSuffix }}</p>
                <p class="plan-blurb">Your profession tools plus Free invoicing.</p>
                <ul>
                    <li>Free financial layer (5 clients)</li>
                    <li>Medical, Architect, or Engineer tools</li>
                    <li>Stampables and certificates</li>
                    <li>Upgrade path to Full Pro</li>
                </ul>
                <a href="#paths" class="btn btn-ghost">See practice paths</a>
            </article>

            <article class="plan bargain">
                <span class="plan-badge">Best value</span>
                <div class="plan-name">Full Pro</div>
                <div class="plan-price">€{{ $pro }}<span>/mo</span></div>
                <p class="plan-vat">{{ $vatSuffix }}</p>
                <p class="plan-blurb">Standard accounts plus your profession package.</p>
                <p class="save-note">Save €{{ $save }}/mo vs €{{ $std }} + €{{ $prac }} (ex-VAT)</p>
                <ul>
                    <li>Everything in Standard</li>
                    <li>Everything in Practice</li>
                    <li>Unlimited clients</li>
                    <li>One plan for the whole desk</li>
                </ul>
                <a href="#paths" class="btn btn-primary">Choose Full Pro</a>
            </article>
        </div>

        <div class="accounts-path" id="accounts-path">
            <h2 class="section-title">Accounts path</h2>
            <p class="section-sub" style="margin-bottom: 0;">The general route for any sole trader who needs the books first. Add a profession package later when the practice tools matter.</p>
            <div class="step-row">
                <div class="step">
                    <div class="step-num">Step 1</div>
                    <h3>Free</h3>
                    <p>Invoice a small client list and learn the desk.</p>
                </div>
                <div class="step">
                    <div class="step-num">Step 2</div>
                    <h3>Standard</h3>
                    <p>Unlock Tax and VAT, expenses, and the accountant pack.</p>
                </div>
                <div class="step">
                    <div class="step-num">Step 3</div>
                    <h3>Full Pro</h3>
                    <p>Keep Standard accounts and add Medical, Architect, or Engineer tools.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section" id="paths">
        <h2 class="section-title">Practice paths</h2>
        <p class="section-sub">Each profession gets its own tools. Practice is tools plus Free invoicing. Full Pro adds Standard Tax and VAT (save €{{ $save }}/mo vs buying both). Paid prices are ex-VAT ({{ $vatSuffix }}).</p>

        <div class="paths-grid">
            <article class="prof-path med">
                <span class="prof-label">Medical</span>
                <h3>Clinical desk</h3>
                <p class="prof-lead">For doctors who need a vaulted patient record and issued stampables.</p>
                <div class="tools-label">What you get</div>
                <ul>
                    <li>Encrypted patient vault and journals</li>
                    <li>Prescriptions with pharmacist issue codes</li>
                    <li>Referrals and medical certificates</li>
                    <li>Clinical stamp and stampable ledger</li>
                    <li>Trusted device unlock and medical backup</li>
                </ul>
                <div class="prof-prices">
                    <a class="prof-price-row" href="/register">
                        <span>
                            <strong>Practice Medical</strong>
                            <span class="hint">Tools + Free invoicing</span>
                        </span>
                        <span class="amount">€{{ $prac }}<small>/mo</small><span class="amount-vat">{{ $vatSuffix }}</span></span>
                    </a>
                    <a class="prof-price-row pro" href="/register">
                        <span>
                            <strong>Pro Medical</strong>
                            <span class="hint">Tools + Standard accounts</span>
                        </span>
                        <span class="amount">€{{ $pro }}<small>/mo</small><span class="amount-vat">{{ $vatSuffix }}</span></span>
                    </a>
                </div>
            </article>

            <article class="prof-path arch">
                <span class="prof-label">Architect</span>
                <h3>Studio desk</h3>
                <p class="prof-lead">For architects running condition reports, method statements, stamps, and project files.</p>
                <div class="tools-label">What you get</div>
                <ul>
                    <li>Condition reports and method statements</li>
                    <li>BCA template catalog + blank downloads</li>
                    <li>Architect document management (DMS)</li>
                    <li>Project records, phase tracking, and stamper</li>
                    <li>Practice branding on exports</li>
                </ul>
                <div class="prof-prices">
                    <a class="prof-price-row" href="/register">
                        <span>
                            <strong>Practice Architect</strong>
                            <span class="hint">Tools + Free invoicing</span>
                        </span>
                        <span class="amount">€{{ $prac }}<small>/mo</small><span class="amount-vat">{{ $vatSuffix }}</span></span>
                    </a>
                    <a class="prof-price-row pro" href="/register">
                        <span>
                            <strong>Pro Architect</strong>
                            <span class="hint">Tools + Standard accounts</span>
                        </span>
                        <span class="amount">€{{ $pro }}<small>/mo</small><span class="amount-vat">{{ $vatSuffix }}</span></span>
                    </a>
                </div>
            </article>

            <article class="prof-path eng">
                <span class="prof-label">Engineer</span>
                <h3>Technical desk</h3>
                <p class="prof-lead">For engineers who need projects, field certificates, and specialised reports.</p>
                <div class="tools-label">What you get</div>
                <ul>
                    <li>Engineering project workspace (Client → Project → PA)</li>
                    <li>Field certificates and specialised reports</li>
                    <li>Site photos + drawings / document version control</li>
                    <li>Shared simple certificate register</li>
                    <li>Practice branding on stampable PDFs</li>
                </ul>
                <div class="prof-prices">
                    <a class="prof-price-row" href="/register">
                        <span>
                            <strong>Practice Engineer</strong>
                            <span class="hint">Tools + Free invoicing</span>
                        </span>
                        <span class="amount">€{{ $prac }}<small>/mo</small><span class="amount-vat">{{ $vatSuffix }}</span></span>
                    </a>
                    <a class="prof-price-row pro" href="/register">
                        <span>
                            <strong>Pro Engineer</strong>
                            <span class="hint">Tools + Standard accounts</span>
                        </span>
                        <span class="amount">€{{ $pro }}<small>/mo</small><span class="amount-vat">{{ $vatSuffix }}</span></span>
                    </a>
                </div>
            </article>
        </div>
    </section>

    <footer class="site-footer">
        PractisBase models self employed sole traders in Malta. Sole trader scope only, not Ltd company accounts.
        <div style="margin-top: 0.65rem; display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="/" style="color: var(--pb-sea); font-weight: 600; text-decoration: none;">Product</a>
            <a href="/privacy" style="color: var(--pb-sea); font-weight: 600; text-decoration: none;">Privacy Policy</a>
            <a href="/msa" style="color: var(--pb-sea); font-weight: 600; text-decoration: none;">Master Service Agreement</a>
        </div>
    </footer>
</body>
</html>

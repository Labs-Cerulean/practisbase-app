<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PractisBase — Pricing for Maltese sole traders</title>

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

        .profession-tabs {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }
        .profession-tabs button {
            border: 1px solid var(--pb-line);
            background: rgba(255, 255, 255, 0.8);
            color: var(--pb-ink);
            border-radius: 999px;
            padding: 0.45rem 0.9rem;
            font-weight: 700;
            font-size: 0.82rem;
            cursor: pointer;
        }
        .profession-tabs button.active {
            background: var(--pb-ink);
            color: #fff;
            border-color: var(--pb-ink);
        }
        .profession-panel { display: none; }
        .profession-panel.active { display: block; }
        .pair {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }
        @media (max-width: 720px) {
            .pair { grid-template-columns: 1fr; }
        }
        .pair .plan { min-height: auto; }

        .path {
            margin-top: 2.5rem;
            padding: 1.35rem 1.5rem;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.75);
            border: 1px solid var(--pb-line);
        }
        .path ol {
            margin: 0.75rem 0 0;
            padding-left: 1.15rem;
            color: var(--pb-mute);
            font-size: 0.92rem;
            line-height: 1.55;
        }
        .path li { margin-bottom: 0.35rem; }
        .path strong { color: var(--pb-ink); }

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
            .ladder .plan {
                animation: rise 0.65s ease both;
            }
            .ladder .plan:nth-child(1) { animation-delay: 0.05s; }
            .ladder .plan:nth-child(2) { animation-delay: 0.12s; }
            .ladder .plan:nth-child(3) { animation-delay: 0.19s; }
            .ladder .plan:nth-child(4) { animation-delay: 0.26s; }
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
@endphp

    <header class="site-header">
        <img src="/images/logo.png" alt="PractisBase">
        <div class="header-actions">
            <a href="/login">Sign in</a>
            <a href="/register" class="btn btn-primary">Join beta</a>
        </div>
    </header>

    <section class="hero">
        <p class="hero-brand">PractisBase</p>
        <h1>Accounts when you need them. Practice tools when you don’t.</h1>
        <p>Built for Maltese self-employed sole traders — doctors, architects, engineers. Not Ltd companies. Start lean, grow into the full system.</p>
        <div class="hero-cta">
            <a href="/register" class="btn btn-primary">Start free</a>
            <a href="#plans" class="btn btn-ghost">Compare plans</a>
        </div>
        <div class="beta-chip">Closed beta — no card charge yet. Invited testers pick any plan.</div>
    </section>

    <section class="section" id="plans">
        <h2 class="section-title">The ladder</h2>
        <p class="section-sub">Four clear steps. Full Pro is priced as a bundle — about €{{ $save }}/mo less than buying Standard and Practice separately.</p>

        <div class="ladder">
            <article class="plan">
                <div class="plan-name">Free</div>
                <div class="plan-price">€0<span>/mo</span></div>
                <p class="plan-blurb">Try invoicing with a hard client cap.</p>
                <ul>
                    <li>Up to 5 lifetime clients</li>
                    <li>Invoices &amp; RFPs</li>
                    <li>Overview dashboard</li>
                </ul>
                <a href="/register" class="btn btn-ghost">Start free</a>
            </article>

            <article class="plan featured">
                <span class="plan-badge">Accounts</span>
                <div class="plan-name">Standard</div>
                <div class="plan-price">€{{ $std }}<span>/mo</span></div>
                <p class="plan-blurb">Sole-trader Tax &amp; VAT — no profession tools.</p>
                <ul>
                    <li>Unlimited clients</li>
                    <li>Tax &amp; VAT report</li>
                    <li>Expenses &amp; accountant pack</li>
                    <li>Custom branding</li>
                </ul>
                <a href="/register" class="btn btn-primary">Choose Standard</a>
            </article>

            <article class="plan">
                <span class="plan-badge">Profession</span>
                <div class="plan-name">Practice</div>
                <div class="plan-price">€{{ $prac }}<span>/mo</span></div>
                <p class="plan-blurb">Niche clinical / project tools + Free invoicing.</p>
                <ul>
                    <li>Free financial layer (5 clients)</li>
                    <li>Patients, DMS, or projects</li>
                    <li>Stampables &amp; certificates</li>
                    <li>Upgrade path to Full Pro</li>
                </ul>
                <a href="#profession" class="btn btn-ghost">See Practice</a>
            </article>

            <article class="plan bargain">
                <span class="plan-badge">Best value</span>
                <div class="plan-name">Full Pro</div>
                <div class="plan-price">€{{ $pro }}<span>/mo</span></div>
                <p class="plan-blurb">Standard accounts + your profession package.</p>
                <p class="save-note">Save €{{ $save }}/mo vs €{{ $std }} + €{{ $prac }}</p>
                <ul>
                    <li>Everything in Standard</li>
                    <li>Everything in Practice</li>
                    <li>Unlimited clients</li>
                    <li>One plan for the whole desk</li>
                </ul>
                <a href="#profession" class="btn btn-primary">Choose Full Pro</a>
            </article>
        </div>

        <div class="path">
            <h2 class="section-title" style="font-size: 1.15rem;">Typical paths</h2>
            <ol>
                <li><strong>Accounts first:</strong> Free → Standard → Full Pro when you need clinical/project tools.</li>
                <li><strong>Practice first:</strong> Free → Practice → Full Pro when Tax &amp; VAT becomes real.</li>
                <li><strong>All in:</strong> Straight to Full Pro if you already run both desks.</li>
            </ol>
        </div>
    </section>

    <section class="section" id="profession">
        <h2 class="section-title">Profession packages</h2>
        <p class="section-sub">Practice and Full Pro are matched to your warrant. Pick your profession to compare Practice-only vs the Full Pro bundle.</p>

        <div class="profession-tabs" role="tablist">
            <button type="button" class="active" data-tab="med">Medical</button>
            <button type="button" data-tab="arch">Architect</button>
            <button type="button" data-tab="eng">Engineer</button>
        </div>

        <div class="profession-panel active" data-panel="med">
            <div class="pair">
                <article class="plan">
                    <div class="plan-name">Practice Medical</div>
                    <div class="plan-price">€{{ $prac }}<span>/mo</span></div>
                    <p class="plan-blurb">Clinical tools + Free invoicing. No Tax &amp; VAT yet.</p>
                    <ul>
                        <li>Secure patient journals</li>
                        <li>Prescriptions &amp; referrals</li>
                        <li>Clinical stampables</li>
                        <li>5 lifetime clients on invoices</li>
                    </ul>
                    <a href="/register" class="btn btn-ghost">Select Practice Med</a>
                </article>
                <article class="plan bargain">
                    <span class="plan-badge">Bundle</span>
                    <div class="plan-name">Pro Medical</div>
                    <div class="plan-price">€{{ $pro }}<span>/mo</span></div>
                    <p class="plan-blurb">Practice tools + Standard Tax &amp; VAT.</p>
                    <p class="save-note">Save €{{ $save }}/mo vs buying both</p>
                    <ul>
                        <li>All Practice Medical tools</li>
                        <li>Unlimited clients</li>
                        <li>Tax &amp; VAT, expenses, accountant</li>
                    </ul>
                    <a href="/register" class="btn btn-primary">Select Pro Med</a>
                </article>
            </div>
        </div>

        <div class="profession-panel" data-panel="arch">
            <div class="pair">
                <article class="plan">
                    <div class="plan-name">Practice Architect</div>
                    <div class="plan-price">€{{ $prac }}<span>/mo</span></div>
                    <p class="plan-blurb">Project tools + Free invoicing. No Tax &amp; VAT yet.</p>
                    <ul>
                        <li>Architect DMS</li>
                        <li>Document stamper</li>
                        <li>Project phase tracking</li>
                        <li>5 lifetime clients on invoices</li>
                    </ul>
                    <a href="/register" class="btn btn-ghost">Select Practice Arch</a>
                </article>
                <article class="plan bargain">
                    <span class="plan-badge">Bundle</span>
                    <div class="plan-name">Pro Architect</div>
                    <div class="plan-price">€{{ $pro }}<span>/mo</span></div>
                    <p class="plan-blurb">Practice tools + Standard Tax &amp; VAT.</p>
                    <p class="save-note">Save €{{ $save }}/mo vs buying both</p>
                    <ul>
                        <li>All Practice Architect tools</li>
                        <li>Unlimited clients</li>
                        <li>Tax &amp; VAT, expenses, accountant</li>
                    </ul>
                    <a href="/register" class="btn btn-primary">Select Pro Arch</a>
                </article>
            </div>
        </div>

        <div class="profession-panel" data-panel="eng">
            <div class="pair">
                <article class="plan">
                    <div class="plan-name">Practice Engineer</div>
                    <div class="plan-price">€{{ $prac }}<span>/mo</span></div>
                    <p class="plan-blurb">Engineering tools + Free invoicing. No Tax &amp; VAT yet.</p>
                    <ul>
                        <li>Projects &amp; certificates</li>
                        <li>Technical exports</li>
                        <li>5 lifetime clients on invoices</li>
                    </ul>
                    <a href="/register" class="btn btn-ghost">Select Practice Eng</a>
                </article>
                <article class="plan bargain">
                    <span class="plan-badge">Bundle</span>
                    <div class="plan-name">Pro Engineer</div>
                    <div class="plan-price">€{{ $pro }}<span>/mo</span></div>
                    <p class="plan-blurb">Practice tools + Standard Tax &amp; VAT.</p>
                    <p class="save-note">Save €{{ $save }}/mo vs buying both</p>
                    <ul>
                        <li>All Practice Engineer tools</li>
                        <li>Unlimited clients</li>
                        <li>Tax &amp; VAT, expenses, accountant</li>
                    </ul>
                    <a href="/register" class="btn btn-primary">Select Pro Eng</a>
                </article>
            </div>
        </div>
    </section>

    <footer class="site-footer">
        PractisBase models self-employed sole traders in Malta. Sole-trader scope only — not Ltd company accounts.
    </footer>

    <script>
        (function () {
            var tabs = document.querySelectorAll('.profession-tabs button');
            var panels = document.querySelectorAll('.profession-panel');
            tabs.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var id = btn.getAttribute('data-tab');
                    tabs.forEach(function (b) { b.classList.toggle('active', b === btn); });
                    panels.forEach(function (p) {
                        p.classList.toggle('active', p.getAttribute('data-panel') === id);
                    });
                });
            });
        })();
    </script>
</body>
</html>

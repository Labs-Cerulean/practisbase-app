<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PractisBase | The sole-trader toolkit for Malta</title>
    <meta name="description" content="Accounts, VAT, and profession tools built for Maltese doctors, architects, and engineers — not generic SaaS.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&display=swap" rel="stylesheet">
    @include('partials.pwa-head')

    <style>
        :root {
            --ink: #0b1f33;
            --ink-soft: #243b53;
            --mute: #5c6f82;
            --sea: #0b7eb5;
            --sea-deep: #086690;
            --line: rgba(15, 39, 68, 0.12);
            --paper: #f3f7fb;
            --white: #ffffff;
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            color: var(--ink);
            font-family: "DM Sans", ui-sans-serif, system-ui, sans-serif;
            background: var(--paper);
            -webkit-font-smoothing: antialiased;
        }
        a { color: inherit; }

        .top {
            position: absolute;
            inset: 0 0 auto;
            z-index: 5;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.15rem clamp(1.25rem, 4vw, 3.5rem);
            max-width: 1280px;
            width: 100%;
            margin: 0 auto;
            left: 0;
            right: 0;
        }
        .top img { height: 34px; width: auto; }
        .top-brand {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            text-decoration: none;
            color: var(--ink);
        }
        .top-brand strong {
            font-family: Fraunces, Georgia, serif;
            font-size: 1.05rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }
        .top-nav {
            display: flex;
            align-items: center;
            gap: 1.1rem;
        }
        .top-nav a {
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--ink-soft);
        }
        .top-nav a:hover { color: var(--ink); }
        .top-nav a.btn-primary,
        .top-nav a.btn-primary:hover {
            color: #fff;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            padding: 0.85rem 1.25rem;
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.92rem;
            text-decoration: none;
            border: 1px solid transparent;
            cursor: pointer;
            transition: transform 0.25s ease, background 0.25s ease, box-shadow 0.25s ease;
        }
        .btn:hover { transform: translateY(-1px); }
        .btn-primary {
            background: var(--ink);
            color: #fff !important;
            box-shadow: 0 14px 30px rgba(11, 31, 51, 0.18);
        }
        .btn-primary:hover { background: #13293f; color: #fff !important; }
        .btn-ghost {
            background: rgba(255, 255, 255, 0.55);
            border-color: var(--line);
            color: var(--ink);
            backdrop-filter: blur(8px);
        }
        .btn-sea {
            background: var(--sea);
            color: #fff;
            box-shadow: 0 14px 28px rgba(11, 126, 181, 0.28);
        }
        .btn-sea:hover { background: var(--sea-deep); }

        /* —— Hero: one composition —— */
        .hero {
            position: relative;
            min-height: auto;
            display: grid;
            align-items: start;
            overflow: hidden;
            background:
                linear-gradient(180deg, rgba(243, 247, 251, 0.15) 0%, rgba(243, 247, 251, 0.72) 58%, var(--paper) 100%),
                radial-gradient(900px 520px at 78% 28%, rgba(11, 126, 181, 0.22), transparent 60%),
                radial-gradient(700px 480px at 12% 80%, rgba(11, 31, 51, 0.14), transparent 55%),
                linear-gradient(135deg, #d7e8f4 0%, #eef4f9 42%, #c9dce9 100%);
        }
        .hero-stage {
            position: absolute;
            inset: 0;
            pointer-events: none;
        }
        .hero-product {
            position: absolute;
            right: clamp(-2%, 3vw, 8%);
            top: 4.75rem;
            transform: perspective(1200px) rotateY(-8deg) rotateX(3deg);
            width: min(52vw, 640px);
            border-radius: 22px 0 0 22px;
            background:
                linear-gradient(160deg, rgba(255,255,255,0.97), rgba(255,255,255,0.78)),
                #fff;
            border: 1px solid rgba(255,255,255,0.7);
            border-right: 0;
            box-shadow:
                0 40px 80px rgba(11, 31, 51, 0.18),
                inset 0 1px 0 rgba(255,255,255,0.9);
            overflow: hidden;
            transform-origin: right center;
        }
        .hero-product-chrome {
            position: relative;
            display: flex;
            gap: 0.35rem;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--line);
            background: rgba(255,255,255,0.7);
        }
        .hero-product-chrome span {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: #c9d6e3;
        }
        .hero-product-body {
            position: relative;
            padding: 1.1rem 1.25rem 1.35rem;
            display: grid;
            gap: 0.7rem;
        }
        .hero-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.65rem;
        }
        .hero-block {
            border-radius: 12px;
            background: rgba(243, 247, 251, 0.95);
            border: 1px solid var(--line);
            padding: 0.85rem 0.95rem;
            min-height: 4.1rem;
        }
        .hero-block strong {
            display: block;
            font-size: 0.68rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--mute);
            margin-bottom: 0.3rem;
            font-weight: 700;
        }
        .hero-block em {
            font-style: normal;
            font-family: Fraunces, Georgia, serif;
            font-size: 1.15rem;
            font-weight: 600;
            color: var(--ink);
            line-height: 1.2;
        }
        .hero-block.accent {
            background: linear-gradient(145deg, #0b1f33, #163553);
            color: #fff;
            border-color: transparent;
        }
        .hero-block.accent strong { color: rgba(255,255,255,0.65); }
        .hero-block.accent em { color: #fff; }
        .hero-block.med {
            background: linear-gradient(145deg, #0f766e, #115e59);
            border-color: transparent;
            color: #fff;
        }
        .hero-block.med strong { color: rgba(255,255,255,0.7); }
        .hero-block.med em { color: #fff; }
        .hero-block.arch {
            background: linear-gradient(145deg, #3f6212, #365314);
            border-color: transparent;
            color: #fff;
        }
        .hero-block.arch strong { color: rgba(255,255,255,0.7); }
        .hero-block.arch em { color: #fff; }
        .hero-block.eng {
            background: linear-gradient(145deg, #0c4a6e, #075985);
            border-color: transparent;
            color: #fff;
        }
        .hero-block.eng strong { color: rgba(255,255,255,0.7); }
        .hero-block.eng em { color: #fff; }

        .hero-copy {
            position: relative;
            z-index: 2;
            max-width: 1280px;
            width: 100%;
            margin: 0 auto;
            padding: 5.75rem clamp(1.25rem, 4vw, 3.5rem) 2.75rem;
        }
        .hero-logo {
            display: block;
            width: clamp(11rem, 28vw, 18rem);
            height: auto;
            margin: 0 0 1.15rem;
            filter: drop-shadow(0 16px 36px rgba(11, 31, 51, 0.14));
        }
        .hero h1 {
            margin: 0 0 0.75rem;
            max-width: 14ch;
            font-family: Fraunces, Georgia, serif;
            font-size: clamp(1.45rem, 3.2vw, 2.15rem);
            font-weight: 500;
            line-height: 1.2;
            color: var(--ink-soft);
        }
        .hero-lead {
            margin: 0;
            max-width: 28rem;
            color: var(--mute);
            font-size: 1.05rem;
            line-height: 1.55;
        }
        .hero-cta {
            margin-top: 1.5rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.7rem;
        }

        /* —— Story sections —— */
        .story {
            max-width: 1120px;
            margin: 0 auto;
            padding: clamp(3.5rem, 8vw, 6rem) clamp(1.25rem, 4vw, 3.5rem);
        }
        .story-kicker {
            margin: 0 0 0.75rem;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--sea);
        }
        .story h2 {
            margin: 0 0 0.85rem;
            font-family: Fraunces, Georgia, serif;
            font-size: clamp(1.9rem, 4vw, 2.75rem);
            font-weight: 600;
            letter-spacing: -0.03em;
            line-height: 1.12;
            max-width: 16ch;
        }
        .story > p {
            margin: 0;
            max-width: 34rem;
            color: var(--mute);
            font-size: 1.05rem;
            line-height: 1.6;
        }

        .made-for {
            display: grid;
            gap: 0;
            margin-top: 3rem;
            border-top: 1px solid var(--line);
        }
        .made-row {
            display: grid;
            grid-template-columns: minmax(10rem, 0.85fr) 1.15fr;
            gap: 1.5rem 2.5rem;
            padding: 1.85rem 0;
            border-bottom: 1px solid var(--line);
            align-items: start;
        }
        .made-row h3 {
            margin: 0;
            font-family: Fraunces, Georgia, serif;
            font-size: clamp(1.35rem, 2.5vw, 1.7rem);
            font-weight: 600;
            letter-spacing: -0.02em;
            line-height: 1.2;
        }
        .made-row p {
            margin: 0;
            color: var(--mute);
            font-size: 1.02rem;
            line-height: 1.6;
            max-width: 38rem;
        }

        .paths {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1.5rem;
            margin-top: 2.5rem;
        }
        .path {
            padding: 0.25rem 0 0;
        }
        .path-label {
            display: block;
            margin-bottom: 0.55rem;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: var(--sea);
        }
        .path h3 {
            margin: 0 0 0.55rem;
            font-family: Fraunces, Georgia, serif;
            font-size: 1.45rem;
            font-weight: 600;
        }
        .path p {
            margin: 0 0 1rem;
            color: var(--mute);
            line-height: 1.55;
            font-size: 0.98rem;
        }
        .path ul {
            margin: 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 0.45rem;
        }
        .path li {
            position: relative;
            padding-left: 1rem;
            color: var(--ink-soft);
            font-size: 0.95rem;
            line-height: 1.45;
        }
        .path li::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0.55em;
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: var(--sea);
        }

        .community {
            margin: 0 clamp(1.25rem, 4vw, 3.5rem) clamp(3rem, 7vw, 5rem);
            padding: clamp(2.5rem, 5vw, 3.5rem) clamp(1.5rem, 4vw, 3rem);
            border-radius: 28px;
            background:
                radial-gradient(700px 280px at 100% 0%, rgba(11, 126, 181, 0.18), transparent 55%),
                linear-gradient(160deg, #0b1f33 0%, #14324d 100%);
            color: #fff;
            max-width: 1120px;
            margin-left: auto;
            margin-right: auto;
        }
        .community h2 {
            margin: 0 0 0.75rem;
            font-family: Fraunces, Georgia, serif;
            font-size: clamp(1.8rem, 3.5vw, 2.4rem);
            font-weight: 600;
            letter-spacing: -0.03em;
            max-width: 18ch;
        }
        .community p {
            margin: 0;
            max-width: 36rem;
            color: rgba(255,255,255,0.72);
            font-size: 1.05rem;
            line-height: 1.6;
        }
        .community-actions {
            margin-top: 1.75rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.7rem;
        }
        .community .btn-ghost {
            background: transparent;
            border-color: rgba(255,255,255,0.28);
            color: #fff;
        }
        .community .btn-ghost:hover { background: rgba(255,255,255,0.08); }

        .join-panel {
            display: none;
            margin-top: 1.5rem;
            max-width: 26rem;
            padding: 1rem 1.05rem;
            border-radius: 16px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.16);
        }
        .join-panel.is-open { display: block; }
        .join-panel label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.7);
            margin-bottom: 0.35rem;
        }
        .join-panel input {
            width: 100%;
            margin-bottom: 0.85rem;
            padding: 0.75rem 0.85rem;
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.2);
            background: rgba(255,255,255,0.95);
            color: var(--ink);
            font: inherit;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .join-panel .hint {
            margin: -0.4rem 0 0.85rem;
            font-size: 0.78rem;
            color: rgba(255,255,255,0.55);
            line-height: 1.4;
        }
        .join-panel .join-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.55rem;
        }

        .site-footer {
            padding: 1rem 1.5rem 2.75rem;
            text-align: center;
            color: var(--mute);
            font-size: 0.82rem;
            line-height: 1.5;
        }
        .site-footer a {
            color: var(--sea);
            font-weight: 600;
            text-decoration: none;
        }
        .footer-links {
            margin-top: 0.65rem;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        @media (max-width: 900px) {
            .hero-product {
                opacity: 0.28;
                width: min(88vw, 520px);
                right: -14%;
            }
            .hero-copy { padding-top: 5.25rem; padding-bottom: 2.25rem; }
            .hero-logo { width: clamp(9.5rem, 42vw, 14rem); }
            .made-row { grid-template-columns: 1fr; gap: 0.45rem; }
            .paths { grid-template-columns: 1fr; gap: 2rem; }
        }
        @media (max-width: 560px) {
            .hero-product { display: none; }
            .top-nav .hide-sm { display: none; }
            .top-brand strong { display: none; }
            .hero-copy { padding-top: 4.75rem; }
        }

        @media (prefers-reduced-motion: no-preference) {
            .hero-logo, .hero h1, .hero-lead, .hero-cta, .hero-product {
                animation: rise 0.85s cubic-bezier(0.22, 1, 0.36, 1) both;
            }
            .hero h1 { animation-delay: 0.08s; }
            .hero-lead { animation-delay: 0.14s; }
            .hero-cta { animation-delay: 0.2s; }
            .hero-product { animation-delay: 0.16s; animation-name: float-in; }
            .made-row, .path, .community {
                animation: rise 0.7s ease both;
                animation-timeline: view();
                animation-range: entry 10% cover 30%;
            }
        }
        @keyframes rise {
            from { opacity: 0; transform: translateY(18px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes float-in {
            from { opacity: 0; transform: perspective(1200px) rotateY(-8deg) rotateX(3deg) translateX(40px); }
            to { opacity: 1; transform: perspective(1200px) rotateY(-8deg) rotateX(3deg) translateX(0); }
        }
    </style>
</head>
<body>
    <header class="top">
        <a class="top-brand" href="/">
            <img src="/images/logo.png" alt="">
            <strong>PractisBase</strong>
        </a>
        <nav class="top-nav" aria-label="Primary">
            <a class="hide-sm" href="#what">Product</a>
            <a class="hide-sm" href="/pricing">Pricing</a>
            <a href="/login">Sign in</a>
            <a href="/register" class="btn btn-primary" style="padding: 0.65rem 1.05rem;">Register</a>
        </nav>
    </header>

    <section class="hero" aria-label="Introduction">
        <div class="hero-stage" aria-hidden="true">
            <div class="hero-product">
                <div class="hero-product-chrome">
                    <span></span><span></span><span></span>
                </div>
                <div class="hero-product-body">
                    <div class="hero-row">
                        <div class="hero-block med">
                            <strong>Medical</strong>
                            <em>Journals · Rx · referrals</em>
                        </div>
                        <div class="hero-block arch">
                            <strong>Architect</strong>
                            <em>BCA · declarations</em>
                        </div>
                    </div>
                    <div class="hero-row">
                        <div class="hero-block eng">
                            <strong>Engineer</strong>
                            <em>Equipment · certificates</em>
                        </div>
                        <div class="hero-block accent">
                            <strong>Accounts</strong>
                            <em>Tax · VAT · SSC</em>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="hero-copy">
            <img class="hero-logo" src="/images/logo.png" alt="PractisBase">
            <h1>Made for Maltese practice.</h1>
            <p class="hero-lead">The sole-trader toolkit that understands local tax, VAT, and the way doctors, architects, and engineers actually work.</p>
            <div class="hero-cta">
                <a href="#what" class="btn btn-sea">See what it does</a>
                <a href="/register" class="btn btn-ghost">Register</a>
            </div>
        </div>
    </section>

    <section class="story" id="what">
        <p class="story-kicker">What it is</p>
        <h2>One desk for your books and your profession.</h2>
        <p>Generic software ignores Maltese parameters. PractisBase is built around Article 10 / 11, provisional tax, SSC, and the documents your warrant actually needs.</p>

        <div class="made-for">
            <div class="made-row">
                <h3>Made for your books</h3>
                <p>Raise invoices and RFPs, track expenses, and open a live fiscal report that mirrors how Malta taxes sole traders. Auto complete your Tax forms (TA22) and export to your accountant, with year locking when you close a year.</p>
            </div>
            <div class="made-row">
                <h3>Made for your practice</h3>
                <p>Profession tools sit beside the ledger. Doctors get secured and private patient journals, prescriptions, certificates, and referrals. Architects get condition reports, BCA templates, and declarations. Engineers get equipment certificate management alongside field certificates and project files. Every practice gets a document manager.</p>
            </div>
            <div class="made-row">
                <h3>Made for calm compliance</h3>
                <p>Audit friendly breakdowns instead of black box totals. Accountant packs when you need to hand work over.</p>
            </div>
            <div class="made-row">
                <h3>Made with the community</h3>
                <p>Request features, send feedback, and watch the toolkit upgrade. Built by Maltese professionals, for Maltese professionals, not a foreign template with Malta painted on.</p>
            </div>
        </div>
    </section>

    <section class="story" id="professions" style="padding-top: 0;">
        <p class="story-kicker">Profession paths</p>
        <h2>Tools that match the warrant.</h2>
        <p>Start with accounts, or go straight into the practice desk for your field. Full Pro keeps both on one plan.</p>

        <div class="paths">
            <article class="path">
                <span class="path-label">Medical</span>
                <h3>Clinical desk</h3>
                <p>Secured and private patient work under your recovery key.</p>
                <ul>
                    <li>Secured and private patient journals</li>
                    <li>Prescriptions with issue codes</li>
                    <li>Referrals and medical certificates</li>
                    <li>Document manager and trusted devices</li>
                </ul>
            </article>
            <article class="path">
                <span class="path-label">Architect</span>
                <h3>Studio desk</h3>
                <p>Project files, BCA templates, and declarations in one place.</p>
                <ul>
                    <li>Condition reports and method statements</li>
                    <li>BCA templates and declarations</li>
                    <li>Phase tracking and document manager</li>
                    <li>Branded exports for the studio</li>
                </ul>
            </article>
            <article class="path">
                <span class="path-label">Engineer</span>
                <h3>Technical desk</h3>
                <p>Field work tied back to the client and the PA.</p>
                <ul>
                    <li>Client → project → PA workspace</li>
                    <li>Equipment certificate management</li>
                    <li>Field certificates and specialised reports</li>
                    <li>Document manager and site photos</li>
                </ul>
            </article>
        </div>
    </section>

    <section class="community" id="join">
        <h2>Register and start building.</h2>
        <p>Register free. Add a Founding code at signup if you have one.</p>
        <div class="community-actions">
            <a href="/register" class="btn btn-sea">Register</a>
            <button type="button" class="btn btn-ghost" id="openJoinForm">Have a promo code?</button>
            <a href="/pricing" class="btn btn-ghost">See pricing</a>
        </div>
        <form class="join-panel" id="heroJoinForm" method="GET" action="/register" aria-label="Register with promo or referral code">
            <label for="landingPromoCode">Access / promo code</label>
            <input id="landingPromoCode" type="text" name="promo_code" maxlength="40" placeholder="e.g. FOUNDING-50" value="{{ request('promo_code') }}" autocomplete="off">
            <p class="hint">Profession access codes unlock Full Pro. Cohort promos apply free months or discounts.</p>
            <label for="landingRefCode">Referral code (optional)</label>
            <input id="landingRefCode" type="text" name="ref" maxlength="40" placeholder="Friend's code" value="{{ request('ref') }}" autocomplete="off">
            <div class="join-actions">
                <button type="submit" class="btn btn-sea">Continue to register</button>
                <button type="button" class="btn btn-ghost" id="closeJoinForm">Cancel</button>
            </div>
        </form>
    </section>

    <footer class="site-footer">
        For Maltese sole traders, not Ltd companies.
        <div class="footer-links">
            <a href="/pricing">Pricing</a>
            <a href="/privacy">Privacy Policy</a>
            <a href="/msa">Master Service Agreement</a>
        </div>
    </footer>

    <script>
        (function () {
            var form = document.getElementById('heroJoinForm');
            var openBtn = document.getElementById('openJoinForm');
            var closeBtn = document.getElementById('closeJoinForm');
            if (!form || !openBtn) return;

            function openForm() {
                form.classList.add('is-open');
                var promo = document.getElementById('landingPromoCode');
                if (promo) promo.focus();
            }
            function closeForm() {
                form.classList.remove('is-open');
            }

            openBtn.addEventListener('click', openForm);
            if (closeBtn) closeBtn.addEventListener('click', closeForm);

            var params = new URLSearchParams(window.location.search);
            if (params.get('promo_code') || params.get('ref')) {
                openForm();
                var join = document.getElementById('join');
                if (join) join.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        })();
    </script>
</body>
</html>

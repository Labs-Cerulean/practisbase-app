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
        
        .pricing-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.5rem; }
        .pricing-card { background: var(--bg-surface); border-radius: var(--radius-lg); padding: 1.75rem; box-shadow: var(--shadow-md); border: 1px solid var(--border-light); display: flex; flex-direction: column; }
        .pricing-card.popular { border: 2px solid var(--primary-cerulean); transform: scale(1.02); position: relative; }
        .popular-badge { position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: var(--primary-cerulean); color: white; padding: 0.2rem 0.75rem; border-radius: 20px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; }
        
        .tier-name { font-size: 1.15rem; font-weight: 700; color: var(--primary-navy); margin-bottom: 0.25rem; }
        .tier-price { font-size: 2.25rem; font-weight: 700; color: var(--primary-navy); margin-bottom: 0.15rem; }
        .tier-price span { font-size: 0.9rem; color: var(--text-muted); font-weight: 400; }
        .tier-vat { font-size: 0.78rem; font-weight: 700; color: var(--text-muted); margin: 0 0 0.5rem; }
        .tier-blurb { font-size: 0.8rem; color: var(--text-muted); margin: 0 0 1rem; line-height: 1.4; min-height: 2.4em; }
        .vat-banner { background: #eff6ff; color: #1e40af; text-align: center; padding: 0.75rem 1rem; border-radius: var(--radius-md); font-size: 0.85rem; font-weight: 600; margin-bottom: 1.25rem; border: 1px solid #bfdbfe; line-height: 1.4; }
        
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
        
        .dev-banner { background: #fef3c7; color: #b45309; text-align: center; padding: 0.75rem; border-radius: var(--radius-md); font-size: 0.85rem; font-weight: 600; margin-bottom: 2rem; border: 1px solid #fde68a; }
        .section-label { font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--text-muted); margin: 2rem 0 0.85rem; }
    </style>
</head>
<body>
@php
    $allowed = $allowedTiers ?? [];
    $practiceTier = collect($allowed)->first(fn ($t) => str_starts_with($t, 'practice-'));
    $proTier = collect($allowed)->first(fn ($t) => str_starts_with($t, 'pro-'));
    $vatSuffix = \App\Support\TierPolicy::priceVatSuffix();
@endphp

    <main class="pricing-container">
        <div class="pricing-header">
            <div class="step-indicator">Step 3 of 3</div>
            <h2 style="color: var(--primary-navy); font-size: 2.25rem; margin-bottom: 0.5rem;">Select Your Tier</h2>
            <p style="color: var(--text-muted); font-size: 1rem;">Start with practice tools or accounts — upgrade into the full system later.</p>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.35rem;">[TESTING MODE: No credit card required.]</p>
        </div>

        <div class="vat-banner">{{ \App\Support\TierPolicy::pricingVatDisclaimer() }} Paid tiers show ex-VAT list prices.</div>

        <div class="dev-banner">
            DEV BYPASS ACTIVE: Selecting a paid tier will instantly upgrade your account for testing. Stripe is bypassed.
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
                <form action="/onboarding/plans-submit" method="POST" style="margin-top: auto;">
                    @csrf
                    <input type="hidden" name="tier" value="free">
                    <button type="submit" class="btn-tier btn-outline">Select Free</button>
                </form>
            </div>

            <div class="pricing-card popular">
                <div class="popular-badge">Accounts</div>
                <div class="tier-name">Standard</div>
                <div class="tier-price">€{{ \App\Support\TierPolicy::PRICE_STANDARD }}<span>/mo</span></div>
                <p class="tier-vat">{{ $vatSuffix }}</p>
                <p class="tier-blurb">Sole-trader Tax &amp; VAT — no profession tools.</p>
                <ul class="feature-list">
                    <li><strong>Unlimited Clients</strong></li>
                    <li>Tax &amp; VAT report</li>
                    <li>Expenses &amp; receipts</li>
                    <li>Accountant pack</li>
                    <li>Custom branding</li>
                </ul>
                <form action="/onboarding/plans-submit" method="POST" style="margin-top: auto;">
                    @csrf
                    <input type="hidden" name="tier" value="standard">
                    <button type="submit" class="btn-tier btn-solid">Select Standard</button>
                </form>
            </div>
        </div>

        @if($practiceTier || $proTier)
            <div class="section-label">Your profession</div>
            <div class="pricing-grid">
                @if($practiceTier)
                    @php
                        $pkg = \App\Support\TierPolicy::packageForTier($practiceTier);
                        $accent = $pkg === 'med' ? '#059669' : ($pkg === 'eng' ? '#d97706' : 'var(--primary-navy)');
                    @endphp
                    <div class="pricing-card" style="border-top: 4px solid {{ $accent }};">
                        <div class="tier-name" style="color: {{ $accent }};">{{ \App\Support\TierPolicy::label($practiceTier) }}</div>
                        <div class="tier-price">€{{ \App\Support\TierPolicy::PRICE_PRACTICE }}<span>/mo</span></div>
                        <p class="tier-blurb">Profession tools + Free invoicing. Add Tax &amp; VAT later via Full Pro.</p>
                        <ul class="feature-list">
                            <li>Free financial layer (5 clients + invoices)</li>
                            <li class="group-header" style="color: {{ $accent }};">Practice tools:</li>
                            @if($pkg === 'med')
                                <li>Secure patient journals</li>
                                <li>Prescriptions &amp; referrals</li>
                                <li>Clinical stampables</li>
                            @elseif($pkg === 'arch')
                                <li>Architect DMS</li>
                                <li>Document stamper</li>
                                <li>Project phase tracking</li>
                            @else
                                <li>Engineering projects</li>
                                <li>Certificates</li>
                                <li>Technical exports</li>
                            @endif
                        </ul>
                        <form action="/onboarding/plans-submit" method="POST" style="margin-top: auto;">
                            @csrf
                            <input type="hidden" name="tier" value="{{ $practiceTier }}">
                            <button type="submit" class="btn-tier btn-outline" style="border-color: {{ $accent }}; color: {{ $accent }};">Select Practice</button>
                        </form>
                    </div>
                @endif

                @if($proTier)
                    @php
                        $pkg = \App\Support\TierPolicy::packageForTier($proTier);
                        $accent = $pkg === 'med' ? '#059669' : ($pkg === 'eng' ? '#d97706' : 'var(--primary-navy)');
                    @endphp
                    <div class="pricing-card" style="border-top: 4px solid {{ $accent }};">
                        <div class="tier-name" style="color: {{ $accent }};">{{ \App\Support\TierPolicy::label($proTier) }}</div>
                        <div class="tier-price">€{{ \App\Support\TierPolicy::PRICE_PRO }}<span>/mo</span></div>
                        <p class="tier-blurb">Everything — Standard accounts plus practice tools. Save €{{ \App\Support\TierPolicy::bundleSavingsEuro() }}/mo vs buying both.</p>
                        <ul class="feature-list">
                            <li><strong>All Standard financial features</strong></li>
                            <li>Unlimited clients</li>
                            <li class="group-header" style="color: {{ $accent }};">Plus practice tools:</li>
                            @if($pkg === 'med')
                                <li>Patients, prescriptions, referrals</li>
                            @elseif($pkg === 'arch')
                                <li>DMS, stamper, projects</li>
                            @else
                                <li>Projects &amp; certificates</li>
                            @endif
                        </ul>
                        <form action="/onboarding/plans-submit" method="POST" style="margin-top: auto;">
                            @csrf
                            <input type="hidden" name="tier" value="{{ $proTier }}">
                            <button type="submit" class="btn-tier btn-outline" style="border-color: {{ $accent }}; color: {{ $accent }};">Select Full Pro</button>
                        </form>
                    </div>
                @endif
            </div>
        @endif
    </main>

</body>
</html>

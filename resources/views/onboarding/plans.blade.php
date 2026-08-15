<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Choose Plan | PractisBase</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
    <style>
        * { box-sizing: border-box; }
        body { background-color: var(--bg-canvas); overflow-x: hidden; margin: 0; }
        .pricing-container { max-width: 1100px; margin: 0 auto; padding: 1.25rem 1rem 2.5rem; }
        @media (min-width: 640px) {
            .pricing-container { padding: 2rem 1.5rem 3rem; }
        }
        .pricing-header { text-align: center; margin-bottom: 1.5rem; }
        .step-indicator { font-size: 0.8rem; color: var(--primary-cerulean); font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.4rem; }
        .resume-note { color: var(--text-muted); font-size: 0.8rem; margin-top: 0.45rem; line-height: 1.4; }

        .pricing-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.15rem; }
        .pricing-card { background: var(--bg-surface); border-radius: var(--radius-lg); padding: 1.5rem; box-shadow: var(--shadow-md); border: 1px solid var(--border-light); display: flex; flex-direction: column; }
        .pricing-card.popular { border: 2px solid var(--primary-cerulean); position: relative; }
        .popular-badge { position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: var(--primary-cerulean); color: white; padding: 0.2rem 0.75rem; border-radius: 20px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; }

        .tier-name { font-size: 1.1rem; font-weight: 700; color: var(--primary-navy); margin-bottom: 0.25rem; }
        .tier-price { font-size: 2.1rem; font-weight: 700; color: var(--primary-navy); margin-bottom: 0.1rem; }
        .tier-price span { font-size: 0.85rem; color: var(--text-muted); font-weight: 400; }
        .tier-vat { font-size: 0.78rem; font-weight: 600; color: var(--text-muted); margin: 0 0 0.15rem; }
        .tier-inc { font-size: 0.82rem; font-weight: 700; color: var(--primary-navy); margin: 0 0 0.65rem; }
        .tier-blurb { font-size: 0.82rem; color: var(--text-muted); margin: 0 0 1rem; line-height: 1.4; min-height: 2.4em; }

        .feature-list { list-style: none; padding: 0; margin-bottom: 1.25rem; flex-grow: 1; font-size: 0.88rem; }
        .feature-list li { margin-bottom: 0.55rem; color: var(--text-main); display: flex; align-items: flex-start; gap: 0.5rem; line-height: 1.3; }
        .feature-list li::before { content: '✓'; color: var(--primary-cerulean); font-weight: bold; }
        .feature-list li.group-header { margin-top: 0.85rem; margin-bottom: 0.3rem; color: var(--primary-cerulean); font-weight: 700; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .feature-list li.group-header::before { display: none; }

        .btn-tier { width: 100%; padding: 0.7rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.92rem; cursor: pointer; text-align: center; border: none; }
        .btn-outline { background: transparent; border: 1px solid var(--primary-cerulean); color: var(--primary-cerulean); }
        .btn-solid { background: var(--primary-cerulean); color: white; border: 1px solid var(--primary-cerulean); }
        .btn-muted { background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1; cursor: not-allowed; }

        .section-label { font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--text-muted); margin: 1.75rem 0 0.85rem; }
        .promo-banner {
            background: #eff6ff;
            color: #1e3a8a;
            text-align: center;
            padding: 0.85rem 1rem;
            border-radius: var(--radius-md);
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
            border: 1px solid #bfdbfe;
            line-height: 1.45;
        }
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 0.85rem 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1rem;
            font-size: 0.85rem;
        }
    </style>
    @include('partials.pwa-head')
</head>
<body>
@php
    $allowed = $allowedTiers ?? [];
    $practiceTier = collect($allowed)->first(fn ($t) => str_starts_with($t, 'practice-'));
    $proTier = collect($allowed)->first(fn ($t) => str_starts_with($t, 'pro-'));
    $std = \App\Support\TierPolicy::PRICE_STANDARD;
    $prac = \App\Support\TierPolicy::PRICE_PRACTICE;
    $pro = \App\Support\TierPolicy::PRICE_PRO;
    $vatSuffix = \App\Support\TierPolicy::priceVatSuffix();
    $canPaid = $user->canActivatePaidTierWithoutStripe();
@endphp

    <main class="pricing-container">
        <div class="pricing-header">
            <div class="step-indicator">Step 3 of 3</div>
            <h2 style="color: var(--primary-navy); font-size: clamp(1.5rem, 4vw, 2.1rem); margin: 0 0 0.4rem;">Select your plan</h2>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">Start with profession tools or accounts. Upgrade into the full system later.</p>
            <p class="resume-note">Your account is already saved. If you leave, sign in again to finish this step.</p>
        </div>

        @if ($errors->any())
            <div class="alert-error">
                @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif

        @if($canPaid && $user->trial_ends_at)
            <div class="promo-banner">
                Founding access until {{ $user->trial_ends_at->format('d M Y') }}. Choose a plan.
            </div>
        @elseif($canPaid)
            <div class="promo-banner">
                Founding access. Choose a plan.
            </div>
        @else
            <div class="promo-banner">
                Start Free, or use a Founding code at signup. Card billing soon.
            </div>
        @endif

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
                <div class="tier-price">€{{ $std }}<span>/mo</span></div>
                <p class="tier-vat">{{ $vatSuffix }}</p>
                <p class="tier-inc">€{{ \App\Support\TierPolicy::priceIncludingVat($std) }}/mo inc VAT</p>
                <p class="tier-blurb">Sole trader Tax &amp; VAT. No profession tools.</p>
                <ul class="feature-list">
                    <li><strong>Unlimited Clients</strong></li>
                    <li>Tax &amp; VAT report</li>
                    <li>Expenses &amp; receipts</li>
                    <li>Accountant pack</li>
                    <li>Custom branding</li>
                    <li>Document Stamper (PDF)</li>
                </ul>
                @if($canPaid)
                    <form action="/onboarding/plans-submit" method="POST" style="margin-top: auto;">
                        @csrf
                        <input type="hidden" name="tier" value="standard">
                        <button type="submit" class="btn-tier btn-solid">Select Standard</button>
                    </form>
                @else
                    <button type="button" class="btn-tier btn-muted" disabled style="margin-top: auto;">Needs promo or billing</button>
                @endif
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
                        <div class="tier-price">€{{ $prac }}<span>/mo</span></div>
                        <p class="tier-vat">{{ $vatSuffix }}</p>
                        <p class="tier-inc">€{{ \App\Support\TierPolicy::priceIncludingVat($prac) }}/mo inc VAT</p>
                        <p class="tier-blurb">Profession tools + Free invoicing. Add Tax &amp; VAT later via Full Pro.</p>
                        <ul class="feature-list">
                            <li>Free financial layer (5 clients + invoices)</li>
                            <li class="group-header" style="color: {{ $accent }};">Profession tools:</li>
                            @if($pkg === 'med')
                                <li>Secure patient journals</li>
                                <li>Prescriptions &amp; referrals</li>
                                <li>Clinical stampables</li>
                                <li>Document Stamper (PDF)</li>
                            @elseif($pkg === 'arch')
                                <li>Condition reports &amp; method statements</li>
                                <li>BCA catalog + Architect DMS</li>
                                <li>Document Stamper + project phases</li>
                            @else
                                <li>Field certificates &amp; specialised reports</li>
                                <li>Client → Project → PA hierarchy</li>
                                <li>Equipment certificate management</li>
                                <li>Document Stamper (PDF)</li>
                            @endif
                        </ul>
                        @if($canPaid)
                            <form action="/onboarding/plans-submit" method="POST" style="margin-top: auto;">
                                @csrf
                                <input type="hidden" name="tier" value="{{ $practiceTier }}">
                                <button type="submit" class="btn-tier btn-outline" style="border-color: {{ $accent }}; color: {{ $accent }};">Select profession plan</button>
                            </form>
                        @else
                            <button type="button" class="btn-tier btn-muted" disabled style="margin-top: auto;">Needs promo or billing</button>
                        @endif
                    </div>
                @endif

                @if($proTier)
                    @php
                        $pkg = \App\Support\TierPolicy::packageForTier($proTier);
                        $accent = $pkg === 'med' ? '#059669' : ($pkg === 'eng' ? '#d97706' : 'var(--primary-navy)');
                    @endphp
                    <div class="pricing-card" style="border-top: 4px solid {{ $accent }};">
                        <div class="tier-name" style="color: {{ $accent }};">{{ \App\Support\TierPolicy::label($proTier) }}</div>
                        <div class="tier-price">€{{ $pro }}<span>/mo</span></div>
                        <p class="tier-vat">{{ $vatSuffix }}</p>
                        <p class="tier-inc">€{{ \App\Support\TierPolicy::priceIncludingVat($pro) }}/mo inc VAT</p>
                        <p class="tier-blurb">Standard accounts plus profession tools. Save €{{ \App\Support\TierPolicy::bundleSavingsEuro() }}/mo vs buying both.</p>
                        <ul class="feature-list">
                            <li><strong>All Standard financial features</strong></li>
                            <li>Unlimited clients</li>
                            <li class="group-header" style="color: {{ $accent }};">Plus profession tools:</li>
                            @if($pkg === 'med')
                                <li>Patients, prescriptions, referrals</li>
                            @elseif($pkg === 'arch')
                                <li>Condition reports, method statements, DMS</li>
                            @else
                                <li>Field certificates, reports &amp; projects</li>
                            @endif
                        </ul>
                        @if($canPaid)
                            <form action="/onboarding/plans-submit" method="POST" style="margin-top: auto;">
                                @csrf
                                <input type="hidden" name="tier" value="{{ $proTier }}">
                                <button type="submit" class="btn-tier btn-outline" style="border-color: {{ $accent }}; color: {{ $accent }};">Select Full Pro</button>
                            </form>
                        @else
                            <button type="button" class="btn-tier btn-muted" disabled style="margin-top: auto;">Needs promo or billing</button>
                        @endif
                    </div>
                @endif
            </div>
        @endif
    </main>

</body>
</html>

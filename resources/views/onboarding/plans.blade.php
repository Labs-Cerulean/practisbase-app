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
        .billing-vat {
            background: white;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            padding: 1rem 1.1rem;
            margin-bottom: 1.25rem;
        }
        .billing-vat label { display: flex; gap: 0.55rem; align-items: flex-start; font-size: 0.88rem; line-height: 1.4; cursor: pointer; color: var(--text-main); }
        .billing-vat input[type="checkbox"] { margin-top: 0.15rem; }
        .billing-vat .vat-field { margin-top: 0.75rem; }
        .billing-vat .vat-field span { display: block; font-weight: 600; font-size: 0.82rem; margin-bottom: 0.35rem; }
        .billing-vat input[type="text"] { width: 100%; max-width: 280px; padding: 0.7rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); }
        .billing-vat p { margin: 0.7rem 0 0; font-size: 0.78rem; color: var(--text-muted); line-height: 1.4; }
    </style>
    @include('partials.pwa-head')
</head>
<body>
    @include('partials.staging-banner')
@php
    $allowed = $allowedTiers ?? [];
    $practiceTier = collect($allowed)->first(fn ($t) => str_starts_with($t, 'practice-'));
    $proTier = collect($allowed)->first(fn ($t) => str_starts_with($t, 'pro-'));
    $std = \App\Support\TierPolicy::PRICE_STANDARD;
    $prac = \App\Support\TierPolicy::PRICE_PRACTICE;
    $pro = \App\Support\TierPolicy::PRICE_PRO;
    $vatSuffix = \App\Support\TierPolicy::priceVatSuffix();
    $promoUntil = $user->trial_ends_at && $user->trial_ends_at->gt(now()->addDays(14));
@endphp

    <main class="pricing-container">
        <div class="pricing-header">
            <div class="step-indicator">Step 2 of 2</div>
            <h2 style="color: var(--primary-navy); font-size: clamp(1.5rem, 4vw, 2.1rem); margin: 0;">Choose a plan</h2>
        </div>

        @if ($errors->any())
            <div class="alert-error">
                @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif

        @if($promoUntil)
            <div class="promo-banner">Card required. Billing starts {{ $user->trial_ends_at->timezone('Europe/Malta')->format('j M Y') }}.</div>
        @else
            <div class="promo-banner">14-day trial. Card required. Billing starts when the trial ends.</div>
        @endif

        @if(\App\Support\Deployment::isStaging())
            <div class="promo-banner">
                @if(\App\Support\StripeBilling::sandboxLinked())
                    Stripe sandbox is linked to this staging site. Test card 4242 4242 4242 4242. A real card is refused.
                @else
                    This staging site is waiting for the Stripe sandbox secret key.
                @endif
            </div>
        @endif

        @php
            $includeBillingVat = (string) old('include_billing_vat', $user->include_billing_vat ? '1' : '0') === '1';
            $billingVatNumber = old('billing_vat_number', $user->billing_vat_number ?: $user->vat_number);
        @endphp
        <div class="billing-vat">
            <label for="include-billing-vat">
                <input type="checkbox" id="include-billing-vat" value="1" {{ $includeBillingVat ? 'checked' : '' }}>
                <span>Include my VAT number on the Cerulean Labs Limited invoice and receipt.</span>
            </label>
            <div class="vat-field" id="billing-vat-field" style="{{ $includeBillingVat ? '' : 'display: none;' }}">
                <span>VAT number</span>
                <input type="text" id="billing-vat-number" value="{{ $billingVatNumber }}" maxlength="32" autocomplete="off" placeholder="MT12345678">
            </div>
            <p>Invoices and receipts are issued by Cerulean Labs Limited. Leave this unticked when you do not want a VAT number printed.</p>
        </div>

        @if($practiceTier || $proTier)
            <div class="section-label">Desk</div>
            <div class="pricing-grid">
                @if($practiceTier)
                    @php
                        $pkg = \App\Support\TierPolicy::packageForTier($practiceTier);
                        $accent = $pkg === 'med' ? '#059669' : ($pkg === 'eng' ? '#d97706' : 'var(--primary-navy)');
                    @endphp
                    <div class="pricing-card popular" style="border-top: 4px solid {{ $accent }};">
                        <div class="popular-badge" style="background: {{ $accent }};">Desk</div>
                        <div class="tier-name" style="color: {{ $accent }};">{{ \App\Support\TierPolicy::label($practiceTier) }}</div>
                        <div class="tier-price">€{{ $prac }}<span>/mo</span></div>
                        <p class="tier-vat">{{ $vatSuffix }}</p>
                        <p class="tier-inc">€{{ \App\Support\TierPolicy::priceIncludingVat($prac) }}/mo inc VAT</p>
                        <p class="tier-blurb">Profession tools.</p>
                        <ul class="feature-list">
                            <li>Invoices, up to 5 clients</li>
                            @if($pkg === 'med')
                                <li>Private journals, prescriptions, referrals</li>
                                <li>Clinical stampables</li>
                            @elseif($pkg === 'arch')
                                <li>Condition reports and method statements</li>
                                <li>BCA templates and project files</li>
                            @else
                                <li>Field certificates and specialised reports</li>
                                <li>Equipment certificates and project files</li>
                            @endif
                        </ul>
                        <form action="/onboarding/plans-submit" method="POST" style="margin-top: auto;">
                            @csrf
                            <input type="hidden" name="tier" value="{{ $practiceTier }}">
                            <button type="submit" class="btn-tier btn-outline" style="border-color: {{ $accent }}; color: {{ $accent }};">Start free trial</button>
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
                        <div class="tier-price">€{{ $pro }}<span>/mo</span></div>
                        <p class="tier-vat">{{ $vatSuffix }}</p>
                        <p class="tier-inc">€{{ \App\Support\TierPolicy::priceIncludingVat($pro) }}/mo inc VAT</p>
                        <p class="tier-blurb">Profession tools and books.</p>
                        <ul class="feature-list">
                            <li>Unlimited clients, expenses, tax and VAT</li>
                            @if($pkg === 'med')
                                <li>Patients, prescriptions, referrals</li>
                            @elseif($pkg === 'arch')
                                <li>Condition reports, method statements, project files</li>
                            @else
                                <li>Field certificates, reports, and projects</li>
                            @endif
                        </ul>
                        <form action="/onboarding/plans-submit" method="POST" style="margin-top: auto;">
                            @csrf
                            <input type="hidden" name="tier" value="{{ $proTier }}">
                            <button type="submit" class="btn-tier btn-outline" style="border-color: {{ $accent }}; color: {{ $accent }};">Start free trial</button>
                        </form>
                    </div>
                @endif
            </div>
        @endif

        <div class="section-label">Books</div>
        <div class="pricing-grid">
            <div class="pricing-card">
                <div class="tier-name">Free</div>
                <div class="tier-price">€0<span>/mo</span></div>
                <p class="tier-blurb">Invoices.</p>
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

            <div class="pricing-card">
                <div class="tier-name">Standard</div>
                <div class="tier-price">€{{ $std }}<span>/mo</span></div>
                <p class="tier-vat">{{ $vatSuffix }}</p>
                <p class="tier-inc">€{{ \App\Support\TierPolicy::priceIncludingVat($std) }}/mo inc VAT</p>
                <p class="tier-blurb">Tax and VAT.</p>
                <ul class="feature-list">
                    <li><strong>Unlimited Clients</strong></li>
                    <li>Tax &amp; VAT report</li>
                    <li>Expenses &amp; receipts</li>
                    <li>Accountant pack</li>
                    <li>Custom branding</li>
                    <li>Document Stamper (PDF)</li>
                </ul>
                <form action="/onboarding/plans-submit" method="POST" style="margin-top: auto;">
                    @csrf
                    <input type="hidden" name="tier" value="standard">
                    <button type="submit" class="btn-tier btn-solid">Start free trial</button>
                </form>
            </div>
        </div>
    </main>
    <script>
        (function () {
            var box = document.getElementById('include-billing-vat');
            var field = document.getElementById('billing-vat-field');
            var number = document.getElementById('billing-vat-number');
            if (box && field) {
                box.addEventListener('change', function () {
                    field.style.display = box.checked ? '' : 'none';
                });
            }
            document.querySelectorAll('form[action="/onboarding/plans-submit"]').forEach(function (form) {
                form.addEventListener('submit', function () {
                    function hidden(name, value) {
                        var input = form.querySelector('input[type="hidden"][name="' + name + '"]');
                        if (!input) {
                            input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = name;
                            form.appendChild(input);
                        }
                        input.value = value;
                    }
                    hidden('include_billing_vat', box && box.checked ? '1' : '0');
                    hidden('billing_vat_number', number ? number.value : '');
                });
            });
        })();
    </script>

</body>
</html>

@extends('layouts.app')

@section('page_title', 'Content studio')

@section('content')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,500;9..40,600;9..40,700&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap" rel="stylesheet">

    <style>
        .cs-wrap { max-width: 1100px; margin: 0 auto; }
        .cs-hero {
            display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap;
            align-items: flex-start; margin-bottom: 1.25rem;
        }
        .cs-hero h1 { margin: 0 0 0.35rem; font-size: 1.5rem; color: var(--primary-navy); }
        .cs-hero p { margin: 0; color: var(--text-muted); font-size: 0.95rem; line-height: 1.45; max-width: 38rem; }
        .cs-chip-row { display: flex; flex-wrap: wrap; gap: 0.45rem; margin: 1rem 0 1.5rem; }
        .cs-chip {
            border: 1px solid var(--border-light); background: white; color: var(--primary-navy);
            border-radius: 999px; padding: 0.4rem 0.85rem; font-size: 0.8rem; font-weight: 600;
            cursor: pointer; text-decoration: none;
        }
        .cs-chip:hover, .cs-chip.is-active { background: var(--primary-navy); color: #fff; border-color: var(--primary-navy); }
        .cs-pack {
            background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm); margin-bottom: 1.75rem; overflow: hidden;
        }
        .cs-pack-head {
            display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap;
            align-items: center; padding: 1rem 1.25rem; border-bottom: 1px solid var(--border-light);
            background: #f8fafc;
        }
        .cs-pack-head h2 { margin: 0; font-size: 1.05rem; color: var(--primary-navy); }
        .cs-meta { font-size: 0.75rem; color: var(--text-muted); font-weight: 600; letter-spacing: 0.03em; text-transform: uppercase; }
        .cs-ready {
            display: inline-block; background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0;
            font-size: 0.72rem; font-weight: 700; padding: 0.25rem 0.55rem; border-radius: 999px;
        }
        .cs-body {
            display: grid; grid-template-columns: minmax(280px, 420px) 1fr; gap: 0;
        }
        @media (max-width: 860px) {
            .cs-body { grid-template-columns: 1fr; }
        }
        .cs-graphic-wrap {
            padding: 1.25rem; background: #eef2f6; border-right: 1px solid var(--border-light);
        }
        @media (max-width: 860px) {
            .cs-graphic-wrap { border-right: none; border-bottom: 1px solid var(--border-light); }
        }
        .cs-graphic {
            aspect-ratio: 1 / 1; width: 100%; max-width: 420px; margin: 0 auto;
            border-radius: 18px; overflow: hidden; position: relative;
            display: flex; flex-direction: column; justify-content: space-between;
            padding: 1.6rem 1.5rem 1.35rem; color: #fff;
            box-shadow: 0 18px 40px rgba(11, 31, 51, 0.18);
            font-family: "DM Sans", ui-sans-serif, system-ui, sans-serif;
        }
        .cs-graphic.theme-sea {
            background:
                radial-gradient(120% 80% at 100% 0%, rgba(255,255,255,0.18), transparent 55%),
                linear-gradient(145deg, #0b1f33 0%, #0b7eb5 58%, #086690 100%);
        }
        .cs-graphic.theme-ink {
            background:
                radial-gradient(90% 70% at 0% 100%, rgba(11,126,181,0.35), transparent 50%),
                linear-gradient(160deg, #071421 0%, #0b1f33 55%, #16324f 100%);
        }
        .cs-graphic.theme-teal {
            background:
                radial-gradient(100% 80% at 100% 0%, rgba(255,255,255,0.14), transparent 50%),
                linear-gradient(150deg, #0f3d3a 0%, #0f766e 55%, #115e59 100%);
        }
        .cs-graphic.theme-olive {
            background:
                radial-gradient(100% 80% at 0% 0%, rgba(255,255,255,0.12), transparent 45%),
                linear-gradient(150deg, #1a2e05 0%, #3f6212 55%, #365314 100%);
        }
        .cs-graphic.theme-sky {
            background:
                radial-gradient(100% 80% at 100% 100%, rgba(255,255,255,0.16), transparent 45%),
                linear-gradient(150deg, #0c4a6e 0%, #0369a1 55%, #0284c7 100%);
        }
        .cs-graphic.theme-mist {
            background:
                linear-gradient(160deg, #0b1f33 0%, #1e3a5f 45%, #334155 100%);
        }
        .cs-graphic.theme-gold {
            background:
                radial-gradient(90% 70% at 100% 0%, rgba(251,191,36,0.28), transparent 50%),
                linear-gradient(150deg, #0b1f33 0%, #1e3a5f 40%, #92400e 100%);
        }
        .cs-brand {
            display: flex; align-items: center; gap: 0.55rem;
        }
        .cs-brand img { height: 28px; width: auto; filter: brightness(0) invert(1); opacity: 0.95; }
        .cs-brand strong {
            font-family: Fraunces, Georgia, serif; font-size: 1rem; font-weight: 700; letter-spacing: -0.02em;
        }
        .cs-kicker {
            display: inline-block; margin-top: 1.35rem; font-size: 0.72rem; font-weight: 700;
            letter-spacing: 0.08em; text-transform: uppercase; opacity: 0.85;
            border: 1px solid rgba(255,255,255,0.35); padding: 0.3rem 0.55rem; border-radius: 999px;
        }
        .cs-graphic h3 {
            margin: 0.85rem 0 0.55rem; font-family: Fraunces, Georgia, serif;
            font-size: clamp(1.45rem, 3.2vw, 1.85rem); line-height: 1.15; letter-spacing: -0.02em; font-weight: 700;
        }
        .cs-graphic .sub {
            margin: 0; font-size: 0.92rem; line-height: 1.45; opacity: 0.9; max-width: 28ch;
        }
        .cs-highlight {
            margin-top: 1.1rem; display: inline-block; background: rgba(255,255,255,0.14);
            border: 1px solid rgba(255,255,255,0.28); padding: 0.55rem 0.75rem; border-radius: 10px;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 0.85rem; font-weight: 700;
            letter-spacing: 0.02em;
        }
        .cs-foot {
            display: flex; justify-content: space-between; align-items: end; gap: 0.75rem; margin-top: 1.25rem;
            font-size: 0.78rem; opacity: 0.88;
        }
        .cs-foot .cta { font-weight: 700; }
        .cs-copy-col { padding: 1.25rem; }
        .cs-label {
            font-size: 0.72rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase;
            color: var(--text-muted); margin: 0 0 0.4rem;
        }
        .cs-caption {
            width: 100%; min-height: 11rem; resize: vertical; padding: 0.85rem 1rem;
            border: 1px solid var(--border-light); border-radius: var(--radius-md);
            font-family: inherit; font-size: 0.9rem; line-height: 1.5; color: var(--primary-navy);
            background: #f8fafc;
        }
        .cs-tags {
            margin-top: 0.75rem; padding: 0.65rem 0.85rem; background: #f1f5f9;
            border-radius: var(--radius-md); font-size: 0.82rem; color: var(--text-muted); line-height: 1.4;
        }
        .cs-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.85rem; }
        .cs-btn {
            border: none; cursor: pointer; border-radius: var(--radius-md); font-weight: 700; font-size: 0.85rem;
            padding: 0.55rem 0.95rem;
        }
        .cs-btn-primary { background: var(--primary-cerulean); color: #fff; }
        .cs-btn-primary:hover { background: var(--primary-cerulean-hover); }
        .cs-btn-ghost { background: white; color: var(--primary-navy); border: 1px solid var(--border-light); }
        .cs-tip {
            margin-top: 1rem; font-size: 0.82rem; color: var(--text-muted); line-height: 1.45;
            padding: 0.75rem 0.85rem; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: var(--radius-md);
        }
        .cs-status { font-size: 0.8rem; color: #065f46; font-weight: 600; min-height: 1.2em; margin-top: 0.45rem; }
        .cs-guide {
            background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg);
            padding: 1.15rem 1.25rem; box-shadow: var(--shadow-sm); margin-bottom: 1.5rem;
        }
        .cs-guide h2 { margin: 0 0 0.5rem; font-size: 1rem; color: var(--primary-navy); }
        .cs-guide ol { margin: 0; padding-left: 1.2rem; color: var(--text-muted); font-size: 0.88rem; line-height: 1.55; }
        .cs-guide code {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 0.84em;
            background: #f1f5f9; padding: 0.1rem 0.35rem; border-radius: 4px; color: var(--primary-navy);
        }
    </style>

    <div class="cs-wrap">
        <div class="cs-hero">
            <div>
                <div style="font-size: 0.75rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.25rem;">Cerulean Labs · Marketing</div>
                <h1>Content studio</h1>
                <p>
                    Ready-to-post LinkedIn packs for PractisBase. Screenshot the graphic, copy the caption, or screen-record the tip for each card.
                    Company login only.
                </p>
            </div>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <a href="{{ $siteUrl }}" target="_blank" rel="noopener" style="background: white; color: var(--primary-navy); border: 1px solid var(--border-light); padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">Open site</a>
                <a href="{{ $siteUrl }}/register?promo_code={{ urlencode($foundingCode) }}" target="_blank" rel="noopener" style="background: var(--primary-cerulean); color: white; padding: 0.55rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.85rem; text-decoration: none;">Founding signup link</a>
            </div>
        </div>

        <div class="cs-guide">
            <h2>How to use</h2>
            <ol>
                <li>Pick a pack below (start with <strong>Launch · Founding 25</strong>).</li>
                <li>Screenshot the square graphic (full card, no browser chrome if you can).</li>
                <li>Click <strong>Copy caption</strong>, paste into LinkedIn, attach the image.</li>
                <li>Optional: follow the screen-record tip for a 15–30s demo clip.</li>
                <li>Founding code live in copy: <code>{{ $foundingCode }}</code> · first 25 · 3 months free.</li>
            </ol>
        </div>

        <div class="cs-chip-row" id="cs-filters">
            <button type="button" class="cs-chip is-active" data-filter="all">All</button>
            <button type="button" class="cs-chip" data-filter="launch">Launch</button>
            <button type="button" class="cs-chip" data-filter="product">Product</button>
            <button type="button" class="cs-chip" data-filter="profession">Professions</button>
            <button type="button" class="cs-chip" data-filter="trust">Trust</button>
        </div>

        @foreach($packs as $pack)
            @php
                $group = match (true) {
                    str_contains($pack['id'], 'launch') || str_contains($pack['id'], 'founding') => 'launch',
                    in_array($pack['id'], ['doctors-vault', 'architects-desk', 'engineers-field'], true) => 'profession',
                    in_array($pack['id'], ['backup-trust', 'cerulean-labs'], true) => 'trust',
                    default => 'product',
                };
            @endphp
            <article class="cs-pack" id="pack-{{ $pack['id'] }}" data-group="{{ $group }}">
                <div class="cs-pack-head">
                    <div>
                        <div class="cs-meta">{{ $pack['channel'] }} · Ready to post</div>
                        <h2>{{ $pack['label'] }}</h2>
                    </div>
                    <span class="cs-ready">Ready</span>
                </div>
                <div class="cs-body">
                    <div class="cs-graphic-wrap">
                        <div class="cs-graphic theme-{{ $pack['theme'] }}" data-graphic>
                            <div>
                                <div class="cs-brand">
                                    <span style="display: inline-flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.95); border-radius: 8px; padding: 0.3rem 0.45rem;">
                                        <img src="/images/logo.png" alt="" style="height: 22px; width: auto; filter: none; opacity: 1;">
                                    </span>
                                    <strong>PractisBase</strong>
                                </div>
                                <div class="cs-kicker">{{ $pack['kicker'] }}</div>
                                <h3>{{ $pack['headline'] }}</h3>
                                <p class="sub">{{ $pack['subline'] }}</p>
                                @if($pack['highlight'])
                                    <div class="cs-highlight">{{ $pack['highlight'] }}</div>
                                @endif
                            </div>
                            <div class="cs-foot">
                                <span class="cta">{{ $pack['cta'] }}</span>
                                <span>practisbase.com</span>
                            </div>
                        </div>
                    </div>
                    <div class="cs-copy-col">
                        <div class="cs-label">LinkedIn caption</div>
                        <textarea class="cs-caption" id="caption-{{ $pack['id'] }}" readonly>{{ $pack['caption'] }}

{{ $pack['hashtags'] }}</textarea>
                        <div class="cs-tags">{{ $pack['hashtags'] }}</div>
                        <div class="cs-actions">
                            <button type="button" class="cs-btn cs-btn-primary" data-copy="#caption-{{ $pack['id'] }}">Copy caption</button>
                            @if(filled($pack['highlight']))
                                <button type="button" class="cs-btn cs-btn-ghost" data-copy-text="{{ $pack['highlight'] }}">Copy highlight</button>
                            @endif
                            <a class="cs-btn cs-btn-ghost" style="text-decoration: none; display: inline-flex; align-items: center;" href="#pack-{{ $pack['id'] }}">Focus graphic</a>
                        </div>
                        <div class="cs-status" data-status-for="caption-{{ $pack['id'] }}" aria-live="polite"></div>
                        <div class="cs-tip"><strong>Screen tip:</strong> {{ $pack['tip'] }}</div>
                    </div>
                </div>
            </article>
        @endforeach
    </div>

    <script>
        (function () {
            function copyText(text, statusEl) {
                function done() {
                    if (statusEl) statusEl.textContent = 'Copied.';
                    setTimeout(function () { if (statusEl) statusEl.textContent = ''; }, 1800);
                }
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(done).catch(function () {
                        fallback(text); done();
                    });
                } else {
                    fallback(text); done();
                }
            }
            function fallback(text) {
                var ta = document.createElement('textarea');
                ta.value = text;
                document.body.appendChild(ta);
                ta.select();
                try { document.execCommand('copy'); } catch (e) {}
                document.body.removeChild(ta);
            }

            document.querySelectorAll('[data-copy]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var sel = btn.getAttribute('data-copy');
                    var el = document.querySelector(sel);
                    if (!el) return;
                    var status = document.querySelector('[data-status-for="' + el.id + '"]');
                    copyText(el.value, status);
                });
            });

            document.querySelectorAll('[data-copy-text]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (btn.disabled) return;
                    var text = btn.getAttribute('data-copy-text') || '';
                    if (!text) return;
                    var pack = btn.closest('.cs-pack');
                    var status = pack ? pack.querySelector('.cs-status') : null;
                    copyText(text, status);
                });
            });

            var filters = document.getElementById('cs-filters');
            if (filters) {
                filters.addEventListener('click', function (e) {
                    var btn = e.target.closest('[data-filter]');
                    if (!btn) return;
                    filters.querySelectorAll('.cs-chip').forEach(function (c) { c.classList.remove('is-active'); });
                    btn.classList.add('is-active');
                    var f = btn.getAttribute('data-filter');
                    document.querySelectorAll('.cs-pack').forEach(function (pack) {
                        pack.style.display = (f === 'all' || pack.getAttribute('data-group') === f) ? '' : 'none';
                    });
                });
            }
        })();
    </script>
@endsection

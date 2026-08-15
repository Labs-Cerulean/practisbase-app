<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('page_title') | PractisBase</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
    <style>
        body {
            margin: 0;
            background: var(--bg-canvas);
            color: var(--text-main, #1e293b);
            font-family: Inter, sans-serif;
        }
        .legal-shell {
            max-width: 820px;
            margin: 0 auto;
            padding: 2rem 1.25rem 3rem;
        }
        .legal-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1.75rem;
        }
        .legal-top img {
            max-width: 160px;
            height: auto;
        }
        .legal-card {
            background: white;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            padding: 1.75rem 1.5rem;
            line-height: 1.65;
            font-size: 0.95rem;
            color: #334155;
        }
        .legal-card h1 {
            color: var(--primary-navy);
            font-size: 1.6rem;
            margin: 0 0 0.5rem;
        }
        .legal-card h2 {
            color: var(--primary-navy);
            font-size: 1.1rem;
            margin: 1.75rem 0 0.6rem;
        }
        .legal-card p { margin: 0 0 0.85rem; }
        .legal-card ul { margin: 0 0 0.85rem; padding-left: 1.25rem; }
        .legal-card li { margin-bottom: 0.35rem; }
        .legal-card a { color: var(--primary-cerulean); font-weight: 600; text-decoration: none; }
        .legal-card a:hover { text-decoration: underline; }
        .legal-meta {
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-bottom: 1.25rem;
        }
        .legal-note {
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #78350f;
            border-radius: var(--radius-md);
            padding: 0.85rem 1rem;
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
        }
        .legal-footer-links {
            margin-top: 1.5rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            font-size: 0.9rem;
        }
        .legal-footer-links a {
            color: var(--primary-cerulean);
            font-weight: 600;
            text-decoration: none;
        }
    </style>
    @include('partials.pwa-head')
</head>
<body>
    <div class="legal-shell">
        <div class="legal-top">
            <a href="{{ auth()->check() ? '/dashboard' : '/' }}">
                <img src="/images/logo.png" alt="PractisBase">
            </a>
            <div style="display: flex; gap: 0.85rem; flex-wrap: wrap; font-size: 0.9rem;">
                <a href="/privacy" style="color: var(--primary-navy); font-weight: 600; text-decoration: none;">Privacy</a>
                <a href="/msa" style="color: var(--primary-navy); font-weight: 600; text-decoration: none;">MSA</a>
                @auth
                    <a href="/community/feedback" style="color: var(--primary-cerulean); font-weight: 600; text-decoration: none;">Community feedback</a>
                @else
                    <a href="/login" style="color: var(--primary-cerulean); font-weight: 600; text-decoration: none;">Sign in</a>
                @endauth
            </div>
        </div>

        <article class="legal-card">
            @yield('content')
        </article>

        <div class="legal-footer-links">
            <a href="/privacy">Privacy Policy</a>
            <a href="/msa">Master Service Agreement</a>
            <a href="{{ auth()->check() ? '/dashboard' : '/' }}">Back to PractisBase</a>
        </div>
    </div>
</body>
</html>

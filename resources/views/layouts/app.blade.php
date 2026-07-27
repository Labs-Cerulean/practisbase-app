<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('page_title', 'Overview') · {{ config('app.name', 'PractisBase') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/css/style.css">
</head>
<body>

    <div class="app-layout" id="app-layout">
        <div class="nav-backdrop" id="nav-backdrop" hidden></div>

        <aside class="app-sidebar" id="app-sidebar">
            <div class="sidebar-brand">
                <img src="/images/logo.png" alt="PractisBase">
                <button type="button" class="nav-close" id="nav-close" aria-label="Close menu">&times;</button>
            </div>

            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="/dashboard" class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}">Dashboard</a>
                    </li>
                    <li>
                        <a href="/clients" class="nav-link {{ request()->is('clients*') ? 'active' : '' }}">Clients</a>
                    </li>
                    <li>
                        <a href="/ledger" class="nav-link {{ request()->is('ledger*') ? 'active' : '' }}">Ledger</a>
                    </li>

                    @auth
                        @if(auth()->user()->canAccessReports())
                            <li class="nav-section">Standard</li>
                            <li>
                                <a href="/reports" class="nav-link {{ request()->is('reports*') ? 'active' : '' }}">Fiscal Report</a>
                            </li>
                            <li>
                                <a href="/expenses" class="nav-link {{ request()->is('expenses*') ? 'active' : '' }}">Expenses</a>
                            </li>
                            <li>
                                <a href="/exports/accountant" class="nav-link {{ request()->is('exports*') ? 'active' : '' }}">Accountant</a>
                            </li>
                        @endif

                        @if(auth()->user()->canAccessProPackage('med'))
                            <li class="nav-section">Pro Medical</li>
                            <li><a href="/pro/medical/patients" class="nav-link {{ request()->is('pro/medical/patients*') || request()->is('pro/medical/vault*') ? 'active' : '' }}">Patients</a></li>
                            <li><a href="/pro/medical/stampables" class="nav-link {{ request()->is('pro/medical/stampables*') ? 'active' : '' }}">Stampables</a></li>
                        @elseif(auth()->user()->canAccessProPackage('arch'))
                            <li class="nav-section">Pro Architect</li>
                            <li><a href="/pro/architect/projects" class="nav-link {{ request()->is('pro/architect/projects*') ? 'active' : '' }}">Projects</a></li>
                            <li><a href="/pro/architect/stamper" class="nav-link {{ request()->is('pro/architect/stamper*') ? 'active' : '' }}">Stamper</a></li>
                            <li><a href="/pro/certificates" class="nav-link {{ request()->is('pro/certificates*') ? 'active' : '' }}">Certificates</a></li>
                        @elseif(auth()->user()->canAccessProPackage('eng'))
                            <li class="nav-section">Pro Engineer</li>
                            <li><a href="/pro/engineer/projects" class="nav-link {{ request()->is('pro/engineer/projects*') ? 'active' : '' }}">Projects</a></li>
                            <li><a href="/pro/certificates" class="nav-link {{ request()->is('pro/certificates*') ? 'active' : '' }}">Certificates</a></li>
                            <li><span class="nav-link nav-link-disabled">EMS / BMS</span></li>
                        @endif

                        <li class="nav-section nav-section-mobile">Account</li>
                        <li class="nav-mobile-only"><a href="/settings" class="nav-link {{ request()->is('settings*') ? 'active' : '' }}">Settings</a></li>
                        <li class="nav-mobile-only">
                            <form action="/logout" method="POST" class="nav-logout-form">
                                @csrf
                                <button type="submit" class="nav-link nav-logout-btn">Log out</button>
                            </form>
                        </li>
                    @endauth
                </ul>
            </nav>
        </aside>

        <header class="app-header">
            <div class="header-left">
                <button type="button" class="nav-toggle" id="nav-toggle" aria-label="Open menu" aria-controls="app-sidebar" aria-expanded="false">
                    <span></span><span></span><span></span>
                </button>
                <div class="header-title">@yield('page_title', 'Overview')</div>
            </div>

            @auth
            <div class="user-profile">
                <span class="tier-badge">{{ \App\Support\TierPolicy::label(auth()->user()->tier) }}</span>
                <div class="user-meta">
                    <div class="user-name">{{ auth()->user()->name }}</div>
                    <div class="user-profession">{{ auth()->user()->profession }}</div>
                </div>
                <div class="avatar" aria-hidden="true">{{ substr(auth()->user()->name, 0, 1) }}</div>
                <a href="/settings" class="header-link header-desktop-only">Settings</a>
                <form action="/logout" method="POST" class="header-logout header-desktop-only">
                    @csrf
                    <button type="submit" class="logout-btn">Log out</button>
                </form>
            </div>
            @endauth
        </header>

        <main class="app-main">
            @auth
                <div class="beta-banner">
                    <strong>Closed beta</strong> — billing is not live. Plans are for testing (Settings). Do not rely on this as your sole clinical or accounting system of record.
                </div>
            @endauth

            <div class="app-main-body">
                @yield('content')
            </div>

            <footer class="app-footer">
                <div>&copy; {{ date('Y') }} PractisBase</div>
                <div class="app-footer-links">
                    <a href="#">Privacy</a>
                    <a href="#">MSA</a>
                    <a href="#">Security</a>
                </div>
                <div class="app-footer-build">
                    Build {{ env('APP_VERSION', '1.0') }}.{{ substr(env('RAILWAY_GIT_COMMIT_SHA', 'dev'), 0, 7) }}
                </div>
            </footer>
        </main>
    </div>

    <script>
        (function () {
            var layout = document.getElementById('app-layout');
            var toggle = document.getElementById('nav-toggle');
            var closeBtn = document.getElementById('nav-close');
            var backdrop = document.getElementById('nav-backdrop');
            if (!layout || !toggle) return;

            function setOpen(open) {
                layout.classList.toggle('nav-open', open);
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                if (backdrop) backdrop.hidden = !open;
                document.body.style.overflow = open ? 'hidden' : '';
            }

            toggle.addEventListener('click', function () {
                setOpen(!layout.classList.contains('nav-open'));
            });
            if (closeBtn) closeBtn.addEventListener('click', function () { setOpen(false); });
            if (backdrop) backdrop.addEventListener('click', function () { setOpen(false); });
            document.querySelectorAll('.app-sidebar .nav-link').forEach(function (link) {
                link.addEventListener('click', function () { setOpen(false); });
            });
            window.addEventListener('resize', function () {
                if (window.innerWidth > 768) setOpen(false);
            });
        })();
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'PractisBase') }} | @yield('page_title', 'Overview')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/css/style.css?v=shell3">
</head>
<body>
    @php
        $companyMode = auth()->check() && auth()->user()->canAccessCompanyBooks();
    @endphp

    <div class="app-layout" id="app-layout">
        <div class="nav-backdrop" id="nav-backdrop" hidden></div>

        <aside class="app-sidebar" id="app-sidebar">
            <div class="sidebar-brand">
                <a href="{{ $companyMode ? '/company' : '/dashboard' }}" style="display: block;">
                    <img src="/images/logo.png" alt="PractisBase">
                </a>
            </div>

            <nav class="sidebar-nav">
                <ul>
                    @auth
                        @if($companyMode)
                            <li class="nav-section-label">Cerulean Labs Ltd</li>
                            <li><a href="/company" class="nav-link {{ request()->is('company') ? 'active' : '' }}">Desk</a></li>
                            <li><a href="/company/accounts" class="nav-link {{ request()->is('company/accounts*') ? 'active' : '' }}">Accounts</a></li>
                            <li><a href="/company/invoices" class="nav-link {{ request()->is('company/invoices*') ? 'active' : '' }}">Invoices</a></li>
                            <li><a href="/company/recurring" class="nav-link {{ request()->is('company/recurring*') ? 'active' : '' }}">Monthly billing</a></li>
                            <li><a href="/company/expenses" class="nav-link {{ request()->is('company/expenses*') ? 'active' : '' }}">Expenses</a></li>
                            <li><a href="/company/bank" class="nav-link {{ request()->is('company/bank*') ? 'active' : '' }}">Bank recon</a></li>
                            <li><a href="/company/dividends" class="nav-link {{ request()->is('company/dividends*') ? 'active' : '' }}">Dividends</a></li>
                            <li><a href="/company/clients" class="nav-link {{ request()->is('company/clients*') ? 'active' : '' }}">Clients</a></li>
                            <li><a href="/company/profile" class="nav-link {{ request()->is('company/profile*') ? 'active' : '' }}">Company profile</a></li>
                            <li class="nav-section-label" aria-hidden="true">Community</li>
                            <li><a href="/community/feedback/inbox" class="nav-link {{ request()->is('community/feedback/inbox*') ? 'active' : '' }}">Feedback inbox</a></li>
                            <li><a href="/community/feedback" class="nav-link {{ request()->is('community/feedback') || request()->is('community/feedback/create') || (request()->is('community/feedback/*') && ! request()->is('community/feedback/inbox*')) ? 'active' : '' }}">My feedback</a></li>
                        @else
                            <li class="nav-section-label" aria-hidden="true">General</li>
                            <li>
                                <a href="/dashboard" class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}">Overview</a>
                            </li>
                            <li>
                                <a href="/clients" class="nav-link {{ request()->is('clients*') ? 'active' : '' }}">Clients</a>
                            </li>
                            <li>
                                <a href="/ledger" class="nav-link {{ request()->is('ledger*') ? 'active' : '' }}">Invoices</a>
                            </li>

                            @if(auth()->user()->canAccessReports())
                                <li class="nav-section-label" aria-hidden="true">Money</li>
                                <li>
                                    <a href="/reports" class="nav-link {{ request()->is('reports*') ? 'active' : '' }}">Tax &amp; VAT</a>
                                </li>
                                <li>
                                    <a href="/expenses" class="nav-link {{ request()->is('expenses*') ? 'active' : '' }}">Expenses</a>
                                </li>
                                <li>
                                    <a href="/exports/accountant" class="nav-link {{ request()->is('exports*') ? 'active' : '' }}">For accountant</a>
                                </li>
                            @endif

                            @if(auth()->user()->canAccessProPackage('med'))
                                <li class="nav-section-label" aria-hidden="true">Clinical</li>
                                <li><a href="/pro/medical/patients" class="nav-link {{ request()->is('pro/medical/patients*') || request()->is('pro/medical/vault*') ? 'active' : '' }}">Patients</a></li>
                                <li><a href="/pro/medical/stampables" class="nav-link {{ request()->is('pro/medical/stampables*') ? 'active' : '' }}">Stampables</a></li>
                            @elseif(auth()->user()->canAccessProPackage('arch'))
                                <li class="nav-section-label" aria-hidden="true">Projects</li>
                                <li><a href="/pro/architect/projects" class="nav-link {{ request()->is('pro/architect/projects*') || request()->is('pro/architect/pa*') ? 'active' : '' }}">Projects</a></li>
                                <li><a href="/pro/architect/documents" class="nav-link {{ request()->is('pro/architect/documents*') ? 'active' : '' }}">Documents</a></li>
                                <li><a href="/pro/architect/condition-reports" class="nav-link {{ request()->is('pro/architect/condition-reports*') ? 'active' : '' }}">Condition reports</a></li>
                                <li><a href="/pro/architect/method-statements" class="nav-link {{ request()->is('pro/architect/method-statements*') ? 'active' : '' }}">Method statements</a></li>
                                <li><a href="/pro/architect/templates" class="nav-link {{ request()->is('pro/architect/templates*') ? 'active' : '' }}">BCA templates</a></li>
                                <li><a href="/pro/architect/stamper" class="nav-link {{ request()->is('pro/architect/stamper*') ? 'active' : '' }}">Stamper</a></li>
                                <li><a href="/pro/certificates" class="nav-link {{ request()->is('pro/certificates*') ? 'active' : '' }}">Certificates</a></li>
                            @elseif(auth()->user()->canAccessProPackage('eng'))
                                <li class="nav-section-label" aria-hidden="true">Projects</li>
                                <li><a href="/pro/engineer/projects" class="nav-link {{ request()->is('pro/engineer/projects*') || request()->is('pro/engineer/pa*') ? 'active' : '' }}">Projects</a></li>
                                <li><a href="/pro/engineer/documents" class="nav-link {{ request()->is('pro/engineer/documents*') ? 'active' : '' }}">Documents</a></li>
                                <li><a href="/pro/certificates" class="nav-link {{ request()->is('pro/certificates*') || request()->is('pro/engineer/certificates*') ? 'active' : '' }}">Certificates</a></li>
                                <li><a href="/pro/engineer/reports" class="nav-link {{ request()->is('pro/engineer/reports*') ? 'active' : '' }}">Reports</a></li>
                                <li class="nav-section-label" aria-hidden="true">Equipment</li>
                                <li><a href="/pro/engineer/equipment" class="nav-link {{ request()->is('pro/engineer/equipment') || request()->is('pro/engineer/equipment/create') || (request()->is('pro/engineer/equipment/*') && ! request()->is('pro/engineer/equipment/due')) ? 'active' : '' }}">Register</a></li>
                                <li><a href="/pro/engineer/equipment/due" class="nav-link {{ request()->is('pro/engineer/equipment/due') ? 'active' : '' }}">Due board</a></li>
                            @endif

                            <li class="nav-section-label" aria-hidden="true">Community</li>
                            <li>
                                <a href="/community/feedback" class="nav-link {{ request()->is('community/feedback*') ? 'active' : '' }}">Feedback</a>
                            </li>
                        @endif

                        <li class="nav-mobile-only nav-section-label">Account</li>
                        <li class="nav-mobile-only">
                            <a href="/settings" class="nav-link {{ request()->is('settings*') ? 'active' : '' }}">Settings</a>
                        </li>
                        <li class="nav-mobile-only">
                            <form action="/logout" method="POST" style="margin: 0;">
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
                <button type="button" class="nav-toggle" id="nav-toggle" aria-label="Open menu" aria-expanded="false" aria-controls="app-sidebar">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>
                <div class="header-title">@yield('page_title', 'Overview')</div>
            </div>

            @auth
            <div class="user-profile">
                @if($companyMode)
                    <span class="tier-badge">Company books</span>
                    <div class="user-meta">
                        <div class="user-name">{{ auth()->user()->name }}</div>
                        <div class="user-profession">Cerulean Labs Ltd</div>
                    </div>
                @else
                    <span class="tier-badge">{{ \App\Support\TierPolicy::label(auth()->user()->tier) }}</span>
                    <div class="user-meta">
                        <div class="user-name">{{ auth()->user()->name }}</div>
                        <div class="user-profession">{{ auth()->user()->profession }}</div>
                    </div>
                @endif
                <div class="avatar" aria-hidden="true">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                <a href="/settings" class="header-action header-desktop-only">Settings</a>
                <form action="/logout" method="POST" class="header-desktop-only header-logout-form">
                    @csrf
                    <button type="submit" class="logout-btn">Log out</button>
                </form>
            </div>
            @endauth
        </header>

        <main class="app-main">
            @auth
                <div class="beta-banner">
                    @if($companyMode)
                        <strong>Cerulean Labs Ltd desk</strong> — internal company books (Art 10). Sole-trader tax tools are disabled on this account.
                    @else
                        <strong>Closed beta</strong> — billing is not live yet. Plans are granted for testing (Settings). Do not rely on this build as your sole clinical or accounting system of record.
                    @endif
                </div>
            @endauth

            <div class="app-main-body">
                @auth
                    @if(session('success'))
                        <div style="margin-bottom: 1rem; padding: 0.85rem 1rem; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: var(--radius-md); color: #065f46; font-size: 0.9rem;">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div style="margin-bottom: 1rem; padding: 0.85rem 1rem; background: #fef2f2; border: 1px solid #fecaca; border-radius: var(--radius-md); color: #991b1b; font-size: 0.9rem;">{{ session('error') }}</div>
                    @endif
                @endauth
                @yield('content')
            </div>

            <footer class="app-footer">
                @if($companyMode ?? false)
                    <div>&copy; {{ date('Y') }} PractisBase · Internal Cerulean Labs Limited company desk.</div>
                @else
                    <div>&copy; {{ date('Y') }} PractisBase. All rights reserved. For Maltese sole traders only — not for Ltd companies.</div>
                @endif
                <div class="app-footer-links">
                    <a href="/privacy">Privacy Policy</a>
                    <a href="/msa">Master Service Agreement</a>
                    <a href="/community/feedback">Community feedback</a>
                </div>
                <div class="app-footer-build">
                    <strong>Build {{ env('APP_VERSION', '1.0') }}.{{ substr(env('RAILWAY_GIT_COMMIT_SHA', 'dev'), 0, 7) }}</strong>
                </div>
            </footer>
        </main>
    </div>

    <script>
        (function () {
            var layout = document.getElementById('app-layout');
            var toggle = document.getElementById('nav-toggle');
            var backdrop = document.getElementById('nav-backdrop');
            if (!layout || !toggle) return;

            function setOpen(open) {
                layout.classList.toggle('nav-open', open);
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                if (backdrop) backdrop.hidden = !open;
                document.body.classList.toggle('nav-lock', open);
            }

            toggle.addEventListener('click', function () {
                setOpen(!layout.classList.contains('nav-open'));
            });
            if (backdrop) backdrop.addEventListener('click', function () { setOpen(false); });
            layout.querySelectorAll('.app-sidebar .nav-link').forEach(function (link) {
                link.addEventListener('click', function () { setOpen(false); });
            });
            window.addEventListener('resize', function () {
                if (window.innerWidth > 900) setOpen(false);
            });
        })();
    </script>
</body>
</html>

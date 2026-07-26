<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'PractisBase') }} | Professional Platform</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/css/style.css">
    <style>
        /* Small additions for the dynamic badges and buttons */
        .tier-badge { background: rgba(255,255,255,0.2); color: white; padding: 0.2rem 0.6rem; border-radius: 20px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border: 1px solid rgba(255,255,255,0.3); }
        .logout-btn { background: none; border: none; color: #cbd5e1; cursor: pointer; font-weight: 600; font-size: 0.85rem; padding: 0; transition: 0.2s; }
        .logout-btn:hover { color: #ef4444; }
    </style>
</head>
<body>

    <div class="app-layout">
        
        <aside class="app-sidebar">
            <div class="sidebar-brand" style="justify-content: center; height: auto; min-height: var(--header-height); padding: var(--space-md) var(--space-md);">
                <img src="/images/logo.png" alt="PractisBase Full Logo" style="width: 100%; max-width: 180px; height: auto; object-fit: contain;">
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="/dashboard" class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}">Dashboard</a>
                    </li>
                    <li>
                        <a href="/clients" class="nav-link {{ request()->is('clients*') ? 'active' : '' }}">Clients Directory</a>
                    </li>
                    <li>
                        <a href="/ledger" class="nav-link {{ request()->is('ledger*') ? 'active' : '' }}">Ledger & Invoices</a>
                    </li>
                    
                    @auth
                        @if(auth()->user()->canAccessReports())
                            <li style="margin-top: 2rem; padding-left: 1rem; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">
                                Standard Tools
                            </li>
                            <li>
                                <a href="/reports" class="nav-link {{ request()->is('reports*') ? 'active' : '' }}">
                                    Live Fiscal Report
                                </a>
                            </li>
                            <li>
                                <a href="/expenses" class="nav-link {{ request()->is('expenses*') ? 'active' : '' }}">
                                    Expenses
                                </a>
                            </li>
                            <li>
                                <a href="/exports/accountant" class="nav-link {{ request()->is('exports*') ? 'active' : '' }}">
                                    Accountant Download
                                </a>
                            </li>
                        @endif

                        @if(auth()->user()->canAccessProPackage('med'))
                            <li style="margin-top: 2rem; padding-left: 1rem; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">
                                Pro Tools
                            </li>
                            <li><a href="/pro/medical/patients" class="nav-link {{ request()->is('pro/medical*') ? 'active' : '' }}">Patient Journals</a></li>
                            <li><a href="/pro/certificates" class="nav-link {{ request()->is('pro/certificates*') ? 'active' : '' }}">Certificates &amp; Declarations</a></li>
                        @elseif(auth()->user()->canAccessProPackage('arch'))
                            <li style="margin-top: 2rem; padding-left: 1rem; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">
                                Pro Tools
                            </li>
                            <li><a href="/pro/architect/projects" class="nav-link {{ request()->is('pro/architect/projects*') ? 'active' : '' }}">Architect DMS</a></li>
                            <li><a href="/pro/architect/stamper" class="nav-link {{ request()->is('pro/architect/stamper*') ? 'active' : '' }}">Document Stamper</a></li>
                            <li><a href="/pro/certificates" class="nav-link {{ request()->is('pro/certificates*') ? 'active' : '' }}">Certificates &amp; Declarations</a></li>
                        @elseif(auth()->user()->canAccessProPackage('eng'))
                            <li style="margin-top: 2rem; padding-left: 1rem; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">
                                Pro Tools
                            </li>
                            <li><a href="/pro/engineer/projects" class="nav-link {{ request()->is('pro/engineer/projects*') ? 'active' : '' }}">Engineering Projects</a></li>
                            <li><a href="/pro/certificates" class="nav-link {{ request()->is('pro/certificates*') ? 'active' : '' }}">Certificates &amp; Declarations</a></li>
                            <li><span class="nav-link" style="opacity: 0.55; cursor: default;">EMS / BMS Templates</span></li>
                        @endif
                    @endauth
                </ul>
            </nav>
        </aside>

        <header class="app-header">
            <div class="header-title">
                @yield('page_title', 'Overview')
            </div>
            
            @auth
            <div class="user-profile">
                <span class="tier-badge" style="margin-right: 1rem;">{{ ucwords(str_replace('-', ' ', auth()->user()->tier)) }}</span>
                
                <div style="text-align: right; line-height: 1.2; margin-right: 0.5rem;">
                    <div class="user-name" style="color: white;">{{ auth()->user()->name }}</div>
                    <div style="font-size: 0.7rem; color: #cbd5e1;">{{ auth()->user()->profession }}</div>
                </div>
                
                <div class="avatar">{{ substr(auth()->user()->name, 0, 1) }}</div>
                <a href="/settings" style="color: #cbd5e1; text-decoration: none; font-size: 0.85rem; font-weight: 600; margin-left: 1rem; border-left: 1px solid rgba(255,255,255,0.2); padding-left: 1rem; transition: 0.2s;">
                    Settings
                </a>
                <form action="/logout" method="POST" style="margin: 0 0 0 1rem; border-left: 1px solid rgba(255,255,255,0.2); padding-left: 1rem; display: flex; align-items: center;">
                    @csrf
                    <button type="submit" class="logout-btn">Log Out</button>
                </form>
            </div>
            @endauth
        </header>

        <main class="app-main" style="display: flex; flex-direction: column;">
            
            <div style="flex-grow: 1;">
                @yield('content')
            </div>

            <footer style="margin-top: 3rem; padding-top: 1.5rem; border-top: 1px solid var(--border-light); display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; color: var(--text-muted); font-size: 0.8rem; gap: 1rem;">
                
                <div>&copy; {{ date('Y') }} PractisBase. All rights reserved.</div>
                
                <div style="display: flex; gap: 1.5rem;">
                    <a href="#" style="color: var(--text-muted); transition: color 0.2s;">Privacy Policy</a>
                    <a href="#" style="color: var(--text-muted); transition: color 0.2s;">Master Service Agreement</a>
                    <a href="#" style="color: var(--text-muted); transition: color 0.2s;">Security & GDPR</a>
                </div>

                <div style="text-align: right;">
                    <strong>Build {{ env('APP_VERSION', '1.0') }}.{{ substr(env('RAILWAY_GIT_COMMIT_SHA', 'dev'), 0, 7) }}</strong> &bull; Engine v{{ app()->version() }}
                </div>
                
            </footer>
        </main>

    </div>

</body>
</html>
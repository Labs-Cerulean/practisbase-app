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
                        <a href="#" class="nav-link active">Dashboard</a>
                    </li>
                    <li>
                        <a href="#" class="nav-link">Clients Directory</a>
                    </li>
                    <li>
                        <a href="#" class="nav-link">Ledger & Invoices</a>
                    </li>
                    <li style="margin-top: 2rem; padding-left: 1rem; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">
                        Pro Tools
                    </li>
                    <li>
                        <a href="#" class="nav-link">Patient Journals</a>
                    </li>
                    <li>
                        <a href="#" class="nav-link">Document Stamper</a>
                    </li>
                </ul>
            </nav>
        </aside>

        <header class="app-header">
            <div class="header-title">
                @yield('page_title', 'Overview')
            </div>
            
            <div class="user-profile">
                <span class="user-name">Dr. Borg</span>
                <div class="avatar">B</div>
            </div>
        </header>

        <main class="app-main">
            @yield('content')
        </main>

    </div>

</body>
</html>
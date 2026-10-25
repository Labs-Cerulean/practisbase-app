<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirm email | PractisBase</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
    <style>
        body { background-color: var(--bg-canvas); display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 2rem; margin: 0; }
        .auth-card { background: var(--bg-surface); padding: 2.5rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); width: 100%; max-width: 450px; }
        .auth-header { text-align: center; margin-bottom: 1.5rem; }
        .auth-header img { width: 100%; max-width: 200px; height: auto; margin-bottom: 1rem; object-fit: contain; }
        .alert-error { background-color: #fef2f2; border: 1px solid #f87171; color: #b91c1c; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.25rem; font-size: 0.85rem; }
        .alert-status { background-color: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.25rem; font-size: 0.85rem; line-height: 1.45; }
        .btn-submit { width: 100%; padding: 0.85rem; background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); font-weight: 600; font-size: 1rem; cursor: pointer; }
        .btn-quiet { width: 100%; margin-top: 0.75rem; padding: 0.75rem; background: transparent; color: var(--text-muted); border: 1px solid var(--border-light); border-radius: var(--radius-md); font-weight: 600; font-size: 0.9rem; cursor: pointer; }
    </style>
    @include('partials.pwa-head')
</head>
<body>
    @include('partials.staging-banner')

    <div class="auth-card">
        <div class="auth-header">
            <img src="/images/logo.png" alt="PractisBase">
            <h2 style="color: var(--primary-navy); margin: 0 0 0.35rem;">Confirm your email</h2>
            <p style="color: var(--text-muted); font-size: 0.95rem; line-height: 1.45; margin: 0;">
                Open the link sent to <strong style="color: var(--primary-navy);">{{ $email }}</strong>. It expires in 60 minutes.
            </p>
        </div>

        @if (session('status'))
            <div class="alert-status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert-error">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form action="/email/verification-notification" method="POST">
            @csrf
            <button type="submit" class="btn-submit">Resend link</button>
        </form>

        <form action="/logout" method="POST">
            @csrf
            <button type="submit" class="btn-quiet">Log out</button>
        </form>
    </div>
</body>
</html>

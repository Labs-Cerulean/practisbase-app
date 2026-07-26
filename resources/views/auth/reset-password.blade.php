<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password | PractisBase</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
    <style>
        body { background-color: var(--bg-canvas); display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 2rem; }
        .auth-card { background: var(--bg-surface); padding: 2.5rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); width: 100%; max-width: 450px; }
        .auth-header { text-align: center; margin-bottom: 2rem; }
        .auth-header img { width: 100%; max-width: 200px; height: auto; margin-bottom: 1rem; object-fit: contain; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--primary-navy); font-size: 0.9rem; }
        .form-group input { width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-family: inherit; font-size: 0.95rem; }
        .alert-error { background-color: #fef2f2; border: 1px solid #f87171; color: #b91c1c; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; font-size: 0.85rem; }
        .hint { font-size: 0.8rem; color: var(--text-muted); margin-top: 0.35rem; line-height: 1.4; }
        .btn-submit { width: 100%; padding: 0.85rem; background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); font-weight: 600; font-size: 1rem; cursor: pointer; }
        .btn-submit:hover { background: var(--primary-cerulean-hover); }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="auth-header">
            <img src="/images/logo.png" alt="PractisBase">
            <h2 style="color: var(--primary-navy); margin-bottom: 0.25rem;">Choose a new password</h2>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">This resets your login password only — not any medical vault key.</p>
        </div>

        @if ($errors->any())
            <div class="alert-error">
                <ul style="margin: 0; padding-left: 1.5rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="/reset-password" method="POST">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" value="{{ old('email', $email) }}" required>
            </div>

            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="password" required autofocus>
                <div class="hint">At least 12 characters, with mixed case, a number, and a symbol.</div>
            </div>

            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="password_confirmation" required>
            </div>

            <button type="submit" class="btn-submit">Reset password</button>
        </form>

        <div style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem;">
            <a href="/login" style="color: var(--primary-cerulean); font-weight: 600;">&larr; Back to sign in</a>
        </div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | PractisBase</title>
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
        .alert-error ul { margin: 0; padding-left: 1.5rem; }
        
        .btn-submit { width: 100%; padding: 0.85rem; background: var(--primary-cerulean); color: white; border: none; border-radius: var(--radius-md); font-weight: 600; font-size: 1rem; cursor: pointer; transition: all 0.2s; }
        .btn-submit:hover { background: var(--primary-cerulean-hover); }
    </style>
</head>
<body>

    <div class="auth-card">
        <div class="auth-header">
            <img src="/images/logo.png" alt="PractisBase">
            <h2 style="color: var(--primary-navy); margin-bottom: 0.25rem;">Welcome Back</h2>
            <p style="color: var(--text-muted); font-size: 0.95rem;">Sign in to your professional account.</p>
        </div>

        @if ($errors->any())
            <div class="alert-error">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="/login-submit" method="POST">
            @csrf
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" value="{{ old('email') }}" required placeholder="john@example.com" autofocus autocomplete="username">
            </div>
            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 0.5rem;">
                    <label style="margin-bottom: 0;">Password</label>
                    <a href="/forgot-password" style="font-size: 0.8rem; color: var(--primary-cerulean); font-weight: 600; text-decoration: none;">Forgot password?</a>
                </div>
                <input type="password" name="password" required autocomplete="current-password">
            </div>

            <button type="submit" class="btn-submit">Sign In</button>
        </form>

        @if (session('status'))
            <div style="background-color: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-top: 1.5rem; font-size: 0.85rem; line-height: 1.45;">
                {{ session('status') }}
            </div>
        @endif
        
        <div style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem;">
            <span style="color: var(--text-muted);">Don't have an account?</span> 
            <a href="/register" style="color: var(--primary-cerulean); font-weight: 600;">Sign up</a>
        </div>
    </div>

</body>
</html>
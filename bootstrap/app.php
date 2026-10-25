<?php

use App\Http\Middleware\EnsureCompanyBooks;
use App\Http\Middleware\EnsureDocumentStamper;
use App\Http\Middleware\EnsureEmailVerified;
use App\Http\Middleware\EnsureMedicalVaultUnlocked;
use App\Http\Middleware\EnsureOnboardingComplete;
use App\Http\Middleware\EnsureProPackage;
use App\Http\Middleware\EnsureTermsAccepted;
use App\Http\Middleware\EnsureUserTier;
use App\Http\Middleware\ForceHttps;
use App\Http\Middleware\PreferCompanyBooksShell;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Railway terminates TLS. Trust the forwarded protocol so https
        // requests are not treated as the plain connection inside the container.
        $middleware->trustProxies(at: '*');
        $middleware->append(ForceHttps::class);

        $middleware->alias([
            'tier' => EnsureUserTier::class,
            'terms' => EnsureTermsAccepted::class,
            'onboarded' => EnsureOnboardingComplete::class,
            'verified' => EnsureEmailVerified::class,
            'pro' => EnsureProPackage::class,
            'vault' => EnsureMedicalVaultUnlocked::class,
            'company_books' => EnsureCompanyBooks::class,
            'company_shell' => PreferCompanyBooksShell::class,
            'stamper' => EnsureDocumentStamper::class,
        ]);

        // Mobile WebAuthn often drops session/CSRF cookies during the biometric UI.
        // These finish endpoints authenticate via a one-time server ticket instead.
        $middleware->validateCsrfTokens(except: [
            'pro/medical/vault/devices/register',
            'pro/medical/vault/devices/unlock',
            'stripe/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

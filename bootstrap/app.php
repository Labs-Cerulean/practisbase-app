<?php

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
        $middleware->alias([
            'tier' => \App\Http\Middleware\EnsureUserTier::class,
            'terms' => \App\Http\Middleware\EnsureTermsAccepted::class,
            'onboarded' => \App\Http\Middleware\EnsureOnboardingComplete::class,
            'pro' => \App\Http\Middleware\EnsureProPackage::class,
            'vault' => \App\Http\Middleware\EnsureMedicalVaultUnlocked::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

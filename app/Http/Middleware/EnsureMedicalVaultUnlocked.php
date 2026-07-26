<?php

namespace App\Http\Middleware;

use App\Models\MedicalVault;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMedicalVaultUnlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->canAccessProPackage('med')) {
            return redirect('/settings')->with(
                'error',
                'Pro Medical access requires a Medical Professional on the pro-med plan.'
            );
        }

        $vault = MedicalVault::activeForUser($user->id);

        if (! $vault) {
            return redirect('/pro/medical/vault/setup');
        }

        if (! $request->session()->has('medical_vault_key')) {
            return redirect('/pro/medical/vault/unlock');
        }

        return $next($request);
    }
}

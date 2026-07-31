<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyBooks
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect('/login');
        }

        if (! $user->canAccessCompanyBooks()) {
            return redirect('/dashboard')->with(
                'error',
                'Company books are an internal Cerulean Labs desk and are not enabled on this account.'
            );
        }

        return $next($request);
    }
}

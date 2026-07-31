<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * When company books are enabled, keep the user inside the Ltd desk.
 * Sole-trader fiscal routes are redirected so the wrong tax brain is not used by mistake.
 */
class PreferCompanyBooksShell
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->canAccessCompanyBooks()) {
            return $next($request);
        }

        $path = trim($request->path(), '/');

        if ($path === 'company' || str_starts_with($path, 'company/')) {
            return $next($request);
        }

        if (str_starts_with($path, 'settings')) {
            return $next($request);
        }

        $redirects = [
            'dashboard' => '/company',
            'clients' => '/company/clients',
            'ledger' => '/company/invoices',
            'reports' => '/company',
            'expenses' => '/company/expenses',
            'exports' => '/company',
            'pro' => '/company',
        ];

        foreach ($redirects as $prefix => $target) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return redirect($target);
            }
        }

        return $next($request);
    }
}

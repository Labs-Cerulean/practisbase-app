<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProPackage
{
    /**
     * @param  string  $package  med|arch|eng
     */
    public function handle(Request $request, Closure $next, string $package): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect('/login');
        }

        if (! $user->canAccessProPackage($package)) {
            return redirect('/settings')->with(
                'error',
                'That Pro package requires a matching profession and active Pro plan.'
            );
        }

        return $next($request);
    }
}

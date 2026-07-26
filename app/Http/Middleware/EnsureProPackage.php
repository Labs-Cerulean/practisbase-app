<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProPackage
{
    /**
     * @param  string  ...$packages  One or more of med|arch|eng (comma-separated args also accepted)
     */
    public function handle(Request $request, Closure $next, string ...$packages): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect('/login');
        }

        $allowed = [];
        foreach ($packages as $packageArg) {
            foreach (explode(',', $packageArg) as $piece) {
                $piece = trim($piece);
                if ($piece !== '') {
                    $allowed[] = $piece;
                }
            }
        }

        foreach ($allowed as $package) {
            if ($user->canAccessProPackage($package)) {
                return $next($request);
            }
        }

        return redirect('/settings')->with(
            'error',
            'That Pro feature requires a matching profession and active Pro plan.'
        );
    }
}

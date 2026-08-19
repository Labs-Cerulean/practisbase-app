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
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['ok' => false, 'message' => 'Unauthenticated.'], 401);
            }

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

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => false,
                'message' => 'That feature requires a matching profession and an active Practice or Pro plan.',
            ], 403);
        }

        return redirect('/settings')->with(
            'error',
            'That feature requires a matching profession and an active Practice or Pro plan.'
        );
    }
}

<?php

namespace App\Http\Middleware;

use App\Support\TierPolicy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserTier
{
    /**
     * @param  string  ...$tiers  e.g. "standard" (means Standard+) or explicit "pro-med,pro-arch"
     */
    public function handle(Request $request, Closure $next, string ...$tiers): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect('/login');
        }

        $allowed = [];
        foreach ($tiers as $tierArg) {
            foreach (explode(',', $tierArg) as $piece) {
                $piece = trim($piece);
                if ($piece !== '') {
                    $allowed[] = $piece;
                }
            }
        }

        if ($allowed === []) {
            $allowed = ['standard'];
        }

        if (TierPolicy::meetsMinimumTier($user, $allowed)) {
            return $next($request);
        }

        return redirect('/settings')
            ->with('error', 'That feature requires a higher plan. Upgrade your subscription to continue.');
    }
}

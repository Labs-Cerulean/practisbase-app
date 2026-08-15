<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDocumentStamper
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect('/login');
        }

        if (! $user->canAccessDocumentStamper()) {
            return redirect('/settings')
                ->with('error', 'Document Stamper needs Standard accounts or a Practice / Full Pro plan.');
        }

        return $next($request);
    }
}

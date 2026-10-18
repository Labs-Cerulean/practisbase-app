<?php

namespace App\Http\Middleware;

use App\Support\HttpsCanonical;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        $target = HttpsCanonical::redirectTarget(
            forwardedProto: $request->headers->get('X-Forwarded-Proto'),
            secure: $request->secure(),
            host: $request->getHost(),
            uri: $request->getRequestUri(),
            local: app()->environment('local', 'testing'),
        );

        if ($target !== null) {
            $status = in_array($request->getMethod(), ['GET', 'HEAD'], true) ? 301 : 308;
            $response = redirect()->away($target, $status);
            $this->rememberHttps($request, $response);

            return $response;
        }

        $response = $next($request);
        $this->rememberHttps($request, $response);

        return $response;
    }

    private function rememberHttps(Request $request, Response $response): void
    {
        if ($request->secure() && ! app()->environment('local', 'testing')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }
    }
}

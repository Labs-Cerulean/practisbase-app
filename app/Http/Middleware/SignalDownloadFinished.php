<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

class SignalDownloadFinished
{
    public const WAIT = 'pb_download_wait';

    public const DONE = 'pb_download';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
            return $response;
        }

        $token = (string) $request->cookie(self::WAIT, '');
        if (! preg_match('/^[a-z0-9]{16,40}$/', $token)) {
            return $response;
        }

        $disposition = strtolower((string) $response->headers->get('content-disposition', ''));
        if (! str_contains($disposition, 'attachment')) {
            return $response;
        }

        $response->headers->setCookie(Cookie::create(
            self::DONE,
            $token,
            time() + 120,
            '/',
            null,
            null,
            false,
            false,
            Cookie::SAMESITE_LAX
        ));

        return $response;
    }
}

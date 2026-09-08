<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Every page here is a per-session Inertia response, never a static asset —
 * static files (public/build, public/images, ...) are served directly by
 * the webserver and never touch this middleware. Left uncontrolled, an
 * intermediary (a CDN, a reverse-proxy cache, or a browser that treats
 * Laravel's default `no-cache` as "cacheable, just revalidate first") can
 * serve a stale Inertia XHR response — raw JSON — for what should be a
 * fresh full-page navigation after a tab sits idle. `no-store` is the one
 * directive every layer is obligated to honour: never cache this, period.
 */
class PreventResponseCaching
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }
}

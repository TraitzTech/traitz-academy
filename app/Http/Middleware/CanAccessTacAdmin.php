<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates the community admin area. Deliberately wider than the `admin`
 * middleware: TAC track mentors and school leads are often plain `user`
 * accounts, and they need to run their own corner of the community.
 */
class CanAccessTacAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->canAccessTacAdmin()) {
            abort(403, 'You do not have access to the community admin area.');
        }

        return $next($request);
    }
}

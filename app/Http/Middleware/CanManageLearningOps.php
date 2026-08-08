<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Coarse gate for the shared "learning ops" management pages (assignments,
 * schedules, broadcast notifications). Fine-grained authorization — which
 * specific course/cohort/program a user may target — is still enforced
 * per-request by the controllers' userCanManage() checks.
 */
class CanManageLearningOps
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->canManageLearningOps()) {
            abort(403, 'Unauthorized access');
        }

        return $next($request);
    }
}

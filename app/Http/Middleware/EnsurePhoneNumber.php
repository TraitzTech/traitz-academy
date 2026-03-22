<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePhoneNumber
{
    /**
     * Routes that should be excluded from phone check.
     *
     * @var array<int, string>
     */
    protected array $except = [
        'settings/profile',
        'logout',
        'login',
        'register',
        'email/verification*',
    ];

    /**
     * Handle an incoming request.
     *
     * Redirects users without a phone number to their profile settings page.
     * This ensures existing users (from before phone was mandatory) update their profile.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && empty($request->user()->phone)) {
            // Allow PATCH to profile (so they can actually save the phone)
            if ($request->is('settings/profile') && $request->isMethod('PATCH')) {
                return $next($request);
            }

            // Allow access to excluded routes
            foreach ($this->except as $pattern) {
                if ($request->is($pattern)) {
                    return $next($request);
                }
            }

            return redirect()->route('profile.edit')
                ->with('status', 'phone-required');
        }

        return $next($request);
    }
}

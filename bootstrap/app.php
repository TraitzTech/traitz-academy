<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\PreventResponseCaching;
use App\Http\Middleware\ShareSiteSettings;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/lms.php'));
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/internship.php'));
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/community.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            ShareSiteSettings::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            PreventResponseCaching::class,
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\IsAdmin::class,
            'executive' => \App\Http\Middleware\IsExecutive::class,
            'tutor' => \App\Http\Middleware\IsTutor::class,
            'learning-ops' => \App\Http\Middleware\CanManageLearningOps::class,
            'tac-staff' => \App\Http\Middleware\CanAccessTacAdmin::class,
            'ensure.phone' => \App\Http\Middleware\EnsurePhoneNumber::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // The forgot-password flow used to 500 whenever the mailer hiccuped
        // (e.g. an SMTP rate limit) because the reset email sent inline with
        // the request. The email is queued now, but this is a second safety
        // net: any leftover failure here should still land the user back on
        // the form with a plain-language message instead of a crash page.
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->routeIs('password.email', 'password.update') && ! $request->expectsJson()) {
                \Illuminate\Support\Facades\Log::error('Password reset request failed', [
                    'route' => $request->route()?->getName(),
                    'email' => $request->input('email'),
                    'error' => $e->getMessage(),
                ]);

                return back()->withInput($request->except('password', 'password_confirmation'))
                    ->with('error', 'Something went wrong on our end and we could not process your password reset. Please try again shortly.');
            }
        });
    })->create();

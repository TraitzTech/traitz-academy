<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Queued version of Laravel's default reset-password notification. Sending
 * it inline (the framework default) meant an SMTP hiccup — e.g. a provider
 * rate limit — surfaced as a 500 on the forgot-password request itself.
 * Queueing lets the request succeed immediately and the mailer retry with
 * backoff instead of failing the user's request.
 */
class ResetPasswordNotification extends ResetPassword implements ShouldQueue
{
    use Queueable;

    public $tries = 5;

    public function backoff(): array
    {
        return [60, 300, 900, 1800, 3600];
    }
}

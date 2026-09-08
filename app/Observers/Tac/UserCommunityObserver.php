<?php

namespace App\Observers\Tac;

use App\Models\User;
use App\Services\Tac\CommunityEnrollmentService;
use Illuminate\Support\Facades\Log;

/**
 * Keeps accounts and community member records joined up.
 *
 * Somebody who registered for an event as a guest already has a TAC member
 * record. The moment they create an account (or change their email to the one
 * we hold), the two link and they get the member area — no duplicate, no
 * manual reconciliation.
 */
class UserCommunityObserver
{
    public function __construct(private CommunityEnrollmentService $enrollment) {}

    public function created(User $user): void
    {
        $this->link($user);
    }

    public function updated(User $user): void
    {
        if ($user->wasChanged('email')) {
            $this->link($user);
        }
    }

    protected function link(User $user): void
    {
        try {
            $this->enrollment->linkUser($user);
        } catch (\Throwable $e) {
            Log::warning('Could not link user to TAC member', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

<?php

namespace App\Observers\Tac;

use App\Jobs\Tac\SyncCommunityMemberFromRegistration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Watches every registration surface in the academy — program applications,
 * event registrations, AI Forge, course enrollments, internships — and pulls
 * each new registrant into the community.
 *
 * Registered against all of {@see \App\Services\Tac\RegistrationMemberMapper::OBSERVED_MODELS}.
 */
class RegistrationCommunityObserver
{
    public function created(Model $registration): void
    {
        try {
            SyncCommunityMemberFromRegistration::dispatch($registration);
        } catch (\Throwable $e) {
            // Community enrollment is a side effect: it must never take down
            // the registration the user actually came to complete.
            Log::warning('Could not queue TAC auto-join', [
                'model' => $registration::class,
                'id' => $registration->getKey(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}

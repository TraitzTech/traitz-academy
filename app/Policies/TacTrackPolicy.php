<?php

namespace App\Policies;

use App\Models\TacTrack;
use App\Models\User;

class TacTrackPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessTacAdmin();
    }

    public function create(User $user): bool
    {
        return $user->hasTacExecutiveAuthority();
    }

    /**
     * A track mentor may curate the description and imagery of the track they
     * run; only executives may create or retire tracks outright.
     */
    public function update(User $user, TacTrack $track): bool
    {
        return $user->hasTacExecutiveAuthority()
            || in_array($track->id, $user->tacManagedTrackIds(), true);
    }

    public function delete(User $user, TacTrack $track): bool
    {
        return $user->hasTacExecutiveAuthority();
    }
}

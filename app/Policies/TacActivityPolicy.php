<?php

namespace App\Policies;

use App\Models\TacActivity;
use App\Models\User;

class TacActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessTacAdmin();
    }

    public function view(User $user, TacActivity $activity): bool
    {
        return $this->update($user, $activity) || $user->canAccessTacAdmin();
    }

    public function create(User $user): bool
    {
        return $user->canAccessTacAdmin();
    }

    /**
     * TAC executives may edit anything. A track mentor owns their track's
     * activities; any leader owns what they personally organise.
     */
    public function update(User $user, TacActivity $activity): bool
    {
        if ($user->hasTacExecutiveAuthority()) {
            return true;
        }

        if (in_array((int) $activity->organizer_leader_id, $user->tacLeaderIds(), true)) {
            return true;
        }

        return $activity->tac_track_id !== null
            && in_array((int) $activity->tac_track_id, $user->tacManagedTrackIds(), true);
    }

    public function delete(User $user, TacActivity $activity): bool
    {
        return $this->update($user, $activity);
    }

    /**
     * Publishing is deliberately narrower than editing: a mentor can draft and
     * refine, but putting something on the public calendar is an executive act.
     */
    public function publish(User $user, TacActivity $activity): bool
    {
        return $user->hasTacExecutiveAuthority();
    }

    /**
     * Managing RSVPs, check-in and reminders for an activity.
     */
    public function manageRsvps(User $user, TacActivity $activity): bool
    {
        return $this->update($user, $activity);
    }

    /**
     * Judging a competition: whoever may manage the activity may score it.
     */
    public function judge(User $user, TacActivity $activity): bool
    {
        return $activity->isCompetition() && $this->update($user, $activity);
    }
}

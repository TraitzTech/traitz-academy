<?php

namespace App\Policies;

use App\Models\TacLeaderPerformanceReview;
use App\Models\User;

class TacLeaderPerformanceReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasTacExecutiveAuthority();
    }

    public function view(User $user, TacLeaderPerformanceReview $review): bool
    {
        return $user->hasTacExecutiveAuthority()
            || (int) $review->leader?->user_id === $user->id;
    }

    /**
     * Writing and deleting reviews is a real academy staff act — not
     * something TAC's own leadership does to each other.
     */
    public function create(User $user): bool
    {
        return $user->canAccessAdminPanel();
    }

    public function delete(User $user, TacLeaderPerformanceReview $review): bool
    {
        return $user->canAccessAdminPanel();
    }
}

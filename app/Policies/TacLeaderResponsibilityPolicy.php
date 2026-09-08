<?php

namespace App\Policies;

use App\Models\TacLeaderResponsibility;
use App\Models\User;

class TacLeaderResponsibilityPolicy
{
    /**
     * TAC executives may look at responsibilities across the roster (they
     * already see the full leadership list); assigning and editing them is
     * reserved for actual academy staff.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasTacExecutiveAuthority();
    }

    public function view(User $user, TacLeaderResponsibility $responsibility): bool
    {
        return $user->hasTacExecutiveAuthority()
            || (int) $responsibility->leader?->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->canAccessAdminPanel();
    }

    public function update(User $user, TacLeaderResponsibility $responsibility): bool
    {
        return $user->canAccessAdminPanel();
    }

    public function delete(User $user, TacLeaderResponsibility $responsibility): bool
    {
        return $user->canAccessAdminPanel();
    }

    /**
     * The one thing a leader may change about their own responsibility: how
     * far along it is. Content (title, description, due date) stays staff-set.
     */
    public function updateStatus(User $user, TacLeaderResponsibility $responsibility): bool
    {
        return $user->canAccessAdminPanel()
            || (int) $responsibility->leader?->user_id === $user->id;
    }
}

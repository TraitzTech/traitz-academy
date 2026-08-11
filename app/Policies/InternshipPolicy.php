<?php

namespace App\Policies;

use App\Models\Internship;
use App\Models\User;

class InternshipPolicy
{
    /**
     * View the internship: the intern themselves, its effective supervisor, or
     * staff with admin-panel access.
     */
    public function view(User $user, Internship $internship): bool
    {
        return (int) $internship->user_id === (int) $user->id
            || $user->canAccessAdminPanel()
            || $internship->isSupervisedBy($user);
    }

    /**
     * Manage/review the internship (review logbooks, mark attendance): the
     * effective supervisor or admin-panel staff. Not the intern.
     */
    public function review(User $user, Internship $internship): bool
    {
        return $user->canAccessAdminPanel() || $internship->isSupervisedBy($user);
    }
}

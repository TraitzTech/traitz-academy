<?php

namespace App\Policies;

use App\Models\TacLeader;
use App\Models\User;

class TacLeaderPolicy
{
    /**
     * The full leadership roster carries personal contact details for every
     * leader across every track and school — that's org-wide visibility, not
     * something a single track mentor or school lead needs to browse.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasTacExecutiveAuthority();
    }

    public function view(User $user, TacLeader $leader): bool
    {
        return $user->hasTacExecutiveAuthority();
    }

    /**
     * Who holds a leadership post is an executive decision — a mentor cannot
     * appoint themselves, or anybody else.
     */
    public function create(User $user): bool
    {
        return $user->hasTacExecutiveAuthority();
    }

    public function update(User $user, TacLeader $leader): bool
    {
        return $user->hasTacExecutiveAuthority();
    }

    public function delete(User $user, TacLeader $leader): bool
    {
        return $user->hasTacExecutiveAuthority();
    }
}

<?php

namespace App\Policies;

use App\Models\TacPartner;
use App\Models\User;

class TacPartnerPolicy
{
    /**
     * The partner directory carries business contact details — restricted to
     * the people who actually manage partnerships, same as write access.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasTacExecutiveAuthority() || $user->isTacPartnershipLead();
    }

    public function view(User $user, TacPartner $partner): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Partnership leads exist to bring in and manage partners, so they get
     * write access to this resource alongside TAC executives.
     */
    public function create(User $user): bool
    {
        return $user->hasTacExecutiveAuthority() || $user->isTacPartnershipLead();
    }

    public function update(User $user, TacPartner $partner): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, TacPartner $partner): bool
    {
        return $user->hasTacExecutiveAuthority();
    }
}

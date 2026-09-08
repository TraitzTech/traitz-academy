<?php

namespace App\Policies;

use App\Models\CommunityMember;
use App\Models\User;

class CommunityMemberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessTacAdmin();
    }

    /**
     * Executives see the whole roster. A track mentor sees members in their
     * track; a school lead sees members at their school.
     */
    public function view(User $user, CommunityMember $member): bool
    {
        if ($user->hasTacExecutiveAuthority()) {
            return true;
        }

        $schools = $user->tacManagedSchools();

        if ($member->school !== null && in_array($member->school, $schools, true)) {
            return true;
        }

        $trackIds = $user->tacManagedTrackIds();

        return $trackIds !== []
            && $member->tracks()->whereIn('tac_tracks.id', $trackIds)->exists();
    }

    /**
     * Adding somebody by hand, and bulk segment operations.
     */
    public function create(User $user): bool
    {
        return $user->hasTacExecutiveAuthority();
    }

    /**
     * Editing member records, promoting them, and exporting the roster are
     * executive acts — mentors read, they do not rewrite.
     */
    public function update(User $user, CommunityMember $member): bool
    {
        return $user->hasTacExecutiveAuthority();
    }

    public function delete(User $user, CommunityMember $member): bool
    {
        return $user->hasTacExecutiveAuthority();
    }

    public function export(User $user): bool
    {
        return $user->hasTacExecutiveAuthority();
    }
}

<?php

namespace App\Policies;

use App\Models\LiveClass;
use App\Models\User;

class LiveClassPolicy
{
    /**
     * May access the class (room, chat, details): moderators, enrolled learners
     * (course access type), or explicitly-listed students (custom access type).
     */
    public function join(User $user, LiveClass $liveClass): bool
    {
        return $liveClass->canUserJoin($user);
    }

    /**
     * May host/moderate the class.
     */
    public function manage(User $user, LiveClass $liveClass): bool
    {
        return $liveClass->isManageableBy($user);
    }
}

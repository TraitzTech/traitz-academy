<?php

namespace App\Http\Controllers\Internships\Concerns;

use App\Models\Internship;
use App\Models\User;

trait ResolvesActiveInternship
{
    /**
     * The current user's active internship (404 if they have none).
     */
    protected function activeInternshipFor(User $user): Internship
    {
        return Internship::query()
            ->with('cohort')
            ->where('user_id', $user->id)
            ->where('status', Internship::STATUS_ACTIVE)
            ->latest('id')
            ->firstOrFail();
    }

    /**
     * Today's date in the internship's timezone (attendance/logbook are keyed by
     * local calendar day).
     */
    protected function todayFor(Internship $internship): string
    {
        return now($internship->timezone())->toDateString();
    }
}

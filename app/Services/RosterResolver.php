<?php

namespace App\Services;

use App\Models\Cohort;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Internship;
use App\Models\Program;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Resolves the student/intern user IDs that belong to an attachable
 * (Course, Cohort, or Program), so Assignments/LiveClasses/Schedule can
 * target any of the three uniformly.
 */
class RosterResolver
{
    public function resolveStudentIds(Model $attachable): Collection
    {
        return match (true) {
            $attachable instanceof Course => Enrollment::query()
                ->where('course_id', $attachable->id)
                ->where('access_status', '!=', 'revoked')
                ->pluck('user_id'),
            $attachable instanceof Cohort => Internship::query()
                ->where('cohort_id', $attachable->id)
                ->pluck('user_id'),
            $attachable instanceof Program => Internship::query()
                ->where('program_id', $attachable->id)
                ->pluck('user_id'),
            default => collect(),
        };
    }
}

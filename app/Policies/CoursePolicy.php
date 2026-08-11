<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\User;

class CoursePolicy
{
    /**
     * Whether the user may moderate the course (staff/tutor tools).
     */
    public function moderate(?User $user, Course $course): bool
    {
        return $user !== null && $user->canModerateCourse($course);
    }

    /**
     * Whether the user may consume the given lesson's content.
     * Free lessons are open to anyone; otherwise the user must moderate the
     * course or hold an access-granting enrollment.
     */
    public function viewLesson(?User $user, Course $course, CourseLesson $lesson): bool
    {
        if ($lesson->is_free) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        return $user->canModerateCourse($course) || $course->grantsAccessTo($user);
    }

    /**
     * Whether the user may take a quiz in the course. Quizzes require an
     * access-granting enrollment (no free-lesson or moderator bypass, matching
     * prior behavior).
     */
    public function takeQuiz(?User $user, Course $course): bool
    {
        return $user !== null && $course->grantsAccessTo($user);
    }
}

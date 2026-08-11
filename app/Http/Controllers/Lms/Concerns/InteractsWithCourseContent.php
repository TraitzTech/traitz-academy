<?php

namespace App\Http\Controllers\Lms\Concerns;

use App\Models\Course;
use App\Models\CourseLesson;

trait InteractsWithCourseContent
{
    /**
     * Ensure the lesson exists within the given published course.
     * Returns 404 (rather than 403) so unpublished or mismatched content is
     * indistinguishable from content that does not exist.
     */
    protected function assertLessonInPublishedCourse(Course $course, CourseLesson $lesson): void
    {
        abort_unless($course->status === 'published', 404);
        abort_unless((int) $lesson->course_id === (int) $course->id, 404);
    }
}

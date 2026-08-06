<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Lms\Concerns\InteractsWithCourseContent;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\Enrollment;
use App\Models\LessonCompletion;
use App\Models\LessonVideoProgress;
use App\Support\Lms\CourseProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CoursePlayerProgressController extends Controller
{
    use InteractsWithCourseContent;

    public function saveVideoProgress(Request $request, Course $course, CourseLesson $lesson): JsonResponse
    {
        $this->assertLessonInPublishedCourse($course, $lesson);
        $this->authorize('viewLesson', [$course, $lesson]);

        $enrollment = $this->resolveEnrollment($course);

        $validated = $request->validate([
            'watched_seconds' => ['required', 'integer', 'min:0'],
            'duration_seconds' => ['required', 'integer', 'min:0'],
        ]);

        $duration = max(1, (int) $validated['duration_seconds']);
        $watched = min((int) $validated['watched_seconds'], $duration);
        $percentage = min(100, round(($watched / $duration) * 100, 2));

        LessonVideoProgress::query()->updateOrCreate(
            [
                'user_id' => auth()->id(),
                'course_lesson_id' => $lesson->id,
            ],
            [
                'watched_seconds' => $watched,
                'duration_seconds' => $duration,
                'percentage' => $percentage,
                'last_watched_at' => now(),
            ]
        );

        if ($percentage >= LessonVideoProgress::COMPLETION_PERCENT_THRESHOLD && $enrollment !== null) {
            LessonCompletion::query()->firstOrCreate(
                [
                    'user_id' => auth()->id(),
                    'course_lesson_id' => $lesson->id,
                ],
                [
                    'enrollment_id' => $enrollment->id,
                    'completed_at' => now(),
                ]
            );
        }

        $progressPercent = $this->recomputeEnrollmentProgress($course, $enrollment);

        return response()->json([
            'ok' => true,
            'percentage' => $percentage,
            'progressPercent' => $progressPercent,
        ]);
    }

    public function completeLesson(Course $course, CourseLesson $lesson): JsonResponse
    {
        $this->assertLessonInPublishedCourse($course, $lesson);
        $this->authorize('viewLesson', [$course, $lesson]);

        $enrollment = $this->resolveEnrollment($course);

        if ($enrollment !== null) {
            LessonCompletion::query()->firstOrCreate(
                [
                    'user_id' => auth()->id(),
                    'course_lesson_id' => $lesson->id,
                ],
                [
                    'enrollment_id' => $enrollment->id,
                    'completed_at' => now(),
                ]
            );
        }

        $progressPercent = $this->recomputeEnrollmentProgress($course, $enrollment);

        return response()->json([
            'ok' => true,
            'progressPercent' => $progressPercent,
        ]);
    }

    private function resolveEnrollment(Course $course): ?Enrollment
    {
        return $course->enrollmentFor(auth()->user());
    }

    private function recomputeEnrollmentProgress(Course $course, ?Enrollment $enrollment): int
    {
        return $enrollment !== null ? CourseProgress::sync($enrollment) : 0;
    }
}

<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\Enrollment;
use App\Models\LessonCompletion;
use App\Models\LessonVideoProgress;
use App\Notifications\Lms\CourseCompletedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CoursePlayerProgressController extends Controller
{
    public function saveVideoProgress(Request $request, Course $course, CourseLesson $lesson): JsonResponse
    {
        abort_unless($course->status === 'published', 404);
        abort_unless((int) $lesson->course_id === (int) $course->id, 404);

        $enrollment = $this->resolveEnrollment($course);
        abort_unless($lesson->is_free || $enrollment !== null, 403);

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
        abort_unless($course->status === 'published', 404);
        abort_unless((int) $lesson->course_id === (int) $course->id, 404);

        $enrollment = $this->resolveEnrollment($course);
        abort_unless($lesson->is_free || $enrollment !== null, 403);

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
        return Enrollment::query()
            ->where('user_id', auth()->id())
            ->where('course_id', $course->id)
            ->whereNotIn('access_status', ['suspended', 'revoked'])
            ->first();
    }

    private function recomputeEnrollmentProgress(Course $course, ?Enrollment $enrollment): int
    {
        if ($enrollment === null) {
            return 0;
        }

        $lessonIds = CourseLesson::query()
            ->where('course_id', $course->id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        $totalLessons = max(1, (int) $lessonIds->count());

        $completedByMark = LessonCompletion::query()
            ->where('user_id', auth()->id())
            ->whereIn('course_lesson_id', $lessonIds)
            ->pluck('course_lesson_id')
            ->map(fn ($id) => (int) $id);

        $completedByVideo = LessonVideoProgress::query()
            ->where('user_id', auth()->id())
            ->whereIn('course_lesson_id', $lessonIds)
            ->where('percentage', '>=', LessonVideoProgress::COMPLETION_PERCENT_THRESHOLD)
            ->pluck('course_lesson_id')
            ->map(fn ($id) => (int) $id);

        $doneCount = $completedByMark
            ->merge($completedByVideo)
            ->unique()
            ->count();

        $percent = (int) min(100, round(($doneCount / $totalLessons) * 100));

        $wasIncomplete = $enrollment->access_status !== 'completed' && $enrollment->progress < 100;

        $enrollment->update([
            'progress' => $percent,
            'access_status' => $percent >= 100 ? 'completed' : 'active',
            'completed_at' => $percent >= 100 ? now() : null,
        ]);

        if ($wasIncomplete && $percent >= 100) {
            $user = auth()->user();
            if ($user !== null) {
                $user->notify(new CourseCompletedNotification($course));
            }
        }

        return $percent;
    }
}

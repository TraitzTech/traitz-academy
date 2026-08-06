<?php

namespace App\Support\Lms;

use App\Models\CourseLesson;
use App\Models\Enrollment;
use App\Models\LessonCompletion;
use App\Models\LessonVideoProgress;
use App\Notifications\Lms\CourseCompletedNotification;
use Illuminate\Support\Collection;

/**
 * Single source of truth for course-completion progress.
 *
 * A lesson counts as "completed" for a user when either an explicit
 * {@see LessonCompletion} mark exists (manual "mark complete", or a passed
 * quiz lesson) or the user's video watch progress has crossed the completion
 * threshold. Keep every progress calculation flowing through this class so the
 * denominator (which lessons count) and numerator (which are done) can never
 * drift between call sites.
 */
class CourseProgress
{
    /**
     * IDs of every lesson that counts toward completion for the course.
     */
    public static function lessonIds(int $courseId): Collection
    {
        return CourseLesson::query()
            ->where('course_id', $courseId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    /**
     * IDs of the course lessons the user has completed (marks + watched video).
     */
    public static function completedLessonIds(int $courseId, int $userId): Collection
    {
        $lessonIds = self::lessonIds($courseId);

        if ($lessonIds->isEmpty()) {
            return collect();
        }

        $fromMarks = LessonCompletion::query()
            ->where('user_id', $userId)
            ->whereIn('course_lesson_id', $lessonIds)
            ->pluck('course_lesson_id')
            ->map(fn ($id) => (int) $id);

        $fromVideo = LessonVideoProgress::query()
            ->where('user_id', $userId)
            ->whereIn('course_lesson_id', $lessonIds)
            ->where('percentage', '>=', LessonVideoProgress::COMPLETION_PERCENT_THRESHOLD)
            ->pluck('course_lesson_id')
            ->map(fn ($id) => (int) $id);

        return $fromMarks->merge($fromVideo)->unique()->values();
    }

    /**
     * Completion percentage (0-100) for the user on the course.
     */
    public static function percent(int $courseId, int $userId): int
    {
        $total = max(1, self::lessonIds($courseId)->count());
        $done = self::completedLessonIds($courseId, $userId)->count();

        return (int) min(100, round(($done / $total) * 100));
    }

    /**
     * Recompute the enrollment's progress, persist it, and fire the completion
     * notification once when the learner first reaches 100%.
     */
    public static function sync(Enrollment $enrollment): int
    {
        $percent = self::percent((int) $enrollment->course_id, (int) $enrollment->user_id);

        $wasIncomplete = $enrollment->access_status !== 'completed' && (int) $enrollment->progress < 100;

        $enrollment->update([
            'progress' => $percent,
            'access_status' => $percent >= 100 ? 'completed' : 'active',
            'completed_at' => $percent >= 100 ? now() : null,
        ]);

        if ($wasIncomplete && $percent >= 100) {
            $enrollment->loadMissing('user', 'course');
            if ($enrollment->user !== null && $enrollment->course !== null) {
                $enrollment->user->notify(new CourseCompletedNotification($enrollment->course));
            }
        }

        return $percent;
    }
}

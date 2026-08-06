<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Lms\Concerns\InteractsWithCourseContent;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\LessonNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LessonNoteController extends Controller
{
    use InteractsWithCourseContent;

    public function upsertLessonNote(Request $request, Course $course, CourseLesson $lesson): JsonResponse
    {
        $this->assertLessonInPublishedCourse($course, $lesson);
        $this->authorize('viewLesson', [$course, $lesson]);

        $payload = $request->validate([
            'content' => ['nullable', 'string', 'max:10000'],
        ]);

        $content = trim((string) ($payload['content'] ?? ''));
        $userId = (int) $request->user()->id;

        $note = LessonNote::query()
            ->where('user_id', $userId)
            ->where('course_lesson_id', (int) $lesson->id)
            ->whereNull('timestamp_seconds')
            ->first();

        if ($content === '') {
            if ($note) {
                $note->delete();
            }

            return response()->json([
                'saved' => true,
                'note' => null,
            ]);
        }

        if (! $note) {
            $note = new LessonNote([
                'user_id' => $userId,
                'course_lesson_id' => (int) $lesson->id,
            ]);
        }

        $note->content = $content;
        $note->timestamp = null;
        $note->timestamp_seconds = null;
        $note->save();

        return response()->json([
            'saved' => true,
            'note' => $this->mapNote($note),
        ]);
    }

    public function storeTimestampNote(Request $request, Course $course, CourseLesson $lesson): JsonResponse
    {
        $this->assertLessonInPublishedCourse($course, $lesson);
        $this->authorize('viewLesson', [$course, $lesson]);

        $payload = $request->validate([
            'content' => ['required', 'string', 'max:10000'],
            'timestamp_seconds' => ['required', 'integer', 'min:0'],
        ]);

        $seconds = (int) $payload['timestamp_seconds'];
        $note = LessonNote::query()->create([
            'user_id' => (int) $request->user()->id,
            'course_lesson_id' => (int) $lesson->id,
            'content' => trim((string) $payload['content']),
            'timestamp' => $this->formatTimestamp($seconds),
            'timestamp_seconds' => $seconds,
        ]);

        return response()->json([
            'saved' => true,
            'note' => $this->mapNote($note),
        ], 201);
    }

    private function formatTimestamp(int $seconds): string
    {
        $seconds = max(0, $seconds);
        $hours = (int) floor($seconds / 3600);
        $minutes = (int) floor(($seconds % 3600) / 60);
        $remainingSeconds = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $remainingSeconds);
        }

        return sprintf('%d:%02d', $minutes, $remainingSeconds);
    }

    private function mapNote(LessonNote $note): array
    {
        return [
            'id' => (int) $note->id,
            'content' => $note->content,
            'timestamp' => $note->timestamp,
            'timestamp_seconds' => $note->timestamp_seconds !== null ? (int) $note->timestamp_seconds : null,
            'updated_at' => optional($note->updated_at)->toIso8601String(),
        ];
    }
}

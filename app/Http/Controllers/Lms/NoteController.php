<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Models\LessonNote;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NoteController extends Controller
{
    public function index(Request $request): Response
    {
        $notes = LessonNote::query()
            ->where('lesson_notes.user_id', (int) $request->user()->id)
            ->join('course_lessons', 'course_lessons.id', '=', 'lesson_notes.course_lesson_id')
            ->join('courses', 'courses.id', '=', 'course_lessons.course_id')
            ->join('course_sections', 'course_sections.id', '=', 'course_lessons.course_section_id')
            ->where('courses.status', 'published')
            ->orderBy('courses.title')
            ->orderBy('course_sections.sort_order')
            ->orderBy('course_lessons.sort_order')
            ->orderBy('lesson_notes.created_at')
            ->select('lesson_notes.*')
            ->with([
                'lesson:id,course_id,course_section_id,title',
                'lesson.course:id,title',
            ])
            ->get()
            ->map(fn (LessonNote $note) => [
                'id' => (int) $note->id,
                'content' => $note->content,
                'timestamp' => $note->timestamp,
                'timestamp_seconds' => $note->timestamp_seconds !== null ? (int) $note->timestamp_seconds : null,
                'updated_at' => optional($note->updated_at)->toIso8601String(),
                'lesson' => [
                    'id' => (int) $note->lesson->id,
                    'title' => $note->lesson->title,
                ],
                'course' => [
                    'id' => (int) $note->lesson->course->id,
                    'title' => $note->lesson->course->title,
                ],
            ])
            ->groupBy(fn (array $note) => $note['course']['id'])
            ->map(function ($groupedNotes): array {
                $first = $groupedNotes->first();

                return [
                    'course' => $first['course'],
                    'notes' => $groupedNotes->values(),
                ];
            })
            ->values();

        return Inertia::render('Lms/Notes/Index', [
            'groups' => $notes,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseSection;
use App\Models\LessonAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LessonAttachmentController extends Controller
{
    public function store(Request $request, Course $course, CourseSection $section, CourseLesson $lesson): RedirectResponse
    {
        $this->authorise($course, $section, $lesson);

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:20480'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $file = $validated['file'];
        $path = $file->store("lesson-attachments/{$course->id}/{$lesson->id}", 'public');

        $mime = $file->getMimeType();
        $ext = strtolower($file->getClientOriginalExtension() ?: '');
        $fileType = $ext !== '' ? $ext : (is_string($mime) && str_contains($mime, '/') ? explode('/', $mime)[1] : null);

        $maxSort = (int) ($lesson->attachments()->max('sort_order') ?? -1);

        LessonAttachment::query()->create([
            'course_lesson_id' => $lesson->id,
            'name' => $validated['name'] ?: $file->getClientOriginalName(),
            'file_url' => $path,
            'file_type' => $fileType,
            'file_size' => $file->getSize(),
            'sort_order' => $maxSort + 1,
        ]);

        return back()->with('success', 'Resource uploaded.');
    }

    public function destroy(Course $course, CourseSection $section, CourseLesson $lesson, LessonAttachment $attachment): RedirectResponse
    {
        $this->authorise($course, $section, $lesson);
        abort_unless((int) $attachment->course_lesson_id === (int) $lesson->id, 404);

        if (! str_starts_with((string) $attachment->file_url, 'http://') && ! str_starts_with((string) $attachment->file_url, 'https://')) {
            Storage::disk('public')->delete($attachment->file_url);
        }

        $attachment->delete();

        $lesson->attachments()->ordered()->get()->values()->each(function (LessonAttachment $a, int $index): void {
            $a->update(['sort_order' => $index]);
        });

        return back()->with('success', 'Resource removed.');
    }

    public function reorder(Request $request, Course $course, CourseSection $section, CourseLesson $lesson): RedirectResponse
    {
        $this->authorise($course, $section, $lesson);

        $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:lesson_attachments,id'],
        ]);

        $allowed = $lesson->attachments()->pluck('id')->map(fn ($id) => (int) $id)->all();

        foreach ($request->order as $index => $attachmentId) {
            $id = (int) $attachmentId;
            if (! in_array($id, $allowed, true)) {
                abort(403);
            }
            LessonAttachment::query()
                ->where('id', $id)
                ->where('course_lesson_id', $lesson->id)
                ->update(['sort_order' => $index]);
        }

        return back()->with('success', 'Resources reordered.');
    }

    private function authorise(Course $course, CourseSection $section, CourseLesson $lesson): void
    {
        if ($course->instructor_id !== auth()->id()) {
            abort(403);
        }
        if ($section->course_id !== $course->id) {
            abort(403);
        }
        if ($lesson->course_section_id !== $section->id || (int) $lesson->course_id !== (int) $course->id) {
            abort(403);
        }
    }
}

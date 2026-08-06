<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CourseLessonController extends Controller
{
    public function store(Request $request, Course $course, CourseSection $section): RedirectResponse
    {
        $this->authorise($course);
        $this->authoriseSection($course, $section);

        $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'type'        => ['required', 'in:video,text,quiz'],
            'description' => ['nullable', 'string', 'max:1000'],
            'content'     => ['nullable', 'string'],
            'duration'    => ['nullable', 'string', 'max:20'],
            'is_free'     => ['boolean'],
        ]);

        $maxOrder = $section->lessons()->max('sort_order') ?? -1;

        $section->lessons()->create([
            'course_id'   => $course->id,
            'title'       => $request->title,
            'type'        => $request->type,
            'description' => $request->description,
            'content'     => $request->content,
            'duration'    => $request->duration,
            'is_free'     => $request->boolean('is_free'),
            'sort_order'  => $maxOrder + 1,
        ]);

        return back()->with('success', 'Lesson added.');
    }

    public function update(Request $request, Course $course, CourseSection $section, CourseLesson $lesson): RedirectResponse
    {
        $this->authorise($course);
        $this->authoriseSection($course, $section);
        $this->authoriseLesson($section, $lesson);

        $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'type'        => ['required', 'in:video,text,quiz'],
            'description' => ['nullable', 'string', 'max:1000'],
            'content'     => ['nullable', 'string'],
            'duration'    => ['nullable', 'string', 'max:20'],
            'is_free'     => ['boolean'],
        ]);

        $lesson->update([
            'title'       => $request->title,
            'type'        => $request->type,
            'description' => $request->description,
            'content'     => $request->content,
            'duration'    => $request->duration,
            'is_free'     => $request->boolean('is_free'),
        ]);

        return back()->with('success', 'Lesson updated.');
    }

    public function destroy(Course $course, CourseSection $section, CourseLesson $lesson): RedirectResponse
    {
        $this->authorise($course);
        $this->authoriseSection($course, $section);
        $this->authoriseLesson($section, $lesson);

        $lesson->delete();

        // Re-index sort_order within the section
        $section->lessons()->ordered()->each(function ($l, $index) {
            $l->update(['sort_order' => $index]);
        });

        return back()->with('success', 'Lesson deleted.');
    }

    public function reorder(Request $request, Course $course, CourseSection $section): RedirectResponse
    {
        $this->authorise($course);
        $this->authoriseSection($course, $section);

        $request->validate([
            'order'   => ['required', 'array'],
            'order.*' => ['integer', 'exists:course_lessons,id'],
        ]);

        foreach ($request->order as $index => $lessonId) {
            $section->lessons()->where('id', $lessonId)->update(['sort_order' => $index]);
        }

        return back()->with('success', 'Lessons reordered.');
    }

    private function authorise(Course $course): void
    {
        if ($course->instructor_id !== auth()->id()) {
            abort(403);
        }
    }

    private function authoriseSection(Course $course, CourseSection $section): void
    {
        if ($section->course_id !== $course->id) {
            abort(403);
        }
    }

    private function authoriseLesson(CourseSection $section, CourseLesson $lesson): void
    {
        if ($lesson->course_section_id !== $section->id) {
            abort(403);
        }
    }
}

<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CourseSectionController extends Controller
{
    public function store(Request $request, Course $course): RedirectResponse
    {
        $this->authorise($course);

        $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $maxOrder = $course->sections()->max('sort_order') ?? -1;

        $course->sections()->create([
            'title'       => $request->title,
            'description' => $request->description,
            'sort_order'  => $maxOrder + 1,
        ]);

        return back()->with('success', 'Section added.');
    }

    public function update(Request $request, Course $course, CourseSection $section): RedirectResponse
    {
        $this->authorise($course);
        $this->authoriseSection($course, $section);

        $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $section->update([
            'title'       => $request->title,
            'description' => $request->description,
        ]);

        return back()->with('success', 'Section updated.');
    }

    public function destroy(Course $course, CourseSection $section): RedirectResponse
    {
        $this->authorise($course);
        $this->authoriseSection($course, $section);

        $section->delete();

        // Re-index sort_order
        $course->sections()->ordered()->each(function ($s, $index) {
            $s->update(['sort_order' => $index]);
        });

        return back()->with('success', 'Section deleted.');
    }

    public function reorder(Request $request, Course $course): RedirectResponse
    {
        $this->authorise($course);

        $request->validate([
            'order'   => ['required', 'array'],
            'order.*' => ['integer', 'exists:course_sections,id'],
        ]);

        foreach ($request->order as $index => $sectionId) {
            $course->sections()->where('id', $sectionId)->update(['sort_order' => $index]);
        }

        return back()->with('success', 'Sections reordered.');
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
}

<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function index(Request $request): Response
    {
        $tutorId = $request->user()->id;
        $search = trim((string) $request->input('search', ''));
        $courseId = $request->filled('course') ? (int) $request->input('course') : null;
        $status = $request->input('status');

        $courses = Course::query()
            ->where('instructor_id', $tutorId)
            ->orderBy('title')
            ->get(['id', 'title']);

        $query = Enrollment::query()
            ->whereHas('course', fn ($q) => $q->where('instructor_id', $tutorId))
            ->with(['user:id,name,email', 'course:id,title']);

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', $like)->orWhere('email', 'like', $like));
        }

        if ($courseId) {
            if (! $courses->pluck('id')->contains($courseId)) {
                $courseId = null;
            } else {
                $query->where('course_id', $courseId);
            }
        }

        if (is_string($status) && in_array($status, ['active', 'suspended', 'revoked', 'completed'], true)) {
            $query->where('access_status', $status);
        }

        $enrollments = $query
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Tutor/Students/Index', [
            'enrollments' => $enrollments,
            'courses' => $courses,
            'filters' => [
                'search' => $search !== '' ? $search : null,
                'course' => $courseId,
                'status' => is_string($status) ? $status : null,
            ],
        ]);
    }
}

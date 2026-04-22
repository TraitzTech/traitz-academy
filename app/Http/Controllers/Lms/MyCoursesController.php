<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MyCoursesController extends Controller
{
    public function index(Request $request): Response
    {
        $enrollments = Enrollment::query()
            ->where('user_id', auth()->id())
            ->with('course:id,title,slug,short_description,cover_image,level,duration,instructor_id,category_id',
                'course.instructor:id,name',
                'course.category:id,name,slug',
                'course.quizzes:id,course_id,title')
            ->when($request->status, fn ($q) => $q->where('access_status', $request->status))
            ->when($request->search, fn ($q) => $q->whereHas('course', fn ($c) => $c->where('title', 'like', "%{$request->search}%")))
            ->latest('enrolled_at')
            ->paginate(12)
            ->withQueryString();

        $enrollments->getCollection()->transform(function (Enrollment $enrollment) {
            $firstQuizId = optional($enrollment->course?->quizzes?->first())->id;
            if ($enrollment->course) {
                $enrollment->course->setAttribute('first_quiz_id', $firstQuizId);
                unset($enrollment->course->quizzes);
            }

            return $enrollment;
        });

        $stats = [
            'total' => Enrollment::where('user_id', auth()->id())->count(),
            'active' => Enrollment::where('user_id', auth()->id())->active()->count(),
            'completed' => Enrollment::where('user_id', auth()->id())->completed()->count(),
        ];

        return Inertia::render('Lms/MyCourses', [
            'enrollments' => $enrollments,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status']),
        ]);
    }
}

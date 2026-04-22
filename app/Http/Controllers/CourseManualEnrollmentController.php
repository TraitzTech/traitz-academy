<?php

namespace App\Http\Controllers;

use App\Actions\Courses\GrantManualCourseEnrollment;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CourseManualEnrollmentController extends Controller
{
    public function store(Request $request, Course $course, GrantManualCourseEnrollment $grant): RedirectResponse
    {
        return $grant->handle($request, $course, $request->user());
    }
}

<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Notifications\Lms\CourseEnrollmentConfirmedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class CourseEnrollmentController extends Controller
{
    public function store(Course $course): RedirectResponse
    {
        abort_unless($course->status === 'published', 404);

        $userId = auth()->id();

        $effectivePrice = $course->effectivePrice();

        if ($effectivePrice > 0) {
            return redirect()
                ->route('lms.courses.checkout', $course)
                ->with('info', 'Complete payment to enroll in this course.');
        }

        $paymentType = 'free';

        $created = false;
        $reactivated = false;

        DB::transaction(function () use ($course, $userId, $paymentType, &$created, &$reactivated) {
            $enrollment = Enrollment::query()->firstOrCreate(
                [
                    'user_id' => $userId,
                    'course_id' => $course->id,
                ],
                [
                    'instalment_plan_id' => null,
                    'payment_type' => $paymentType,
                    'access_status' => 'active',
                    'progress' => 0,
                ]
            );

            $created = $enrollment->wasRecentlyCreated;

            if ($created) {
                $course->increment('enrolled_count');

                return;
            }

            if (in_array($enrollment->access_status, ['suspended', 'revoked'], true)) {
                $enrollment->update([
                    'payment_type' => $paymentType,
                    'access_status' => 'active',
                ]);
                $reactivated = true;
            }
        });

        if (! $created && ! $reactivated) {
            return back()->with('info', 'You are already enrolled in this course.');
        }

        $user = auth()->user();
        if ($user !== null) {
            $user->notify(new CourseEnrollmentConfirmedNotification($course));
        }

        $message = $reactivated
            ? 'Your enrollment has been restored. Continue in My Courses.'
            : 'You have successfully enrolled. Start learning in My Courses.';

        return redirect()
            ->route('lms.my-courses')
            ->with('success', $message);
    }
}

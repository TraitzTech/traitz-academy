<?php

namespace App\Actions\Courses;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Notifications\Lms\CourseEnrollmentConfirmedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GrantManualCourseEnrollment
{
    public function handle(Request $request, Course $course, User $actor): RedirectResponse
    {
        $this->authorizeActor($course, $actor);

        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $student = User::query()->where('email', $validated['email'])->firstOrFail();

        if ((int) $student->id === (int) $actor->id) {
            return back()->with('error', 'You cannot grant yourself access from this screen. Use the course catalogue as a learner, or ask another staff member.');
        }

        $created = false;
        $reactivated = false;
        $alreadyActive = false;
        $alreadyCompleted = false;

        DB::transaction(function () use ($course, $student, &$created, &$reactivated, &$alreadyActive, &$alreadyCompleted) {
            $lockedCourse = Course::query()->whereKey($course->id)->lockForUpdate()->firstOrFail();

            $enrollment = Enrollment::query()
                ->where('user_id', $student->id)
                ->where('course_id', $lockedCourse->id)
                ->lockForUpdate()
                ->first();

            if ($enrollment === null) {
                Enrollment::query()->create([
                    'user_id' => $student->id,
                    'course_id' => $lockedCourse->id,
                    'instalment_plan_id' => null,
                    'payment_type' => 'admin_granted',
                    'access_status' => 'active',
                    'progress' => 0,
                ]);
                $lockedCourse->increment('enrolled_count');
                $created = true;

                return;
            }

            if ($enrollment->access_status === 'completed') {
                $alreadyCompleted = true;

                return;
            }

            if ($enrollment->access_status === 'active') {
                $alreadyActive = true;

                return;
            }

            if (in_array($enrollment->access_status, ['suspended', 'revoked'], true)) {
                $enrollment->update([
                    'payment_type' => 'admin_granted',
                    'access_status' => 'active',
                ]);
                $reactivated = true;
            }
        });

        if ($alreadyCompleted) {
            return back()->with('info', 'This learner has already completed this course.');
        }

        if ($alreadyActive) {
            return back()->with('info', 'This learner is already enrolled with active access.');
        }

        if ($reactivated) {
            $student->notify(new CourseEnrollmentConfirmedNotification($course));

            return back()->with('success', 'Enrollment restored. The learner can access the course again.');
        }

        if ($created) {
            $student->notify(new CourseEnrollmentConfirmedNotification($course));

            return back()->with('success', 'Learner enrolled successfully. They will see this course in My Courses.');
        }

        return back()->with('info', 'No changes were made.');
    }

    private function authorizeActor(Course $course, User $actor): void
    {
        if ($actor->canManuallyEnrollStudentsInCourse($course)) {
            return;
        }

        if ($actor->canAccessAdminPanel()) {
            abort(422, 'Manual enrollment is only available for courses that are not archived.');
        }

        if ($actor->isTutor()) {
            abort_unless(
                (int) $course->instructor_id === (int) $actor->id,
                403,
                'You can only enroll learners in your own courses.'
            );
            abort(422, 'Publish the course before enrolling learners manually.');
        }

        abort(403, 'You are not allowed to enroll learners in this course.');
    }
}

<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Notifications\Lms\CourseEnrollmentConfirmedNotification;
use Illuminate\Support\Facades\Notification;

function makeEnrollableCourse(array $overrides = []): Course
{
    return Course::query()->create(array_merge([
        'instructor_id' => null,
        'category_id' => null,
        'title' => 'Enrollment Test Course',
        'slug' => 'enrollment-test-course-'.uniqid(),
        'short_description' => str_repeat('a', 50),
        'description' => null,
        'level' => 'beginner',
        'status' => 'published',
        'price' => 0,
        'sale_price' => null,
        'duration' => null,
        'is_featured' => false,
    ], $overrides));
}

it('allows an executive admin to manually enroll a student and notifies them', function () {
    Notification::fake();

    $admin = User::factory()->create(['role' => User::ROLE_CTO]);
    $student = User::factory()->create();
    $course = makeEnrollableCourse();

    $response = $this->actingAs($admin)->post(route('admin.courses.enroll-student', $course), [
        'email' => $student->email,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $enrollment = Enrollment::query()->where('user_id', $student->id)->where('course_id', $course->id)->first();
    expect($enrollment)->not->toBeNull()
        ->and($enrollment->access_status)->toBe('active')
        ->and($enrollment->payment_type)->toBe('admin_granted');

    expect($course->fresh()->enrolled_count)->toBe(1);

    Notification::assertSentTo(
        $student,
        CourseEnrollmentConfirmedNotification::class,
        function ($notification) use ($course) {
            $data = $notification->toArray($notification);

            return $notification->course->id === $course->id
                && $data['course_id'] === $course->id
                && $data['body'] === $course->title
                && $data['type'] === 'course_enrolment_confirmed';
        }
    );
});

it('allows a program coordinator to manually enroll a student', function () {
    Notification::fake();

    $coordinator = User::factory()->create(['role' => User::ROLE_PROGRAM_COORDINATOR]);
    $student = User::factory()->create();
    $course = makeEnrollableCourse(['status' => 'draft']);

    $response = $this->actingAs($coordinator)->post(route('admin.courses.enroll-student', $course), [
        'email' => $student->email,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect(Enrollment::query()->where('user_id', $student->id)->where('course_id', $course->id)->exists())->toBeTrue();

    Notification::assertSentTo($student, CourseEnrollmentConfirmedNotification::class);
});

it('allows a tutor to enroll a student only in their own published course', function () {
    Notification::fake();

    $tutor = User::factory()->create(['role' => User::ROLE_TUTOR]);
    $otherTutor = User::factory()->create(['role' => User::ROLE_TUTOR]);
    $student = User::factory()->create();

    $ownCourse = makeEnrollableCourse(['instructor_id' => $tutor->id, 'status' => 'published']);
    $othersCourse = makeEnrollableCourse(['instructor_id' => $otherTutor->id, 'status' => 'published']);

    $this->actingAs($tutor)
        ->post(route('tutor.courses.enroll-student', $othersCourse), ['email' => $student->email])
        ->assertForbidden();

    $response = $this->actingAs($tutor)
        ->post(route('tutor.courses.enroll-student', $ownCourse), ['email' => $student->email]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect(Enrollment::query()->where('user_id', $student->id)->where('course_id', $ownCourse->id)->exists())->toBeTrue();
    Notification::assertSentTo($student, CourseEnrollmentConfirmedNotification::class);
});

it('prevents a tutor from enrolling students in their own unpublished course', function () {
    $tutor = User::factory()->create(['role' => User::ROLE_TUTOR]);
    $student = User::factory()->create();
    $course = makeEnrollableCourse(['instructor_id' => $tutor->id, 'status' => 'draft']);

    $this->actingAs($tutor)
        ->post(route('tutor.courses.enroll-student', $course), ['email' => $student->email])
        ->assertStatus(422);

    expect(Enrollment::query()->where('user_id', $student->id)->where('course_id', $course->id)->exists())->toBeFalse();
});

it('prevents a plain student from manually enrolling anyone', function () {
    $student = User::factory()->create();
    $target = User::factory()->create();
    $course = makeEnrollableCourse();

    $this->actingAs($student)
        ->post(route('admin.courses.enroll-student', $course), ['email' => $target->email])
        ->assertForbidden();
});

it('does not duplicate or re-notify an already active enrollment', function () {
    Notification::fake();

    $admin = User::factory()->create(['role' => User::ROLE_CTO]);
    $student = User::factory()->create();
    $course = makeEnrollableCourse();

    Enrollment::query()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'payment_type' => 'full',
        'access_status' => 'active',
        'progress' => 10,
    ]);

    $response = $this->actingAs($admin)->post(route('admin.courses.enroll-student', $course), [
        'email' => $student->email,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('info');

    expect(Enrollment::query()->where('user_id', $student->id)->where('course_id', $course->id)->count())->toBe(1);
    Notification::assertNothingSent();
});

it('reactivates a revoked enrollment and re-notifies the student', function () {
    Notification::fake();

    $admin = User::factory()->create(['role' => User::ROLE_CTO]);
    $student = User::factory()->create();
    $course = makeEnrollableCourse();

    $enrollment = Enrollment::query()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'payment_type' => 'full',
        'access_status' => 'revoked',
        'progress' => 40,
    ]);

    $response = $this->actingAs($admin)->post(route('admin.courses.enroll-student', $course), [
        'email' => $student->email,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect($enrollment->fresh()->access_status)->toBe('active');
    Notification::assertSentTo($student, CourseEnrollmentConfirmedNotification::class);
});

it('requires the email to belong to an existing user', function () {
    $admin = User::factory()->create(['role' => User::ROLE_CTO]);
    $course = makeEnrollableCourse();

    $this->actingAs($admin)
        ->post(route('admin.courses.enroll-student', $course), ['email' => 'nobody@example.com'])
        ->assertSessionHasErrors('email');
});

it('always sends the enrolment confirmation email regardless of opt-out preferences', function () {
    $admin = User::factory()->create(['role' => User::ROLE_CTO]);
    $student = User::factory()->create([
        'notification_preferences' => ['enrolment_confirmation' => false],
    ]);
    $course = makeEnrollableCourse();

    $this->actingAs($admin)->post(route('admin.courses.enroll-student', $course), [
        'email' => $student->email,
    ]);

    $notification = new CourseEnrollmentConfirmedNotification($course);
    expect($notification->via($student))->toContain('database')->toContain('mail');
});

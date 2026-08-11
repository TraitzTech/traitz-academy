<?php

use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\LessonCompletion;
use App\Models\Quiz;
use App\Models\User;
use App\Notifications\Lms\CourseCompletedNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Covers the centralized course/lesson authorization (CoursePolicy) and the
 * single-source progress pipeline (CourseProgress).
 */
function makeCourse(array $courseState = []): array
{
    $owner = User::factory()->create(['role' => User::ROLE_TUTOR]);
    $course = Course::factory()->create(array_merge([
        'instructor_id' => $owner->id,
        'status' => 'published',
        'price' => 50000,
    ], $courseState));
    $section = CourseSection::factory()->create(['course_id' => $course->id]);

    $paidLesson = CourseLesson::factory()->create([
        'course_id' => $course->id,
        'course_section_id' => $section->id,
        'is_free' => false,
        'sort_order' => 1,
    ]);
    $freeLesson = CourseLesson::factory()->free()->create([
        'course_id' => $course->id,
        'course_section_id' => $section->id,
        'sort_order' => 2,
    ]);

    return compact('owner', 'course', 'section', 'paidLesson', 'freeLesson');
}

function enroll(User $user, Course $course, string $status = 'active'): Enrollment
{
    return Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'access_status' => $status,
    ]);
}

// ---------------------------------------------------------------------------
// Lesson access gating
// ---------------------------------------------------------------------------

it('redirects guests to login', function () {
    ['course' => $course, 'paidLesson' => $lesson] = makeCourse();

    $this->get(route('lms.courses.lessons.show', [$course, $lesson]))
        ->assertRedirect(route('login'));
});

it('allows an enrolled learner into a paid lesson', function () {
    ['course' => $course, 'paidLesson' => $lesson] = makeCourse();
    $student = User::factory()->create();
    enroll($student, $course);

    $this->actingAs($student)
        ->get(route('lms.courses.lessons.show', [$course, $lesson]))
        ->assertOk();
});

it('forbids a non-enrolled learner from a paid lesson', function () {
    ['course' => $course, 'paidLesson' => $lesson] = makeCourse();

    $this->actingAs(User::factory()->create())
        ->get(route('lms.courses.lessons.show', [$course, $lesson]))
        ->assertForbidden();
});

it('allows anyone authenticated into a free lesson', function () {
    ['course' => $course, 'freeLesson' => $lesson] = makeCourse();

    $this->actingAs(User::factory()->create())
        ->get(route('lms.courses.lessons.show', [$course, $lesson]))
        ->assertOk();
});

it('treats suspended and revoked enrollments as no access', function (string $status) {
    ['course' => $course, 'paidLesson' => $lesson] = makeCourse();
    $student = User::factory()->create();
    enroll($student, $course, $status);

    $this->actingAs($student)
        ->get(route('lms.courses.lessons.show', [$course, $lesson]))
        ->assertForbidden();
})->with(['suspended', 'revoked']);

it('lets the owning tutor view a paid lesson without enrolling', function () {
    ['owner' => $owner, 'course' => $course, 'paidLesson' => $lesson] = makeCourse();

    $this->actingAs($owner)
        ->get(route('lms.courses.lessons.show', [$course, $lesson]))
        ->assertOk();
});

it('lets an executive view a paid lesson without enrolling', function () {
    ['course' => $course, 'paidLesson' => $lesson] = makeCourse();
    $exec = User::factory()->create(['role' => User::ROLE_CTO]);

    $this->actingAs($exec)
        ->get(route('lms.courses.lessons.show', [$course, $lesson]))
        ->assertOk();
});

it('404s for an unpublished course', function () {
    ['course' => $course, 'paidLesson' => $lesson] = makeCourse(['status' => 'draft']);
    $student = User::factory()->create();
    enroll($student, $course);

    $this->actingAs($student)
        ->get(route('lms.courses.lessons.show', [$course, $lesson]))
        ->assertNotFound();
});

it('404s when the lesson belongs to a different course', function () {
    ['course' => $course] = makeCourse();
    ['paidLesson' => $otherLesson] = makeCourse();
    $student = User::factory()->create();
    enroll($student, $course);

    $this->actingAs($student)
        ->get(route('lms.courses.lessons.show', [$course, $otherLesson]))
        ->assertNotFound();
});

// ---------------------------------------------------------------------------
// The same gate protects notes and quizzes
// ---------------------------------------------------------------------------

it('forbids notes on a paid lesson without enrollment', function () {
    ['course' => $course, 'paidLesson' => $lesson] = makeCourse();

    $this->actingAs(User::factory()->create())
        ->putJson(route('lms.courses.lessons.notes.upsert', [$course, $lesson]), ['content' => 'hi'])
        ->assertForbidden();
});

it('forbids taking a quiz without enrollment', function () {
    ['course' => $course, 'paidLesson' => $lesson] = makeCourse();
    $quiz = Quiz::factory()->create(['course_id' => $course->id, 'lesson_id' => $lesson->id]);

    $this->actingAs(User::factory()->create())
        ->get(route('lms.quizzes.take', $quiz))
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// Progress pipeline
// ---------------------------------------------------------------------------

it('marks a lesson complete once video progress crosses the threshold', function () {
    ['course' => $course, 'paidLesson' => $lesson] = makeCourse();
    $student = User::factory()->create();
    enroll($student, $course);

    $this->actingAs($student)
        ->postJson(route('lms.courses.lessons.progress', [$course, $lesson]), [
            'watched_seconds' => 95,
            'duration_seconds' => 100,
        ])
        ->assertOk();

    expect(LessonCompletion::query()
        ->where('user_id', $student->id)
        ->where('course_lesson_id', $lesson->id)
        ->exists())->toBeTrue();
});

it('does not complete a lesson below the video threshold', function () {
    ['course' => $course, 'paidLesson' => $lesson] = makeCourse();
    $student = User::factory()->create();
    enroll($student, $course);

    $this->actingAs($student)
        ->postJson(route('lms.courses.lessons.progress', [$course, $lesson]), [
            'watched_seconds' => 10,
            'duration_seconds' => 100,
        ])
        ->assertOk();

    expect(LessonCompletion::query()->where('course_lesson_id', $lesson->id)->exists())->toBeFalse();
});

it('completes the course and notifies the learner when the last lesson is done', function () {
    Notification::fake();

    // Single-lesson course so one completion is 100%.
    $owner = User::factory()->create(['role' => User::ROLE_TUTOR]);
    $course = Course::factory()->create(['instructor_id' => $owner->id, 'status' => 'published', 'price' => 50000]);
    $section = CourseSection::factory()->create(['course_id' => $course->id]);
    $lesson = CourseLesson::factory()->create([
        'course_id' => $course->id,
        'course_section_id' => $section->id,
        'is_free' => false,
    ]);
    $student = User::factory()->create();
    $enrollment = enroll($student, $course);

    $this->actingAs($student)
        ->postJson(route('lms.courses.lessons.complete', [$course, $lesson]))
        ->assertOk();

    $enrollment->refresh();
    expect($enrollment->progress)->toBe(100)
        ->and($enrollment->access_status)->toBe('completed');

    Notification::assertSentTo($student, CourseCompletedNotification::class);
});

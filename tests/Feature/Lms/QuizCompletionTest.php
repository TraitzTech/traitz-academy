<?php

use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\LessonCompletion;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Notifications\Lms\CourseCompletedNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Regression: a course whose lessons include a quiz lesson could never reach
 * 100% completion, because passing the quiz never recorded a lesson completion.
 * Grading the quiz as passed must now complete the course.
 */
function makeQuizCourseFixture(): array
{
    $tutor = User::factory()->create(['role' => User::ROLE_TUTOR]);
    $student = User::factory()->create(['role' => User::ROLE_USER]);

    $course = Course::query()->create([
        'instructor_id' => $tutor->id,
        'title' => 'Test Course',
        'slug' => 'test-course-'.uniqid(),
        'short_description' => 'A course',
        'status' => 'published',
        'price' => 0,
        'published_at' => now(),
    ]);

    $section = CourseSection::query()->create([
        'course_id' => $course->id,
        'title' => 'Section 1',
        'sort_order' => 1,
    ]);

    $videoLesson = CourseLesson::query()->create([
        'course_id' => $course->id,
        'course_section_id' => $section->id,
        'title' => 'Intro Video',
        'type' => 'video',
        'sort_order' => 1,
    ]);

    $quizLesson = CourseLesson::query()->create([
        'course_id' => $course->id,
        'course_section_id' => $section->id,
        'title' => 'Final Quiz',
        'type' => 'quiz',
        'sort_order' => 2,
    ]);

    $quiz = Quiz::query()->create([
        'course_id' => $course->id,
        'lesson_id' => $quizLesson->id,
        'title' => 'Final Quiz',
        'pass_mark_percentage' => 60,
    ]);

    $enrollment = Enrollment::query()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'payment_type' => 'free',
        'access_status' => 'active',
        'progress' => 0,
    ]);

    // The learner has finished the video lesson but not the quiz yet (50%).
    LessonCompletion::query()->create([
        'user_id' => $student->id,
        'course_lesson_id' => $videoLesson->id,
        'enrollment_id' => $enrollment->id,
        'completed_at' => now(),
    ]);

    return compact('tutor', 'student', 'course', 'quizLesson', 'quiz', 'enrollment');
}

it('completes a quiz-containing course when the tutor grades the quiz as passed', function () {
    Notification::fake();
    ['tutor' => $tutor, 'student' => $student, 'quizLesson' => $quizLesson, 'quiz' => $quiz, 'enrollment' => $enrollment] = makeQuizCourseFixture();

    $attempt = QuizAttempt::query()->create([
        'user_id' => $student->id,
        'quiz_id' => $quiz->id,
        'answers' => [],
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    $this->actingAs($tutor)
        ->put(route('tutor.quizzes.attempts.grade', [$quiz, $attempt]), [
            'score_percentage' => 80,
            'passed' => true,
        ])
        ->assertRedirect();

    // The quiz lesson is now recorded as completed for the learner...
    expect(LessonCompletion::query()
        ->where('user_id', $student->id)
        ->where('course_lesson_id', $quizLesson->id)
        ->exists())->toBeTrue();

    // ...pushing the course to 100% and flipping the enrollment to completed.
    $enrollment->refresh();
    expect($enrollment->progress)->toBe(100)
        ->and($enrollment->access_status)->toBe('completed')
        ->and($enrollment->completed_at)->not->toBeNull();

    Notification::assertSentTo($student, CourseCompletedNotification::class);
});

it('recedes quiz-lesson completion when a passed attempt is regraded as failed', function () {
    ['tutor' => $tutor, 'student' => $student, 'quizLesson' => $quizLesson, 'quiz' => $quiz, 'enrollment' => $enrollment] = makeQuizCourseFixture();

    // Pre-existing pass.
    LessonCompletion::query()->create([
        'user_id' => $student->id,
        'course_lesson_id' => $quizLesson->id,
        'enrollment_id' => $enrollment->id,
        'completed_at' => now(),
    ]);

    $attempt = QuizAttempt::query()->create([
        'user_id' => $student->id,
        'quiz_id' => $quiz->id,
        'answers' => [],
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    $this->actingAs($tutor)
        ->put(route('tutor.quizzes.attempts.grade', [$quiz, $attempt]), [
            'score_percentage' => 30,
            'passed' => false,
        ])
        ->assertRedirect();

    expect(LessonCompletion::query()
        ->where('user_id', $student->id)
        ->where('course_lesson_id', $quizLesson->id)
        ->exists())->toBeFalse();

    $enrollment->refresh();
    expect($enrollment->access_status)->toBe('active')
        ->and($enrollment->progress)->toBeLessThan(100);
});

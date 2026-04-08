<?php

use App\Http\Controllers\CourseLessonVideoController;
use App\Http\Controllers\CourseManualEnrollmentController;
use App\Http\Controllers\Lms\AllCoursesController;
use App\Http\Controllers\Lms\CourseCatalogueController;
use App\Http\Controllers\Lms\CourseEnrollmentController;
use App\Http\Controllers\Lms\CoursePaymentController;
use App\Http\Controllers\Lms\MyCoursesController;
use App\Http\Controllers\Lms\QuizAttemptController as StudentQuizAttemptController;
use App\Http\Controllers\Tutor\CourseController as TutorCourseController;
use App\Http\Controllers\Tutor\CourseLessonController;
use App\Http\Controllers\Tutor\CourseSectionController;
use App\Http\Controllers\Tutor\DashboardController as TutorDashboardController;
use App\Http\Controllers\Tutor\LessonUploadController;
use App\Http\Controllers\Tutor\QuizAttemptController as TutorQuizAttemptController;
use App\Http\Controllers\Tutor\QuizBuilderController;
use Illuminate\Support\Facades\Route;

// Public course catalogue & detail
Route::get('/online-courses', [CourseCatalogueController::class, 'index'])->name('lms.catalogue');
Route::get('/online-courses/{course}', [CourseCatalogueController::class, 'show'])->name('lms.catalogue.show');
Route::get('/online-courses/{course}/lessons/{lesson}/preview', [CourseCatalogueController::class, 'preview'])->name('lms.catalogue.preview');

// Authenticated student routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard/courses', [AllCoursesController::class, 'index'])->name('lms.courses');
    Route::get('/dashboard/courses/{course}', [AllCoursesController::class, 'show'])->name('lms.courses.show');
    Route::get('/dashboard/courses/{course}/checkout', [CoursePaymentController::class, 'checkout'])->name('lms.courses.checkout');
    Route::post('/dashboard/courses/{course}/checkout', [CoursePaymentController::class, 'store'])->name('lms.courses.checkout.store');
    Route::get('/dashboard/course-payments/{coursePayment}', [CoursePaymentController::class, 'receipt'])->name('lms.course-payments.receipt');
    Route::get('/dashboard/my-courses', [MyCoursesController::class, 'index'])->name('lms.my-courses');
    Route::get('/dashboard/quizzes/{quiz}', [StudentQuizAttemptController::class, 'show'])->name('lms.quizzes.take');
    Route::patch('/dashboard/quizzes/{quiz}/attempts/{attempt}/progress', [StudentQuizAttemptController::class, 'saveProgress'])->name('lms.quizzes.progress');
    Route::post('/dashboard/quizzes/{quiz}/attempts/{attempt}/submit', [StudentQuizAttemptController::class, 'submit'])->name('lms.quizzes.submit');
    Route::get('/dashboard/quizzes/{quiz}/attempts/{attempt}/result', [StudentQuizAttemptController::class, 'result'])->name('lms.quizzes.result');
    Route::post('/online-courses/{course}/enroll', [CourseEnrollmentController::class, 'store'])->name('lms.courses.enroll');
});

// Tutor routes
Route::middleware(['auth', 'verified', 'tutor'])
    ->prefix('tutor')
    ->name('tutor.')
    ->group(function () {

        // Dashboard
        Route::get('dashboard', [TutorDashboardController::class, 'index'])->name('dashboard');

        // Lesson upload
        Route::get('lessons/upload', [LessonUploadController::class, 'index'])->name('lessons.upload');
        Route::post('lessons/upload', [LessonUploadController::class, 'store'])->name('lessons.upload.store');
        Route::delete('lessons/{lesson}', [LessonUploadController::class, 'destroy'])->name('lessons.destroy');

        // Course CRUD
        Route::get('courses', [TutorCourseController::class, 'index'])->name('courses.index');
        Route::get('courses/create', [TutorCourseController::class, 'create'])->name('courses.create');
        Route::post('courses', [TutorCourseController::class, 'store'])->name('courses.store');
        Route::get('courses/{course}/edit', [TutorCourseController::class, 'edit'])->name('courses.edit');
        Route::put('courses/{course}', [TutorCourseController::class, 'update'])->name('courses.update');
        Route::delete('courses/{course}', [TutorCourseController::class, 'destroy'])->name('courses.destroy');
        Route::post('courses/{course}/cover', [TutorCourseController::class, 'uploadCover'])->name('courses.cover');
        Route::post('courses/{course}/submit', [TutorCourseController::class, 'submit'])->name('courses.submit');
        Route::post('courses/{course}/enroll-student', [CourseManualEnrollmentController::class, 'store'])->name('courses.enroll-student');

        // Section CRUD
        Route::post('courses/{course}/sections', [CourseSectionController::class, 'store'])->name('courses.sections.store');
        Route::put('courses/{course}/sections/{section}', [CourseSectionController::class, 'update'])->name('courses.sections.update');
        Route::delete('courses/{course}/sections/{section}', [CourseSectionController::class, 'destroy'])->name('courses.sections.destroy');
        Route::post('courses/{course}/sections/reorder', [CourseSectionController::class, 'reorder'])->name('courses.sections.reorder');

        // Lesson CRUD
        Route::post('courses/{course}/sections/{section}/lessons', [CourseLessonController::class, 'store'])->name('courses.sections.lessons.store');
        Route::put('courses/{course}/sections/{section}/lessons/{lesson}', [CourseLessonController::class, 'update'])->name('courses.sections.lessons.update');
        Route::delete('courses/{course}/sections/{section}/lessons/{lesson}', [CourseLessonController::class, 'destroy'])->name('courses.sections.lessons.destroy');
        Route::post('courses/{course}/sections/{section}/lessons/reorder', [CourseLessonController::class, 'reorder'])->name('courses.sections.lessons.reorder');
        Route::post('courses/{course}/sections/{section}/lessons/{lesson}/video', [CourseLessonVideoController::class, 'store'])->name('courses.sections.lessons.video.store');

        // Quiz builder and attempts
        Route::get('courses/{course}/lessons/{lesson}/quiz', [QuizBuilderController::class, 'show'])->name('courses.lessons.quiz.builder');
        Route::put('courses/{course}/lessons/{lesson}/quiz', [QuizBuilderController::class, 'upsertMeta'])->name('courses.lessons.quiz.upsert');
        Route::post('quizzes/{quiz}/questions', [QuizBuilderController::class, 'storeQuestion'])->name('quizzes.questions.store');
        Route::put('quizzes/{quiz}/questions/{question}', [QuizBuilderController::class, 'updateQuestion'])->name('quizzes.questions.update');
        Route::delete('quizzes/{quiz}/questions/{question}', [QuizBuilderController::class, 'destroyQuestion'])->name('quizzes.questions.destroy');
        Route::post('quizzes/{quiz}/questions/reorder', [QuizBuilderController::class, 'reorderQuestions'])->name('quizzes.questions.reorder');
        Route::get('quizzes/{quiz}/attempts', [TutorQuizAttemptController::class, 'index'])->name('quizzes.attempts.index');
        Route::get('quizzes/{quiz}/attempts/{attempt}', [TutorQuizAttemptController::class, 'show'])->name('quizzes.attempts.show');
        Route::put('quizzes/{quiz}/attempts/{attempt}/grade', [TutorQuizAttemptController::class, 'grade'])->name('quizzes.attempts.grade');
    });

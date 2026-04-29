<?php

use App\Http\Controllers\CourseLessonVideoController;
use App\Http\Controllers\CourseManualEnrollmentController;
use App\Http\Controllers\Admin\EnrollmentController as AdminEnrollmentController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\CoursePricingController;
use App\Http\Controllers\Admin\LiveClassController as AdminLiveClassController;
use App\Http\Controllers\Admin\LmsDiscussionController as AdminLmsDiscussionController;
use App\Http\Controllers\Admin\LmsReportController as AdminLmsReportController;
use App\Http\Controllers\Admin\QuizAttemptController as AdminQuizAttemptController;
use App\Http\Controllers\Lms\AllCoursesController;
use App\Http\Controllers\Lms\AssignmentController;
use App\Http\Controllers\Lms\BroadcastNotificationController;
use App\Http\Controllers\Lms\CourseCatalogueController;
use App\Http\Controllers\Lms\CourseEnrollmentController;
use App\Http\Controllers\Lms\DiscussionController as StudentDiscussionController;
use App\Http\Controllers\Lms\LiveClassController as StudentLiveClassController;
use App\Http\Controllers\Lms\ScheduleController;
use App\Http\Controllers\Lms\StudentScheduleController;
use App\Http\Controllers\Lms\CoursePaymentController;
use App\Http\Controllers\Lms\CoursePlayerProgressController;
use App\Http\Controllers\Lms\LessonDiscussionController;
use App\Http\Controllers\Lms\LessonNoteController;
use App\Http\Controllers\Lms\NoteController;
use App\Http\Controllers\Lms\MyCoursesController;
use App\Http\Controllers\Lms\QuizAttemptController as StudentQuizAttemptController;
use App\Http\Controllers\Tutor\CourseController as TutorCourseController;
use App\Http\Controllers\Tutor\CourseLessonController;
use App\Http\Controllers\Tutor\CourseSectionController;
use App\Http\Controllers\Tutor\DashboardController as TutorDashboardController;
use App\Http\Controllers\Tutor\LessonAttachmentController;
use App\Http\Controllers\Tutor\LessonUploadController;
use App\Http\Controllers\Tutor\DiscussionController as TutorDiscussionController;
use App\Http\Controllers\Tutor\LiveClassController as TutorLiveClassController;
use App\Http\Controllers\Tutor\QuizAttemptController as TutorQuizAttemptController;
use App\Http\Controllers\Tutor\QuizBuilderController;
use App\Http\Controllers\Tutor\StudentController as TutorStudentController;
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
    Route::get('/dashboard/courses/{course}/lessons/{lesson}', [CourseCatalogueController::class, 'lesson'])->name('lms.courses.lessons.show');
    Route::post('/dashboard/courses/{course}/lessons/{lesson}/progress', [CoursePlayerProgressController::class, 'saveVideoProgress'])->name('lms.courses.lessons.progress');
    Route::post('/dashboard/courses/{course}/lessons/{lesson}/complete', [CoursePlayerProgressController::class, 'completeLesson'])->name('lms.courses.lessons.complete');
    Route::post('/dashboard/courses/{course}/lessons/{lesson}/discussions', [LessonDiscussionController::class, 'store'])->name('lms.courses.lessons.discussions.store');
    Route::delete('/dashboard/courses/{course}/lessons/{lesson}/discussions/{discussion}', [LessonDiscussionController::class, 'destroy'])->name('lms.courses.lessons.discussions.destroy');
    Route::post('/dashboard/courses/{course}/lessons/{lesson}/discussions/{discussion}/upvote', [LessonDiscussionController::class, 'toggleUpvote'])->name('lms.courses.lessons.discussions.upvote');
    Route::post('/dashboard/courses/{course}/lessons/{lesson}/discussions/{discussion}/accept', [LessonDiscussionController::class, 'acceptAnswer'])->name('lms.courses.lessons.discussions.accept');
    Route::put('/dashboard/courses/{course}/lessons/{lesson}/notes', [LessonNoteController::class, 'upsertLessonNote'])->name('lms.courses.lessons.notes.upsert');
    Route::post('/dashboard/courses/{course}/lessons/{lesson}/notes/timestamp', [LessonNoteController::class, 'storeTimestampNote'])->name('lms.courses.lessons.notes.timestamp.store');
    Route::get('/dashboard/quizzes/{quiz}', [StudentQuizAttemptController::class, 'show'])->name('lms.quizzes.take');
    Route::patch('/dashboard/quizzes/{quiz}/attempts/{attempt}/progress', [StudentQuizAttemptController::class, 'saveProgress'])->name('lms.quizzes.progress');
    Route::post('/dashboard/quizzes/{quiz}/attempts/{attempt}/submit', [StudentQuizAttemptController::class, 'submit'])->name('lms.quizzes.submit');
    Route::get('/dashboard/quizzes/{quiz}/attempts/{attempt}/result', [StudentQuizAttemptController::class, 'result'])->name('lms.quizzes.result');
    Route::post('/online-courses/{course}/enroll', [CourseEnrollmentController::class, 'store'])->name('lms.courses.enroll');
    Route::get('/dashboard/discussions', [StudentDiscussionController::class, 'index'])->name('lms.discussions.index');
    Route::get('/dashboard/assignments', [AssignmentController::class, 'studentIndex'])->name('lms.assignments.index');
    Route::get('/dashboard/schedules', [StudentScheduleController::class, 'index'])->name('lms.schedules.index');
    Route::post('/dashboard/schedules/personal-events', [StudentScheduleController::class, 'storePersonal'])->name('lms.schedules.personal-events.store');
    Route::put('/dashboard/schedules/personal-events/{event}', [StudentScheduleController::class, 'updatePersonal'])->name('lms.schedules.personal-events.update');
    Route::delete('/dashboard/schedules/personal-events/{event}', [StudentScheduleController::class, 'destroyPersonal'])->name('lms.schedules.personal-events.destroy');
    Route::get('/dashboard/schedules/google/connect', [StudentScheduleController::class, 'googleRedirect'])->name('lms.schedules.google.connect');
    Route::get('/dashboard/schedules/google/callback', [StudentScheduleController::class, 'googleCallback'])->name('lms.schedules.google.callback');
    Route::post('/dashboard/schedules/google/sync', [StudentScheduleController::class, 'syncGoogle'])->name('lms.schedules.google.sync');
    Route::get('/dashboard/notes', [NoteController::class, 'index'])->name('lms.notes.index');
    Route::get('/dashboard/live-classes', [StudentLiveClassController::class, 'index'])->name('lms.live-classes.index');
    Route::get('/dashboard/live-classes/recordings', [StudentLiveClassController::class, 'recordings'])->name('lms.live-classes.recordings');
    Route::get('/dashboard/live-classes/{liveClass}/details', [StudentLiveClassController::class, 'details'])->name('lms.live-classes.details');
    Route::get('/dashboard/live-classes/{liveClass}', [StudentLiveClassController::class, 'show'])->name('lms.live-classes.show');
    Route::get('/dashboard/live-classes/{liveClass}/messages', [StudentLiveClassController::class, 'messages'])->name('lms.live-classes.messages');
    Route::post('/dashboard/live-classes/{liveClass}/messages', [StudentLiveClassController::class, 'sendMessage'])->name('lms.live-classes.messages.store');
    Route::post('/dashboard/live-classes/{liveClass}/attendance/join', [StudentLiveClassController::class, 'join'])->name('lms.live-classes.attendance.join');
    Route::post('/dashboard/live-classes/{liveClass}/attendance/ping', [StudentLiveClassController::class, 'ping'])->name('lms.live-classes.attendance.ping');
    Route::post('/dashboard/live-classes/{liveClass}/attendance/leave', [StudentLiveClassController::class, 'leave'])->name('lms.live-classes.attendance.leave');
});

// Admin LMS routes
Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/lms/platform-summary', [AdminLmsReportController::class, 'platformSummary'])->name('lms.platform-summary');
        Route::get('/lms/course-reports', [AdminLmsReportController::class, 'courseReports'])->name('lms.course-reports');
        Route::get('/lms/user-reports', [AdminLmsReportController::class, 'userReports'])->name('lms.user-reports');
        Route::get('/lms/discussions', [AdminLmsDiscussionController::class, 'index'])->name('lms.discussions.index');
        Route::get('/lms/assignments', [AssignmentController::class, 'adminIndex'])->name('lms.assignments.index');
        Route::post('/lms/assignments', [AssignmentController::class, 'adminStore'])->name('lms.assignments.store');
        Route::put('/lms/schedules/{schedule}', [ScheduleController::class, 'adminUpdate'])->name('lms.schedules.update');
        Route::delete('/lms/schedules/{schedule}', [ScheduleController::class, 'adminDestroy'])->name('lms.schedules.destroy');
        Route::get('/lms/schedules', [ScheduleController::class, 'adminIndex'])->name('lms.schedules.index');
        Route::post('/lms/schedules', [ScheduleController::class, 'adminStore'])->name('lms.schedules.store');
        Route::get('/lms/notifications', [BroadcastNotificationController::class, 'adminIndex'])->name('lms.notifications.index');
        Route::post('/lms/notifications', [BroadcastNotificationController::class, 'adminSend'])->name('lms.notifications.send');
        Route::get('/lms/live-classes', [AdminLiveClassController::class, 'index'])->name('lms.live-classes.index');
        Route::get('/lms/live-classes/create', [AdminLiveClassController::class, 'create'])->name('lms.live-classes.create');
        Route::post('/lms/live-classes', [AdminLiveClassController::class, 'store'])->name('lms.live-classes.store');
        Route::get('/lms/live-classes/{liveClass}', [AdminLiveClassController::class, 'show'])->name('lms.live-classes.show');
        Route::get('/lms/live-classes/{liveClass}/edit', [AdminLiveClassController::class, 'edit'])->name('lms.live-classes.edit');
        Route::put('/lms/live-classes/{liveClass}', [AdminLiveClassController::class, 'update'])->name('lms.live-classes.update');
        Route::delete('/lms/live-classes/{liveClass}', [AdminLiveClassController::class, 'destroy'])->name('lms.live-classes.destroy');
        Route::post('/lms/live-classes/{liveClass}/recordings', [AdminLiveClassController::class, 'addRecording'])->name('lms.live-classes.recordings.store');

        Route::get('/enrollments', [AdminEnrollmentController::class, 'index'])->name('enrollments.index');
        Route::patch('/enrollments/{enrollment}', [AdminEnrollmentController::class, 'update'])->name('enrollments.update');
        Route::delete('/enrollments/{enrollment}', [AdminEnrollmentController::class, 'destroy'])->name('enrollments.destroy');

        Route::get('/courses', [AdminCourseController::class, 'index'])->name('courses.index');
        Route::get('/courses/{course}', [AdminCourseController::class, 'show'])->name('courses.show');
        Route::get('/courses/{course}/edit', [AdminCourseController::class, 'edit'])->name('courses.edit');
        Route::put('/courses/{course}', [AdminCourseController::class, 'update'])->name('courses.update');
        Route::delete('/courses/{course}', [AdminCourseController::class, 'destroy'])->name('courses.destroy');
        Route::get('/courses/{course}/pricing', [CoursePricingController::class, 'show'])->name('courses.pricing');
        Route::put('/courses/{course}/pricing', [CoursePricingController::class, 'update'])->name('courses.pricing.update');
        Route::post('/courses/{course}/approve', [AdminCourseController::class, 'approve'])->name('courses.approve');
        Route::post('/courses/{course}/reject', [AdminCourseController::class, 'reject'])->name('courses.reject');
        Route::post('/courses/{course}/archive', [AdminCourseController::class, 'archive'])->name('courses.archive');
        Route::post('/courses/{course}/enroll-student', [CourseManualEnrollmentController::class, 'store'])->name('courses.enroll-student');
        Route::post('/courses/{course}/sections/{section}/lessons/{lesson}/video', [CourseLessonVideoController::class, 'store'])->name('courses.sections.lessons.video.store');
        Route::get('/quizzes/{quiz}/attempts', [AdminQuizAttemptController::class, 'index'])->name('quizzes.attempts.index');
        Route::get('/quizzes/{quiz}/attempts/{attempt}', [AdminQuizAttemptController::class, 'show'])->name('quizzes.attempts.show');
    });

// Tutor routes
Route::middleware(['auth', 'verified', 'tutor'])
    ->prefix('tutor')
    ->name('tutor.')
    ->group(function () {

        // Dashboard
        Route::get('dashboard', [TutorDashboardController::class, 'index'])->name('dashboard');

        Route::get('students', [TutorStudentController::class, 'index'])->name('students.index');
        Route::get('discussions', [TutorDiscussionController::class, 'index'])->name('discussions.index');
        Route::get('assignments', [AssignmentController::class, 'tutorIndex'])->name('assignments.index');
        Route::post('assignments', [AssignmentController::class, 'tutorStore'])->name('assignments.store');
        Route::put('schedules/{schedule}', [ScheduleController::class, 'tutorUpdate'])->name('schedules.update');
        Route::delete('schedules/{schedule}', [ScheduleController::class, 'tutorDestroy'])->name('schedules.destroy');
        Route::get('schedules', [ScheduleController::class, 'tutorIndex'])->name('schedules.index');
        Route::post('schedules', [ScheduleController::class, 'tutorStore'])->name('schedules.store');
        Route::get('notifications', [BroadcastNotificationController::class, 'tutorIndex'])->name('notifications.index');
        Route::post('notifications', [BroadcastNotificationController::class, 'tutorSend'])->name('notifications.send');
        Route::get('live-classes', [TutorLiveClassController::class, 'index'])->name('live-classes.index');
        Route::get('live-classes/create', [TutorLiveClassController::class, 'create'])->name('live-classes.create');
        Route::post('live-classes', [TutorLiveClassController::class, 'store'])->name('live-classes.store');
        Route::get('live-classes/{liveClass}', [TutorLiveClassController::class, 'show'])->name('live-classes.show');
        Route::get('live-classes/{liveClass}/edit', [TutorLiveClassController::class, 'edit'])->name('live-classes.edit');
        Route::put('live-classes/{liveClass}', [TutorLiveClassController::class, 'update'])->name('live-classes.update');
        Route::delete('live-classes/{liveClass}', [TutorLiveClassController::class, 'destroy'])->name('live-classes.destroy');
        Route::post('live-classes/{liveClass}/recordings', [TutorLiveClassController::class, 'addRecording'])->name('live-classes.recordings.store');

        // Lesson upload
        Route::get('lessons/upload', [LessonUploadController::class, 'index'])->name('lessons.upload');
        Route::post('lessons/upload', [LessonUploadController::class, 'store'])->name('lessons.upload.store');
        Route::delete('lessons/{lesson}', [LessonUploadController::class, 'destroy'])->name('lessons.destroy');

        // Course CRUD
        Route::get('courses', [TutorCourseController::class, 'index'])->name('courses.index');
        Route::get('courses/create', [TutorCourseController::class, 'create'])->name('courses.create');
        Route::post('courses', [TutorCourseController::class, 'store'])->name('courses.store');
        Route::get('courses/{course}', [TutorCourseController::class, 'show'])->name('courses.show');
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

        Route::post('courses/{course}/sections/{section}/lessons/{lesson}/attachments', [LessonAttachmentController::class, 'store'])->name('courses.sections.lessons.attachments.store');
        Route::delete('courses/{course}/sections/{section}/lessons/{lesson}/attachments/{attachment}', [LessonAttachmentController::class, 'destroy'])->name('courses.sections.lessons.attachments.destroy');
        Route::post('courses/{course}/sections/{section}/lessons/{lesson}/attachments/reorder', [LessonAttachmentController::class, 'reorder'])->name('courses.sections.lessons.attachments.reorder');

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

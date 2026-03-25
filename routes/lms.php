<?php

use App\Http\Controllers\Lms\AllCoursesController;
use App\Http\Controllers\Lms\CourseCatalogueController;
use App\Http\Controllers\Lms\MyCoursesController;
use App\Http\Controllers\Tutor\CourseController as TutorCourseController;
use App\Http\Controllers\Tutor\CourseSectionController;
use App\Http\Controllers\Tutor\CourseLessonController;
use Illuminate\Support\Facades\Route;

// Public course catalogue
Route::get('/online-courses', [CourseCatalogueController::class, 'index'])->name('lms.catalogue');

// Authenticated student routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard/courses', [AllCoursesController::class, 'index'])->name('lms.courses');
    Route::get('/dashboard/my-courses', [MyCoursesController::class, 'index'])->name('lms.my-courses');
});

// Tutor routes
Route::middleware(['auth', 'verified', 'tutor'])
    ->prefix('tutor')
    ->name('tutor.')
    ->group(function () {

        // Course CRUD
        Route::get('courses', [TutorCourseController::class, 'index'])->name('courses.index');
        Route::get('courses/create', [TutorCourseController::class, 'create'])->name('courses.create');
        Route::post('courses', [TutorCourseController::class, 'store'])->name('courses.store');
        Route::get('courses/{course}/edit', [TutorCourseController::class, 'edit'])->name('courses.edit');
        Route::put('courses/{course}', [TutorCourseController::class, 'update'])->name('courses.update');
        Route::delete('courses/{course}', [TutorCourseController::class, 'destroy'])->name('courses.destroy');
        Route::post('courses/{course}/cover', [TutorCourseController::class, 'uploadCover'])->name('courses.cover');
        Route::post('courses/{course}/submit', [TutorCourseController::class, 'submit'])->name('courses.submit');

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
    });

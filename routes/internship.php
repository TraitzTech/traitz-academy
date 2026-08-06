<?php

use App\Http\Controllers\Admin\CohortController;
use App\Http\Controllers\Internships\AttendanceController;
use App\Http\Controllers\Internships\InternshipDashboardController;
use App\Http\Controllers\Internships\LogbookController;
use App\Http\Controllers\Internships\LogbookPdfController;
use App\Http\Controllers\Internships\SupervisorController;
use Illuminate\Support\Facades\Route;

// Intern-facing internship area.
Route::middleware(['auth', 'verified', 'ensure.phone'])
    ->prefix('dashboard/internship')
    ->name('internship.')
    ->group(function () {
        Route::get('/', [InternshipDashboardController::class, 'index'])->name('dashboard');

        Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clock-in');
        Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clock-out');

        Route::post('/logbook', [LogbookController::class, 'store'])->name('logbook.store');
        Route::get('/logbook.pdf', [LogbookPdfController::class, 'own'])->name('logbook.pdf');
    });

// Supervisor: review the interns they supervise (assignment-based, tutor-capable).
Route::middleware(['auth', 'verified', 'ensure.phone'])
    ->prefix('supervisor/interns')
    ->name('supervisor.interns.')
    ->group(function () {
        Route::get('/', [SupervisorController::class, 'index'])->name('index');
        Route::get('/{internship}', [SupervisorController::class, 'show'])->name('show');
        Route::get('/{internship}/logbook.pdf', [LogbookPdfController::class, 'forInternship'])->name('logbook.pdf');
        Route::put('/logbook/{logbookEntry}/review', [SupervisorController::class, 'reviewLogbook'])->name('logbook.review');
        Route::post('/{internship}/attendance', [SupervisorController::class, 'markAttendance'])->name('attendance.mark');
    });

// Admin/coordinator: cohort management + supervisor assignment.
Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin/internships')
    ->name('admin.internships.')
    ->group(function () {
        Route::get('/cohorts', [CohortController::class, 'index'])->name('cohorts.index');
        Route::post('/cohorts', [CohortController::class, 'store'])->name('cohorts.store');
        Route::get('/cohorts/{cohort}', [CohortController::class, 'show'])->name('cohorts.show');
        Route::put('/cohorts/{cohort}', [CohortController::class, 'update'])->name('cohorts.update');
        Route::delete('/cohorts/{cohort}', [CohortController::class, 'destroy'])->name('cohorts.destroy');

        Route::post('/cohorts/{cohort}/interns', [CohortController::class, 'assignIntern'])->name('cohorts.interns.assign');
        Route::delete('/cohorts/{cohort}/interns/{internship}', [CohortController::class, 'removeIntern'])->name('cohorts.interns.remove');
        Route::put('/internships/{internship}/supervisor', [CohortController::class, 'updateInternSupervisor'])->name('internships.supervisor');
    });

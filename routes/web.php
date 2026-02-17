<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\ApplicationController as AdminApplicationController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EmailController;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Admin\InterviewController as AdminInterviewController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\ProgramController as AdminProgramController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SuccessStoryController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\InterviewController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

// Public pages
Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/success-stories', [PageController::class, 'successStories'])->name('success-stories');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::post('/contact', [PageController::class, 'submitContact'])->name('contact.submit');

// Search API Routes
Route::prefix('api/search')->name('search.')->group(function () {
    Route::get('/options', [SearchController::class, 'options'])->name('options');
    Route::get('/programs', [SearchController::class, 'programs'])->name('programs');
    Route::get('/events', [SearchController::class, 'events'])->name('events');
});

// Programs
Route::get('/programs', [ProgramController::class, 'index'])->name('programs.index');
Route::get('/programs/{program:slug}', [ProgramController::class, 'show'])->name('programs.show');

// Applications (Authenticated)
Route::middleware(['auth', 'ensure.phone'])->group(function () {
    Route::get('/programs/{program}/apply', [ApplicationController::class, 'create'])->name('applications.create');
    Route::post('/applications', [ApplicationController::class, 'store'])->name('applications.store');
});

// Events
Route::get('/events', [EventController::class, 'index'])->name('events.index');
Route::get('/events/{event:slug}', [EventController::class, 'show'])->name('events.show');
Route::post('/events/register', [EventController::class, 'register'])->name('events.register');

// User Dashboard (Authenticated)
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'ensure.phone'])
    ->name('dashboard');

// Interviews (Authenticated)
Route::middleware(['auth', 'verified', 'ensure.phone'])->prefix('interviews')->name('interviews.')->group(function () {
    Route::get('/{interview}', [InterviewController::class, 'show'])->name('show');
    Route::post('/{interview}/submit', [InterviewController::class, 'submit'])->name('submit');
    Route::get('/{interview}/result', [InterviewController::class, 'result'])->name('result');
});

Route::middleware(['auth', 'verified', 'ensure.phone'])->prefix('payments')->name('payments.')->group(function () {
    Route::get('/{application}/checkout', [PaymentController::class, 'checkout'])->name('checkout');
    Route::post('/{application}', [PaymentController::class, 'store'])->name('store');
    Route::get('/receipts/{payment}/print', [PaymentController::class, 'printReceiptPdf'])->name('receipt.print');
    Route::get('/receipts/{payment}/download', [PaymentController::class, 'downloadReceiptPdf'])->name('receipt.download');
    Route::get('/receipts/{payment}', [PaymentController::class, 'receipt'])->name('receipt');
});

// Admin Routes (Protected)
Route::prefix('admin')
    ->middleware(['auth', 'verified', 'admin'])
    ->name('admin.')
    ->group(function () {
        // Dashboard
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Programs CRUD
        Route::resource('programs', AdminProgramController::class)->except(['show']);
        Route::post('/programs/{program}/toggle-status', [AdminProgramController::class, 'toggleStatus'])->name('programs.toggle-status');
        Route::post('/programs/{program}/toggle-featured', [AdminProgramController::class, 'toggleFeatured'])->name('programs.toggle-featured');
        Route::post('/programs/bulk-destroy', [AdminProgramController::class, 'bulkDestroy'])->name('programs.bulk-destroy');

        // Events CRUD
        Route::resource('events', AdminEventController::class)->except(['show']);
        Route::post('/events/{event}/toggle-status', [AdminEventController::class, 'toggleStatus'])->name('events.toggle-status');
        Route::post('/events/bulk-destroy', [AdminEventController::class, 'bulkDestroy'])->name('events.bulk-destroy');

        // Applications Management
        Route::get('/applications', [AdminApplicationController::class, 'index'])->name('applications.index');
        Route::get('/applications/{application}', [AdminApplicationController::class, 'show'])->name('applications.show');
        Route::get('/applications/{application}/edit', [AdminApplicationController::class, 'edit'])->name('applications.edit');
        Route::put('/applications/{application}', [AdminApplicationController::class, 'update'])->name('applications.update');
        Route::post('/applications/{application}/accept', [AdminApplicationController::class, 'accept'])->name('applications.accept');
        Route::post('/applications/{application}/reject', [AdminApplicationController::class, 'reject'])->name('applications.reject');
        Route::post('/applications/{application}/schedule-interview', [AdminApplicationController::class, 'scheduleInterview'])->name('applications.schedule-interview');
        Route::post('/applications/{application}/payment-reminder', [AdminApplicationController::class, 'sendPaymentReminder'])->name('applications.payment-reminder');
        Route::post('/applications/bulk', [AdminApplicationController::class, 'bulkAction'])->name('applications.bulk');
        Route::post('/applications/bulk-schedule-interview', [AdminApplicationController::class, 'bulkScheduleInterview'])->name('applications.bulk-schedule-interview');
        Route::post('/applications/bulk-payment-reminder', [AdminApplicationController::class, 'bulkPaymentReminder'])->name('applications.bulk-payment-reminder');
        Route::delete('/applications/{application}', [AdminApplicationController::class, 'destroy'])->name('applications.destroy');

        // Payments Management
        Route::get('/payments', [AdminPaymentController::class, 'index'])->name('payments.index');
        Route::get('/payments/verify', [AdminPaymentController::class, 'verify'])->name('payments.verify');
        Route::post('/payments/manual', [AdminPaymentController::class, 'storeManual'])->name('payments.manual-store');
        Route::patch('/payments/{payment}', [AdminPaymentController::class, 'update'])->name('payments.update');

        // Users Management
        Route::get('/users/export', [UserController::class, 'export'])->name('users.export');
        Route::resource('users', UserController::class);
        Route::post('/users/{user}/toggle-role', [UserController::class, 'toggleRole'])->name('users.toggle-role');
        Route::post('/users/bulk-destroy', [UserController::class, 'bulkDestroy'])->name('users.bulk-destroy');

        // Settings
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/upload', [SettingsController::class, 'uploadImage'])->name('settings.upload');
        Route::delete('/settings/image/{key}', [SettingsController::class, 'deleteImage'])->name('settings.delete-image');

        // Email Notifications
        Route::get('/emails', [EmailController::class, 'index'])->name('emails.index');
        Route::post('/emails', [EmailController::class, 'send'])->name('emails.send');
        Route::post('/emails/media', [EmailController::class, 'uploadMedia'])->name('emails.upload-media');
        Route::post('/emails/preview', [EmailController::class, 'preview'])->name('emails.preview');

        // Success Stories CRUD
        Route::resource('success-stories', SuccessStoryController::class)->except(['show']);
        Route::post('/success-stories/{success_story}/toggle-status', [SuccessStoryController::class, 'toggleStatus'])->name('success-stories.toggle-status');
        Route::post('/success-stories/bulk-destroy', [SuccessStoryController::class, 'bulkDestroy'])->name('success-stories.bulk-destroy');

        // Interviews Management
        Route::resource('interviews', AdminInterviewController::class)->except(['show']);
        Route::post('/interviews/{interview}/toggle-status', [AdminInterviewController::class, 'toggleStatus'])->name('interviews.toggle-status');
        Route::get('/interviews/{interview}/responses', [AdminInterviewController::class, 'responses'])->name('interviews.responses');
        Route::get('/interviews/{interview}/responses/{response}', [AdminInterviewController::class, 'showResponse'])->name('interviews.responses.show');
        Route::post('/interviews/{interview}/responses/{response}/review', [AdminInterviewController::class, 'reviewResponse'])->name('interviews.responses.review');

        // Account Settings
        Route::get('/account', AccountController::class)->name('account');
    });

require __DIR__.'/settings.php';

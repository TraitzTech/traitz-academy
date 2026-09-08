<?php

use App\Http\Controllers\Admin\Community\ActivityController as AdminActivityController;
use App\Http\Controllers\Admin\Community\AnnouncementController as AdminAnnouncementController;
use App\Http\Controllers\Admin\Community\CompetitionController as AdminCompetitionController;
use App\Http\Controllers\Admin\Community\DashboardController as AdminCommunityDashboardController;
use App\Http\Controllers\Admin\Community\LeaderController as AdminLeaderController;
use App\Http\Controllers\Admin\Community\LeaderPerformanceReviewController as AdminLeaderPerformanceReviewController;
use App\Http\Controllers\Admin\Community\LeaderResponsibilityController as AdminLeaderResponsibilityController;
use App\Http\Controllers\Admin\Community\MemberController as AdminMemberController;
use App\Http\Controllers\Admin\Community\PartnerController as AdminPartnerController;
use App\Http\Controllers\Admin\Community\RsvpController as AdminRsvpController;
use App\Http\Controllers\Admin\Community\TrackController as AdminTrackController;
use App\Http\Controllers\Community\ActivityController;
use App\Http\Controllers\Community\ActivityPaymentController;
use App\Http\Controllers\Community\CommunityController;
use App\Http\Controllers\Community\CompetitionEntryController;
use App\Http\Controllers\Community\JoinController;
use App\Http\Controllers\Community\LeaderProfileController;
use App\Http\Controllers\Community\MemberAreaController;
use App\Http\Controllers\Community\PartnerController;
use App\Http\Controllers\Community\RsvpController;
use App\Http\Controllers\Community\TeamController;
use App\Http\Controllers\Community\TrackController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Traitz Academy Community (TAC)
|--------------------------------------------------------------------------
| A standing community in its own right, not a campaign page: public pages,
| an always-on join flow, a member area, and an admin panel that lets
| leadership and activities change without a deploy.
*/

Route::prefix('community')->name('community.')->group(function () {
    // ---- Public ----
    Route::get('/', [CommunityController::class, 'index'])->name('index');
    Route::get('/about', [CommunityController::class, 'about'])->name('about');
    Route::get('/get-involved', [CommunityController::class, 'getInvolved'])->name('get-involved');
    Route::get('/partners', PartnerController::class)->name('partners');
    Route::get('/team', TeamController::class)->name('team');
    Route::get('/team/{leader:slug}', [LeaderProfileController::class, 'show'])->name('team.show');

    Route::get('/tracks', [TrackController::class, 'index'])->name('tracks.index');

    // Join — always on, never time-boxed.
    Route::get('/join', [JoinController::class, 'create'])->name('join');
    Route::post('/join', [JoinController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('join.store');
    Route::get('/welcome', [JoinController::class, 'welcome'])->name('welcome');

    // ---- Member area (requires an account) ----
    Route::middleware(['auth', 'verified'])->prefix('me')->name('member.')->group(function () {
        Route::get('/', [MemberAreaController::class, 'dashboard'])->name('dashboard');
        Route::get('/profile', [MemberAreaController::class, 'profile'])->name('profile');
        Route::post('/profile', [MemberAreaController::class, 'updateProfile'])->name('profile.update');
        Route::get('/directory', [MemberAreaController::class, 'directory'])->name('directory');
    });

    // ---- Activities ----
    Route::prefix('activities')->name('activities.')->group(function () {
        Route::get('/', [ActivityController::class, 'index'])->name('index');
        Route::get('/{activity:slug}', [ActivityController::class, 'show'])->name('show');

        Route::post('/{activity:slug}/rsvp', [RsvpController::class, 'store'])
            ->middleware('throttle:20,1')
            ->name('rsvp');
        Route::delete('/{activity:slug}/rsvp', [RsvpController::class, 'destroy'])->name('rsvp.cancel');

        // Paid workshops / bootcamps — MTN MoMo & Orange Money via MeSomb.
        Route::get('/{activity:slug}/checkout', [ActivityPaymentController::class, 'checkout'])->name('checkout');
        Route::post('/{activity:slug}/checkout', [ActivityPaymentController::class, 'pay'])
            ->middleware('throttle:10,1')
            ->name('checkout.pay');

        // Competition entries need an account: entries can be edited later.
        Route::middleware(['auth', 'verified'])->group(function () {
            Route::post('/{activity:slug}/entries', [CompetitionEntryController::class, 'store'])->name('entries.store');
            Route::post('/{activity:slug}/entries/{entry}', [CompetitionEntryController::class, 'update'])->name('entries.update');
            Route::delete('/{activity:slug}/entries/{entry}', [CompetitionEntryController::class, 'destroy'])->name('entries.destroy');
        });
    });

    // Declared last so it never shadows /tracks, /team, /join, etc.
    Route::get('/tracks/{track:slug}', [TrackController::class, 'show'])->name('tracks.show');
});

/*
|--------------------------------------------------------------------------
| Community admin
|--------------------------------------------------------------------------
| Guarded by `tac-staff`, which is deliberately wider than `admin`: TAC track
| mentors and school leads are often plain accounts, and they run their own
| corner of the community. Per-record authority is enforced by policies.
*/

Route::prefix('admin/community')
    ->middleware(['auth', 'verified', 'tac-staff'])
    ->name('admin.community.')
    ->group(function () {
        Route::get('/', AdminCommunityDashboardController::class)->name('dashboard');

        // Members
        Route::prefix('members')->name('members.')->group(function () {
            Route::get('/', [AdminMemberController::class, 'index'])->name('index');
            Route::get('/export', [AdminMemberController::class, 'export'])->name('export');
            Route::post('/', [AdminMemberController::class, 'store'])->name('store');
            Route::post('/bulk', [AdminMemberController::class, 'bulk'])->name('bulk');
            Route::get('/{member}', [AdminMemberController::class, 'show'])->name('show');
            Route::put('/{member}', [AdminMemberController::class, 'update'])->name('update');
            Route::delete('/{member}', [AdminMemberController::class, 'destroy'])->name('destroy');
        });

        // Tracks
        Route::prefix('tracks')->name('tracks.')->group(function () {
            Route::get('/', [AdminTrackController::class, 'index'])->name('index');
            Route::post('/', [AdminTrackController::class, 'store'])->name('store');
            Route::post('/reorder', [AdminTrackController::class, 'reorder'])->name('reorder');
            Route::post('/{track}', [AdminTrackController::class, 'update'])->name('update');
            Route::delete('/{track}', [AdminTrackController::class, 'destroy'])->name('destroy');
        });

        // Leadership roster
        Route::prefix('leaders')->name('leaders.')->group(function () {
            Route::get('/', [AdminLeaderController::class, 'index'])->name('index');
            Route::post('/', [AdminLeaderController::class, 'store'])->name('store');
            Route::get('/{leader}', [AdminLeaderController::class, 'show'])->name('show');
            Route::post('/{leader}', [AdminLeaderController::class, 'update'])->name('update');
            Route::post('/{leader}/retire', [AdminLeaderController::class, 'retire'])->name('retire');
            Route::post('/{leader}/reinstate', [AdminLeaderController::class, 'reinstate'])->name('reinstate');
            Route::post('/{leader}/create-login', [AdminLeaderController::class, 'createLogin'])->name('create-login');
            Route::delete('/{leader}', [AdminLeaderController::class, 'destroy'])->name('destroy');

            // Responsibilities — assigned by staff, progress updated by
            // either staff or the leader themselves.
            Route::post('/{leader}/responsibilities', [AdminLeaderResponsibilityController::class, 'store'])->name('responsibilities.store');
            Route::post('/{leader}/responsibilities/{responsibility}', [AdminLeaderResponsibilityController::class, 'update'])->name('responsibilities.update');
            Route::patch('/{leader}/responsibilities/{responsibility}/status', [AdminLeaderResponsibilityController::class, 'updateStatus'])->name('responsibilities.status');
            Route::delete('/{leader}/responsibilities/{responsibility}', [AdminLeaderResponsibilityController::class, 'destroy'])->name('responsibilities.destroy');

            // Performance reviews — staff-written, append-only.
            Route::post('/{leader}/reviews', [AdminLeaderPerformanceReviewController::class, 'store'])->name('reviews.store');
            Route::delete('/{leader}/reviews/{review}', [AdminLeaderPerformanceReviewController::class, 'destroy'])->name('reviews.destroy');
        });

        // Announcements — a leader emails the people they actually lead.
        Route::prefix('announcements')->name('announcements.')->group(function () {
            Route::get('/', [AdminAnnouncementController::class, 'create'])->name('create');
            Route::post('/', [AdminAnnouncementController::class, 'store'])->name('store');
            Route::post('/media', [AdminAnnouncementController::class, 'uploadMedia'])->name('media');
        });

        // Activities
        Route::prefix('activities')->name('activities.')->group(function () {
            Route::get('/', [AdminActivityController::class, 'index'])->name('index');
            Route::get('/create', [AdminActivityController::class, 'create'])->name('create');
            Route::post('/', [AdminActivityController::class, 'store'])->name('store');
            Route::get('/{activity}', [AdminActivityController::class, 'show'])->name('show');
            Route::get('/{activity}/edit', [AdminActivityController::class, 'edit'])->name('edit');
            Route::post('/{activity}', [AdminActivityController::class, 'update'])->name('update');
            Route::delete('/{activity}', [AdminActivityController::class, 'destroy'])->name('destroy');
            Route::post('/{activity}/status', [AdminActivityController::class, 'setStatus'])->name('status');
            Route::post('/{activity}/featured', [AdminActivityController::class, 'toggleFeatured'])->name('featured');

            // RSVPs
            Route::get('/{activity}/rsvps/export', [AdminRsvpController::class, 'export'])->name('rsvps.export');
            Route::post('/{activity}/rsvps/bulk', [AdminRsvpController::class, 'bulk'])->name('rsvps.bulk');
            Route::post('/{activity}/rsvps/remind', [AdminRsvpController::class, 'remindAll'])->name('rsvps.remind');
            Route::patch('/{activity}/rsvps/{rsvp}', [AdminRsvpController::class, 'update'])->name('rsvps.update');

            // Competition judging
            Route::get('/{activity}/judge', [AdminCompetitionController::class, 'show'])->name('judge');
            Route::post('/{activity}/judge/{entry}/score', [AdminCompetitionController::class, 'score'])->name('judge.score');
            Route::post('/{activity}/judge/{entry}', [AdminCompetitionController::class, 'updateEntry'])->name('judge.entry');
            Route::post('/{activity}/judge/publish', [AdminCompetitionController::class, 'publishResults'])->name('judge.publish');
        });

        // Partners / sponsors
        Route::prefix('partners')->name('partners.')->group(function () {
            Route::get('/', [AdminPartnerController::class, 'index'])->name('index');
            Route::post('/', [AdminPartnerController::class, 'store'])->name('store');
            Route::post('/{partner}', [AdminPartnerController::class, 'update'])->name('update');
            Route::delete('/{partner}', [AdminPartnerController::class, 'destroy'])->name('destroy');
        });
    });

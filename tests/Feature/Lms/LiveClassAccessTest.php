<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LiveClass;
use App\Models\LiveClassAttendance;
use App\Models\User;

it('lets the owning tutor and admin staff manage/join', function () {
    $tutor = User::factory()->create(['role' => User::ROLE_TUTOR]);
    $liveClass = LiveClass::factory()->create(['tutor_id' => $tutor->id]);
    $exec = User::factory()->create(['role' => User::ROLE_CTO]);
    $otherTutor = User::factory()->create(['role' => User::ROLE_TUTOR]);

    expect($liveClass->isManageableBy($tutor))->toBeTrue()
        ->and($liveClass->isManageableBy($exec))->toBeTrue()
        ->and($liveClass->isManageableBy($otherTutor))->toBeFalse()
        ->and($liveClass->canUserJoin($tutor))->toBeTrue()
        ->and($liveClass->canUserJoin($exec))->toBeTrue();
});

it('grants course-access classes only to learners with access-granting enrollment', function () {
    $liveClass = LiveClass::factory()->create(['access_type' => 'course']);
    $course = Course::factory()->create();
    $liveClass->courses()->attach($course->id);

    $student = User::factory()->create();

    // Not enrolled.
    expect($liveClass->canUserJoin($student))->toBeFalse();

    // Active enrollment grants access.
    $enrollment = Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'access_status' => 'active',
    ]);
    expect($liveClass->canUserJoin($student))->toBeTrue();

    // Suspended enrollment does NOT (canonical scopeGrantsAccess).
    $enrollment->update(['access_status' => 'suspended']);
    expect($liveClass->canUserJoin($student))->toBeFalse();
});

it('grants custom-access classes only to explicitly-listed students', function () {
    $liveClass = LiveClass::factory()->create(['access_type' => 'custom']);
    $invited = User::factory()->create();
    $stranger = User::factory()->create();
    $liveClass->students()->attach($invited->id);

    expect($liveClass->canUserJoin($invited))->toBeTrue()
        ->and($liveClass->canUserJoin($stranger))->toBeFalse();
});

it('scopes visible classes at the query level', function () {
    $tutor = User::factory()->create(['role' => User::ROLE_TUTOR]);
    $mine = LiveClass::factory()->create(['tutor_id' => $tutor->id]);
    $other = LiveClass::factory()->create(); // different tutor, custom, not invited

    $ids = LiveClass::query()->visibleTo($tutor)->pluck('id');

    expect($ids)->toContain($mine->id)->not->toContain($other->id);
});

it('does not create duplicate attendance rows on repeated join', function () {
    $liveClass = LiveClass::factory()->create([
        'access_type' => 'custom',
        'start_time' => now(),
        'duration' => 60,
    ]);
    $student = User::factory()->create();
    $liveClass->students()->attach($student->id);
    $liveClass->markHostOnline();

    $this->actingAs($student)->post("/dashboard/live-classes/{$liveClass->id}/attendance/join")->assertOk();
    $this->actingAs($student)->post("/dashboard/live-classes/{$liveClass->id}/attendance/join")->assertOk();

    $open = LiveClassAttendance::query()
        ->where('live_class_id', $liveClass->id)
        ->where('student_id', $student->id)
        ->whereNull('left_at')
        ->count();

    expect($open)->toBe(1);
});

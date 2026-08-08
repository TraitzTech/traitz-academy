<?php

use App\Models\Cohort;
use App\Models\Internship;
use App\Models\LiveClass;
use App\Models\Program;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

function makeLiveClassCohort(string $name = 'Batch'): Cohort
{
    return Cohort::query()->create([
        'name' => $name, 'slug' => 'batch-'.uniqid(), 'status' => Cohort::STATUS_ACTIVE,
        'start_date' => '2026-08-01', 'end_date' => '2026-09-30', 'timezone' => 'UTC',
    ]);
}

function liveClassCohort(User $supervisor): array
{
    $program = Program::factory()->create(['category' => 'professional-internship']);
    $cohort = makeLiveClassCohort();
    $cohort->programs()->attach($program->id, ['supervisor_id' => $supervisor->id]);

    return [$cohort, $program];
}

it('schedules a live class scoped to the supervisor’s program interns', function () {
    Notification::fake();

    $supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);
    [$cohort, $program] = liveClassCohort($supervisor);
    $intern = User::factory()->create(['role' => User::ROLE_USER]);
    Internship::factory()->create(['cohort_id' => $cohort->id, 'program_id' => $program->id, 'user_id' => $intern->id]);

    $this->actingAs($supervisor)
        ->post('/tutor/live-classes', [
            'title' => 'Weekly sync',
            'start_time' => now()->addDay()->toDateTimeString(),
            'duration' => 60,
            'access_type' => 'course',
            'meeting_url' => 'https://meet.example.com/abc',
            'targets' => [['type' => 'program', 'id' => $program->id]],
        ])
        ->assertRedirect();

    $liveClass = LiveClass::query()->where('title', 'Weekly sync')->firstOrFail();
    // The program target is pinned to the supervisor's exact interns (custom
    // roster) instead of a broad program target that would span every cohort.
    expect($liveClass->access_type)->toBe('custom');
    expect(DB::table('live_class_targets')->where('live_class_id', $liveClass->id)->count())->toBe(0);
    expect($liveClass->students()->pluck('users.id')->all())->toBe([$intern->id]);
});

it('does not let a live class reach the same program in another supervisor’s cohort', function () {
    Notification::fake();

    $program = Program::factory()->create(['category' => 'professional-internship']);

    $supervisorA = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);
    $cohortA = makeLiveClassCohort('Batch A');
    $cohortA->programs()->attach($program->id, ['supervisor_id' => $supervisorA->id]);
    $internA = User::factory()->create(['role' => User::ROLE_USER]);
    Internship::factory()->create(['cohort_id' => $cohortA->id, 'program_id' => $program->id, 'user_id' => $internA->id]);

    $supervisorB = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);
    $cohortB = makeLiveClassCohort('Batch B');
    $cohortB->programs()->attach($program->id, ['supervisor_id' => $supervisorB->id]);
    $internB = User::factory()->create(['role' => User::ROLE_USER]);
    Internship::factory()->create(['cohort_id' => $cohortB->id, 'program_id' => $program->id, 'user_id' => $internB->id]);

    $this->actingAs($supervisorA)
        ->post('/tutor/live-classes', [
            'title' => 'Batch A only',
            'start_time' => now()->addDay()->toDateTimeString(),
            'duration' => 60,
            'access_type' => 'course',
            'meeting_url' => 'https://meet.example.com/abc',
            'targets' => [['type' => 'program', 'id' => $program->id]],
        ])
        ->assertRedirect();

    $liveClass = LiveClass::query()->where('title', 'Batch A only')->firstOrFail();
    $studentIds = $liveClass->students()->pluck('users.id')->all();

    expect($studentIds)->toContain($internA->id);
    expect($studentIds)->not->toContain($internB->id);
    expect($liveClass->canUserJoin($internB->fresh()))->toBeFalse();
});

it('drops a live-class target the supervisor does not supervise', function () {
    Notification::fake();

    $supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);
    liveClassCohort($supervisor);

    $other = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);
    [, $otherProgram] = liveClassCohort($other);

    $this->actingAs($supervisor)
        ->post('/tutor/live-classes', [
            'title' => 'Sneaky class',
            'start_time' => now()->addDay()->toDateTimeString(),
            'duration' => 60,
            'access_type' => 'course',
            'meeting_url' => 'https://meet.example.com/xyz',
            'targets' => [['type' => 'program', 'id' => $otherProgram->id]],
        ])
        ->assertRedirect();

    $liveClass = LiveClass::query()->where('title', 'Sneaky class')->firstOrFail();
    expect(DB::table('live_class_targets')->where('live_class_id', $liveClass->id)->count())->toBe(0);
});

it('forbids a plain student from the live class manager', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    $this->actingAs($user)->get('/tutor/live-classes')->assertStatus(403);
});

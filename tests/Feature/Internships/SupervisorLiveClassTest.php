<?php

use App\Models\Cohort;
use App\Models\LiveClass;
use App\Models\Program;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

function liveClassCohort(User $supervisor): array
{
    $program = Program::factory()->create(['category' => 'professional-internship']);
    $cohort = Cohort::query()->create([
        'name' => 'Batch', 'slug' => 'batch-'.uniqid(), 'status' => Cohort::STATUS_ACTIVE,
        'start_date' => '2026-08-01', 'end_date' => '2026-09-30', 'timezone' => 'UTC',
    ]);
    $cohort->programs()->attach($program->id, ['supervisor_id' => $supervisor->id]);

    return [$cohort, $program];
}

it('lets a supervisor schedule a live class for a program they supervise', function () {
    Notification::fake();

    $supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);
    [, $program] = liveClassCohort($supervisor);

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
    expect(DB::table('live_class_targets')
        ->where('live_class_id', $liveClass->id)
        ->where('target_type', Program::class)
        ->where('target_id', $program->id)
        ->exists())->toBeTrue();
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

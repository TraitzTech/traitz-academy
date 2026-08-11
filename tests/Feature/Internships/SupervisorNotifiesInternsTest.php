<?php

use App\Models\Cohort;
use App\Models\Internship;
use App\Models\Program;
use App\Models\User;
use App\Notifications\Lms\ManualLmsAnnouncementNotification;
use Illuminate\Support\Facades\Notification;

function supervisedCohort(User $supervisor): array
{
    $program = Program::factory()->create(['category' => 'professional-internship']);
    $cohort = Cohort::query()->create([
        'name' => 'Batch', 'slug' => 'batch-'.uniqid(), 'status' => Cohort::STATUS_ACTIVE,
        'start_date' => '2026-08-01', 'end_date' => '2026-09-30', 'timezone' => 'UTC',
    ]);
    $cohort->programs()->attach($program->id, ['supervisor_id' => $supervisor->id]);

    return [$cohort, $program];
}

it('lets a supervisor broadcast a notification to their program interns', function () {
    Notification::fake();

    $supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);
    [$cohort, $program] = supervisedCohort($supervisor);

    $intern = User::factory()->create(['role' => User::ROLE_USER]);
    Internship::factory()->create(['cohort_id' => $cohort->id, 'program_id' => $program->id, 'user_id' => $intern->id]);

    $this->actingAs($supervisor)
        ->post('/tutor/notifications', [
            'audience' => 'all_course_students',
            'attachable_type' => 'program',
            'attachable_id' => $program->id,
            'subject' => 'Standup tomorrow',
            'message' => 'Please be on time.',
        ])
        ->assertRedirect();

    Notification::assertSentTo($intern, ManualLmsAnnouncementNotification::class);
});

it('forbids a supervisor from notifying a whole cohort', function () {
    Notification::fake();

    $supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);
    [$cohort] = supervisedCohort($supervisor);

    $this->actingAs($supervisor)
        ->post('/tutor/notifications', [
            'audience' => 'all_course_students',
            'attachable_type' => 'cohort',
            'attachable_id' => $cohort->id,
            'subject' => 'Whole cohort',
            'message' => 'Should not send.',
        ])
        ->assertStatus(403);

    Notification::assertNothingSent();
});

it('forbids a supervisor from notifying a program they do not supervise', function () {
    Notification::fake();

    $supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);
    supervisedCohort($supervisor);

    $other = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);
    [$otherCohort, $otherProgram] = supervisedCohort($other);
    $intern = User::factory()->create(['role' => User::ROLE_USER]);
    Internship::factory()->create(['cohort_id' => $otherCohort->id, 'program_id' => $otherProgram->id, 'user_id' => $intern->id]);

    $this->actingAs($supervisor)
        ->post('/tutor/notifications', [
            'audience' => 'all_course_students',
            'attachable_type' => 'program',
            'attachable_id' => $otherProgram->id,
            'subject' => 'Sneaky',
            'message' => 'Should not send.',
        ])
        ->assertStatus(403);

    Notification::assertNothingSent();
});

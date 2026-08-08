<?php

use App\Models\Assignment;
use App\Models\Cohort;
use App\Models\Internship;
use App\Models\Program;
use App\Models\User;

function cohortWithSupervisor(User $supervisor): array
{
    $program = Program::factory()->create(['category' => 'professional-internship']);
    $cohort = Cohort::query()->create([
        'name' => 'Batch',
        'slug' => 'batch-'.uniqid(),
        'status' => Cohort::STATUS_ACTIVE,
        'start_date' => '2026-08-01',
        'end_date' => '2026-09-30',
        'timezone' => 'UTC',
    ]);
    $cohort->programs()->attach($program->id, ['supervisor_id' => $supervisor->id]);

    return [$cohort, $program];
}

it('lets an internship supervisor create a task for their cohort interns', function () {
    $supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);
    [$cohort, $program] = cohortWithSupervisor($supervisor);

    // An intern in that cohort/program.
    $intern = User::factory()->create();
    Internship::factory()->create([
        'cohort_id' => $cohort->id,
        'program_id' => $program->id,
        'user_id' => $intern->id,
    ]);

    $this->actingAs($supervisor)
        ->post('/tutor/assignments', [
            'attachable_type' => 'cohort',
            'attachable_id' => $cohort->id,
            'title' => 'Week 1 report',
            'instructions' => 'Write up your first week.',
            'audience' => 'all_course_students',
        ])
        ->assertRedirect();

    expect(Assignment::where('title', 'Week 1 report')->where('attachable_id', $cohort->id)->exists())->toBeTrue();
});

it('forbids a supervisor from targeting a cohort they do not supervise', function () {
    $supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);
    cohortWithSupervisor($supervisor); // gives them access to SOME cohort

    // A different cohort, supervised by someone else.
    $other = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);
    [$otherCohort] = cohortWithSupervisor($other);

    $this->actingAs($supervisor)
        ->post('/tutor/assignments', [
            'attachable_type' => 'cohort',
            'attachable_id' => $otherCohort->id,
            'title' => 'Sneaky task',
            'instructions' => 'Should not be allowed.',
            'audience' => 'all_course_students',
        ])
        ->assertStatus(403);
});

it('forbids a plain user who supervises nobody from the tasks page', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    $this->actingAs($user)->get('/tutor/assignments')->assertStatus(403);
});

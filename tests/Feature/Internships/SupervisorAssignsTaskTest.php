<?php

use App\Models\Assignment;
use App\Models\Cohort;
use App\Models\Internship;
use App\Models\Program;
use App\Models\User;

function makeCohort(string $name = 'Batch'): Cohort
{
    return Cohort::query()->create([
        'name' => $name,
        'slug' => 'batch-'.uniqid(),
        'status' => Cohort::STATUS_ACTIVE,
        'start_date' => '2026-08-01',
        'end_date' => '2026-09-30',
        'timezone' => 'UTC',
    ]);
}

function cohortWithSupervisor(User $supervisor): array
{
    $program = Program::factory()->create(['category' => 'professional-internship']);
    $cohort = makeCohort();
    $cohort->programs()->attach($program->id, ['supervisor_id' => $supervisor->id]);

    return [$cohort, $program];
}

function internIn(Cohort $cohort, Program $program): User
{
    $intern = User::factory()->create();
    Internship::factory()->create([
        'cohort_id' => $cohort->id,
        'program_id' => $program->id,
        'user_id' => $intern->id,
    ]);

    return $intern;
}

it('lets an internship supervisor create a task for their program interns', function () {
    $supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);
    [$cohort, $program] = cohortWithSupervisor($supervisor);
    $intern = internIn($cohort, $program);

    $this->actingAs($supervisor)
        ->post('/tutor/assignments', [
            'attachable_type' => 'program',
            'attachable_id' => $program->id,
            'title' => 'Week 1 report',
            'instructions' => 'Write up your first week.',
            'audience' => 'all_course_students',
        ])
        ->assertRedirect();

    $assignment = Assignment::where('title', 'Week 1 report')->first();
    expect($assignment)->not->toBeNull();
    expect($assignment->attachable_id)->toBe($program->id);
    // "Everyone in the program" is pinned to the supervisor's exact interns.
    expect($assignment->audience)->toBe('selected_students');
    expect($assignment->selectedStudents()->pluck('users.id')->all())->toBe([$intern->id]);
});

it('forbids a supervisor from targeting a whole cohort', function () {
    $supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);
    [$cohort] = cohortWithSupervisor($supervisor);

    $this->actingAs($supervisor)
        ->post('/tutor/assignments', [
            'attachable_type' => 'cohort',
            'attachable_id' => $cohort->id,
            'title' => 'Whole cohort',
            'instructions' => 'Should not be allowed.',
            'audience' => 'all_course_students',
        ])
        ->assertStatus(403);
});

it('forbids a supervisor from targeting a program they do not supervise', function () {
    $supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);
    cohortWithSupervisor($supervisor); // gives them SOME program

    $other = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);
    [, $otherProgram] = cohortWithSupervisor($other);

    $this->actingAs($supervisor)
        ->post('/tutor/assignments', [
            'attachable_type' => 'program',
            'attachable_id' => $otherProgram->id,
            'title' => 'Sneaky task',
            'instructions' => 'Should not be allowed.',
            'audience' => 'all_course_students',
        ])
        ->assertStatus(403);
});

it('does not reach interns of the same program in a cohort supervised by someone else', function () {
    // One program run in two cohorts (batches), each with its own supervisor.
    $program = Program::factory()->create(['category' => 'professional-internship']);

    $supervisorA = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);
    $cohortA = makeCohort('Batch A');
    $cohortA->programs()->attach($program->id, ['supervisor_id' => $supervisorA->id]);
    $internA = internIn($cohortA, $program);

    $supervisorB = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);
    $cohortB = makeCohort('Batch B');
    $cohortB->programs()->attach($program->id, ['supervisor_id' => $supervisorB->id]);
    $internB = internIn($cohortB, $program);

    $this->actingAs($supervisorA)
        ->post('/tutor/assignments', [
            'attachable_type' => 'program',
            'attachable_id' => $program->id,
            'title' => 'Batch A only',
            'instructions' => 'Only my batch.',
            'audience' => 'all_course_students',
        ])
        ->assertRedirect();

    $assignment = Assignment::where('title', 'Batch A only')->first();
    $recipients = $assignment->selectedStudents()->pluck('users.id')->all();

    expect($recipients)->toContain($internA->id);
    expect($recipients)->not->toContain($internB->id);
});

it('forbids a plain user who supervises nobody from the tasks page', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    $this->actingAs($user)->get('/tutor/assignments')->assertStatus(403);
});

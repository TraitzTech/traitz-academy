<?php

use App\Models\Cohort;
use App\Models\Internship;
use App\Models\Program;
use App\Models\User;

it('lists only cohorts the supervisor has interns in', function () {
    $supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);

    $program = Program::factory()->create(['category' => 'professional-internship']);
    $myCohort = Cohort::query()->create([
        'name' => 'My Batch', 'slug' => 'my-batch-'.uniqid(), 'status' => Cohort::STATUS_ACTIVE,
        'start_date' => '2026-08-01', 'end_date' => '2026-09-30', 'timezone' => 'UTC',
    ]);
    $myCohort->programs()->attach($program->id, ['supervisor_id' => $supervisor->id]);

    Internship::factory()->create([
        'cohort_id' => $myCohort->id, 'program_id' => $program->id,
        'user_id' => User::factory()->create()->id,
    ]);

    $otherCohort = Cohort::query()->create([
        'name' => 'Other Batch', 'slug' => 'other-batch-'.uniqid(), 'status' => Cohort::STATUS_ACTIVE,
        'start_date' => '2026-08-01', 'end_date' => '2026-09-30', 'timezone' => 'UTC',
    ]);
    $otherProgram = Program::factory()->create(['category' => 'professional-internship']);
    $otherCohort->programs()->attach($otherProgram->id, ['supervisor_id' => User::factory()->create()->id]);
    Internship::factory()->create([
        'cohort_id' => $otherCohort->id, 'program_id' => $otherProgram->id,
        'user_id' => User::factory()->create()->id,
    ]);

    $this->actingAs($supervisor)
        ->get('/supervisor/cohorts')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Internships/Supervisor/Cohorts/Index')
            ->has('cohorts', 1)
            ->where('cohorts.0.id', $myCohort->id));
});

it('shows a supervised cohort with only the supervisor\'s own interns', function () {
    $supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);
    $otherSupervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);

    $program = Program::factory()->create(['category' => 'professional-internship']);
    $otherProgram = Program::factory()->create(['category' => 'professional-internship']);

    $cohort = Cohort::query()->create([
        'name' => 'Batch', 'slug' => 'batch-'.uniqid(), 'status' => Cohort::STATUS_ACTIVE,
        'start_date' => '2026-08-01', 'end_date' => '2026-09-30', 'timezone' => 'UTC',
    ]);
    $cohort->programs()->attach($program->id, ['supervisor_id' => $supervisor->id]);
    $cohort->programs()->attach($otherProgram->id, ['supervisor_id' => $otherSupervisor->id]);

    $myIntern = Internship::factory()->create([
        'cohort_id' => $cohort->id, 'program_id' => $program->id,
        'user_id' => User::factory()->create()->id,
    ]);
    Internship::factory()->create([
        'cohort_id' => $cohort->id, 'program_id' => $otherProgram->id,
        'user_id' => User::factory()->create()->id,
    ]);

    $this->actingAs($supervisor)
        ->get("/supervisor/cohorts/{$cohort->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Internships/Supervisor/Cohorts/Show')
            ->where('cohort.id', $cohort->id)
            ->has('interns', 1)
            ->where('interns.0.id', $myIntern->id));
});

it('forbids a supervisor from viewing a cohort they have no interns in', function () {
    $supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);

    $cohort = Cohort::query()->create([
        'name' => 'Other Batch', 'slug' => 'other-batch-'.uniqid(), 'status' => Cohort::STATUS_ACTIVE,
        'start_date' => '2026-08-01', 'end_date' => '2026-09-30', 'timezone' => 'UTC',
    ]);

    $this->actingAs($supervisor)
        ->get("/supervisor/cohorts/{$cohort->id}")
        ->assertStatus(403);
});

it('shows a plain student an empty cohort list since they supervise nobody', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    $this->actingAs($user)
        ->get('/supervisor/cohorts')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('cohorts', 0));
});

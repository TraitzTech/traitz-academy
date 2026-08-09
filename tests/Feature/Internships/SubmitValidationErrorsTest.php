<?php

use App\Models\Cohort;
use App\Models\Internship;
use App\Models\Program;
use App\Models\User;

function makeSupervisedProgram(User $supervisor): array
{
    $program = Program::factory()->create(['category' => 'professional-internship']);
    $cohort = Cohort::query()->create([
        'name' => 'Batch', 'slug' => 'batch-'.uniqid(), 'status' => Cohort::STATUS_ACTIVE,
        'start_date' => '2026-08-01', 'end_date' => '2026-09-30', 'timezone' => 'UTC',
    ]);
    $cohort->programs()->attach($program->id, ['supervisor_id' => $supervisor->id]);

    return [$program, $cohort];
}

it('redirects back with a field error instead of a raw 422 page when no student is selected for a schedule', function () {
    $supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);
    [$program] = makeSupervisedProgram($supervisor);
    Internship::factory()->create([
        'program_id' => $program->id,
        'user_id' => User::factory()->create()->id,
    ]);

    $response = $this->actingAs($supervisor)->post('/tutor/schedules', [
        'title' => 'Live class',
        'attachable_type' => 'program',
        'attachable_id' => $program->id,
        'audience' => 'selected_students',
        'student_ids' => [],
        'starts_at' => '2026-08-10 20:00',
    ]);

    $response->assertSessionHasErrors('student_ids');
    $response->assertStatus(302);
});

it('redirects back with a field error instead of a raw 422 page when no student is selected for a task', function () {
    $supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);
    [$program] = makeSupervisedProgram($supervisor);
    Internship::factory()->create([
        'program_id' => $program->id,
        'user_id' => User::factory()->create()->id,
    ]);

    $response = $this->actingAs($supervisor)->post('/tutor/assignments', [
        'title' => 'Task',
        'instructions' => 'Do the thing.',
        'attachable_type' => 'program',
        'attachable_id' => $program->id,
        'audience' => 'selected_students',
        'student_ids' => [],
    ]);

    $response->assertSessionHasErrors('student_ids');
    $response->assertStatus(302);
});

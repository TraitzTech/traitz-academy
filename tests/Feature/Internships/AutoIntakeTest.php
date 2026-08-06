<?php

use App\Actions\Internships\CreateInternshipFromApplication;
use App\Models\Application;
use App\Models\Cohort;
use App\Models\Program;
use App\Models\User;

function makeIntakeProgram(?User $supervisor = null): array
{
    $program = Program::factory()->create(['category' => 'professional-internship']);
    $cohort = Cohort::query()->create([
        'name' => 'Intake 2026',
        'start_date' => now(),
        'end_date' => now()->addMonths(3),
        'status' => Cohort::STATUS_ACTIVE,
        'is_intake' => true,
        'timezone' => 'Africa/Douala',
    ]);
    $cohort->programs()->attach($program->id, ['supervisor_id' => $supervisor?->id]);

    return [$program, $cohort];
}

it('auto-places an accepted internship applicant into the program intake cohort under its supervisor', function () {
    $supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);
    [$program, $cohort] = makeIntakeProgram($supervisor);

    $user = User::factory()->create();
    $application = Application::factory()->create([
        'program_id' => $program->id,
        'user_id' => $user->id,
        'status' => 'accepted',
    ]);

    $internship = app(CreateInternshipFromApplication::class)->execute($application);

    expect($internship->cohort_id)->toBe($cohort->id)
        ->and($internship->effectiveSupervisorId())->toBe($supervisor->id)
        ->and($internship->start_date->toDateString())->toBe($cohort->start_date->toDateString());
});

it('falls back to a standalone internship when the program has no intake cohort', function () {
    $program = Program::factory()->create(['category' => 'academic-internship']);
    $user = User::factory()->create();
    $application = Application::factory()->create([
        'program_id' => $program->id,
        'user_id' => $user->id,
        'status' => 'accepted',
    ]);

    $internship = app(CreateInternshipFromApplication::class)->execute($application);

    expect($internship->cohort_id)->toBeNull()
        ->and($internship->program_id)->toBe($program->id);
});

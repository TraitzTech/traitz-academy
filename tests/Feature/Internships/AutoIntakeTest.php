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

it('does not auto-place into a completed cohort, falling back to standalone', function () {
    [$program, $cohort] = makeIntakeProgram();
    $cohort->update(['status' => Cohort::STATUS_COMPLETED]);

    $user = User::factory()->create();
    $application = Application::factory()->create([
        'program_id' => $program->id,
        'user_id' => $user->id,
        'status' => 'accepted',
    ]);

    $internship = app(CreateInternshipFromApplication::class)->execute($application);

    expect($internship->cohort_id)->toBeNull();
});

it('does not auto-place once the intake window has closed', function () {
    [$program, $cohort] = makeIntakeProgram();
    $cohort->update([
        'intake_opens_at' => now()->subMonths(2)->toDateString(),
        'intake_closes_at' => now()->subDay()->toDateString(),
    ]);

    $user = User::factory()->create();
    $application = Application::factory()->create([
        'program_id' => $program->id,
        'user_id' => $user->id,
        'status' => 'accepted',
    ]);

    $internship = app(CreateInternshipFromApplication::class)->execute($application);

    expect($internship->cohort_id)->toBeNull();
});

it('still auto-places while the intake window is open', function () {
    [$program, $cohort] = makeIntakeProgram();
    $cohort->update([
        'intake_opens_at' => now()->subDay()->toDateString(),
        'intake_closes_at' => now()->addWeek()->toDateString(),
    ]);

    $user = User::factory()->create();
    $application = Application::factory()->create([
        'program_id' => $program->id,
        'user_id' => $user->id,
        'status' => 'accepted',
    ]);

    $internship = app(CreateInternshipFromApplication::class)->execute($application);

    expect($internship->cohort_id)->toBe($cohort->id);
});

it('refuses to place into an explicitly-chosen closed cohort', function () {
    [$program, $cohort] = makeIntakeProgram();
    $cohort->update(['status' => Cohort::STATUS_CANCELLED]);

    $user = User::factory()->create();
    $application = Application::factory()->create([
        'program_id' => $program->id,
        'user_id' => $user->id,
        'status' => 'accepted',
    ]);

    expect(fn () => app(CreateInternshipFromApplication::class)->execute($application, $cohort))
        ->toThrow(RuntimeException::class);
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

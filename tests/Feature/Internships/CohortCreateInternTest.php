<?php

use App\Models\Cohort;
use App\Models\Program;
use App\Models\User;

function makeCohortWithProgramForManualAdd(): array
{
    $program = Program::factory()->create(['category' => 'professional-internship']);
    $cohort = Cohort::query()->create([
        'name' => 'Batch',
        'slug' => 'batch-'.uniqid(),
        'status' => Cohort::STATUS_UPCOMING,
        'start_date' => '2026-08-04',
        'end_date' => '2026-08-31',
        'timezone' => 'UTC',
    ]);
    $cohort->programs()->attach($program->id, ['supervisor_id' => null]);

    return [$cohort, $program];
}

it('lets an admin manually place an existing user into an open cohort', function () {
    $admin = User::factory()->create(['role' => User::ROLE_CTO]);
    [$cohort, $program] = makeCohortWithProgramForManualAdd();
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->post("/admin/internships/cohorts/{$cohort->id}/interns/manual", [
            'program_id' => $program->id,
            'mode' => 'existing',
            'user_id' => $user->id,
        ])
        ->assertRedirect();

    expect(\App\Models\Internship::where('cohort_id', $cohort->id)->where('user_id', $user->id)->exists())->toBeTrue();
});

it('rejects manually placing an intern into a closed cohort', function () {
    $admin = User::factory()->create(['role' => User::ROLE_CTO]);
    [$cohort, $program] = makeCohortWithProgramForManualAdd();
    $cohort->update(['status' => Cohort::STATUS_CANCELLED]);
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->post("/admin/internships/cohorts/{$cohort->id}/interns/manual", [
            'program_id' => $program->id,
            'mode' => 'existing',
            'user_id' => $user->id,
        ])
        ->assertStatus(422);

    expect(\App\Models\Internship::where('cohort_id', $cohort->id)->where('user_id', $user->id)->exists())->toBeFalse();
});

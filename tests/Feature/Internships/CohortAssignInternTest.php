<?php

use App\Models\Cohort;
use App\Models\Internship;
use App\Models\Program;
use App\Models\User;

function makeCohortWithProgram(): array
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

it('adds several unassigned interns to a cohort in one request', function () {
    $admin = User::factory()->create(['role' => User::ROLE_CTO]);
    [$cohort, $program] = makeCohortWithProgram();

    $interns = Internship::factory()->count(3)->create([
        'program_id' => $program->id,
        'cohort_id' => null,
    ]);

    $this->actingAs($admin)
        ->post("/admin/internships/cohorts/{$cohort->id}/interns", [
            'internship_ids' => $interns->pluck('id')->all(),
        ])
        ->assertRedirect();

    expect(Internship::whereIn('id', $interns->pluck('id'))->where('cohort_id', $cohort->id)->count())->toBe(3);
});

it('rejects adding an intern whose program is not run by the cohort', function () {
    $admin = User::factory()->create(['role' => User::ROLE_CTO]);
    [$cohort] = makeCohortWithProgram();
    $otherProgram = Program::factory()->create(['category' => 'professional-internship']);

    $intern = Internship::factory()->create(['program_id' => $otherProgram->id, 'cohort_id' => null]);

    $this->actingAs($admin)
        ->post("/admin/internships/cohorts/{$cohort->id}/interns", [
            'internship_ids' => [$intern->id],
        ])
        ->assertStatus(422);

    expect($intern->fresh()->cohort_id)->toBeNull();
});

it('skips a user already in the cohort instead of hitting the unique constraint', function () {
    $admin = User::factory()->create(['role' => User::ROLE_CTO]);
    [$cohort, $program] = makeCohortWithProgram();
    $user = User::factory()->create();

    // Same user already assigned to the cohort under this program.
    Internship::factory()->create(['program_id' => $program->id, 'cohort_id' => $cohort->id, 'user_id' => $user->id]);
    // A second, standalone record for the same user (e.g. a second application).
    $standalone = Internship::factory()->create(['program_id' => $program->id, 'cohort_id' => null, 'user_id' => $user->id]);

    $this->actingAs($admin)
        ->post("/admin/internships/cohorts/{$cohort->id}/interns", [
            'internship_ids' => [$standalone->id],
        ])
        ->assertRedirect();

    // The standalone record stays unassigned — no duplicate, no 500.
    expect($standalone->fresh()->cohort_id)->toBeNull();
    expect(Internship::where('cohort_id', $cohort->id)->where('user_id', $user->id)->count())->toBe(1);
});

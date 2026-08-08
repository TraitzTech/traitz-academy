<?php

use App\Models\Cohort;
use App\Models\Program;
use App\Models\User;

it('defaults the timezone when the create form submits an empty value', function () {
    $admin = User::factory()->create(['role' => User::ROLE_CTO]);
    $program = Program::factory()->create(['category' => 'professional-internship']);

    // Mirrors the real bug: the form field is present but empty, so the
    // 'nullable' validation rule lets it through as an explicit null,
    // which used to override the DB column default and fail the NOT NULL
    // constraint on `cohorts.timezone`.
    $this->actingAs($admin)->post('/admin/internships/cohorts', [
        'name' => 'Traittz Hort 7.0',
        'start_date' => '2026-08-04',
        'end_date' => '2026-08-31',
        'timezone' => '',
        'programs' => [['program_id' => $program->id, 'supervisor_id' => null]],
    ])->assertRedirect();

    $cohort = Cohort::query()->latest('id')->first();
    expect($cohort->timezone)->not->toBeNull()->not->toBe('');
});

it('keeps an existing timezone when the update form submits an empty value', function () {
    $admin = User::factory()->create(['role' => User::ROLE_CTO]);
    $program = Program::factory()->create(['category' => 'professional-internship']);
    $cohort = Cohort::query()->create([
        'name' => 'Existing Batch',
        'slug' => 'existing-batch-'.uniqid(),
        'status' => Cohort::STATUS_UPCOMING,
        'start_date' => '2026-08-04',
        'end_date' => '2026-08-31',
        'timezone' => 'Africa/Douala',
    ]);

    $this->actingAs($admin)->put("/admin/internships/cohorts/{$cohort->id}", [
        'name' => $cohort->name,
        'start_date' => $cohort->start_date?->toDateString(),
        'end_date' => $cohort->end_date?->toDateString(),
        'timezone' => '',
        'programs' => [['program_id' => $program->id, 'supervisor_id' => null]],
    ])->assertRedirect();

    expect($cohort->fresh()->timezone)->toBe('Africa/Douala');
});

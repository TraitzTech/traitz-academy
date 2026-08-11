<?php

use App\Models\Program;
use App\Models\User;

it('stamps the cohort intake window onto every program in the cohort', function () {
    $admin = User::factory()->create(['role' => User::ROLE_CTO]);
    $p1 = Program::factory()->create(['category' => 'professional-internship']);
    $p2 = Program::factory()->create(['category' => 'academic-internship']);

    $this->actingAs($admin)->post('/admin/internships/cohorts', [
        'name' => 'February Batch',
        'start_date' => '2026-02-01',
        'end_date' => '2026-04-30',
        'intake_opens_at' => '2026-01-01',
        'intake_closes_at' => '2026-01-31',
        'programs' => [
            ['program_id' => $p1->id, 'supervisor_id' => null],
            ['program_id' => $p2->id, 'supervisor_id' => null],
        ],
    ])->assertRedirect();

    expect($p1->fresh()->applications_open_at->toDateString())->toBe('2026-01-01')
        ->and($p1->fresh()->applications_close_at->toDateString())->toBe('2026-01-31')
        ->and($p2->fresh()->applications_open_at->toDateString())->toBe('2026-01-01')
        ->and($p2->fresh()->applications_close_at->toDateString())->toBe('2026-01-31');
});

it('leaves program windows untouched when the cohort has no intake window', function () {
    $admin = User::factory()->create(['role' => User::ROLE_CTO]);
    $program = Program::factory()->create([
        'category' => 'professional-internship',
        'applications_open_at' => null,
        'applications_close_at' => null,
    ]);

    $this->actingAs($admin)->post('/admin/internships/cohorts', [
        'name' => 'Rolling Batch',
        'start_date' => '2026-02-01',
        'end_date' => '2026-04-30',
        'programs' => [['program_id' => $program->id, 'supervisor_id' => null]],
    ])->assertRedirect();

    expect($program->fresh()->applications_open_at)->toBeNull()
        ->and($program->fresh()->applications_close_at)->toBeNull();
});

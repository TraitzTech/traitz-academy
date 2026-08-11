<?php

use App\Models\Cohort;
use App\Models\Internship;
use App\Models\LogbookEntry;
use App\Models\Program;
use App\Models\User;

it('shows a supervisor an overview of their interns with review counts', function () {
    $supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);

    $program = Program::factory()->create(['category' => 'professional-internship']);
    $cohort = Cohort::query()->create([
        'name' => 'Batch', 'slug' => 'batch-'.uniqid(), 'status' => Cohort::STATUS_ACTIVE,
        'start_date' => '2026-08-01', 'end_date' => '2026-09-30', 'timezone' => 'UTC',
    ]);
    $cohort->programs()->attach($program->id, ['supervisor_id' => $supervisor->id]);

    $internship = Internship::factory()->create([
        'cohort_id' => $cohort->id, 'program_id' => $program->id,
        'user_id' => User::factory()->create()->id,
    ]);

    // One submitted entry awaiting review.
    LogbookEntry::query()->create([
        'internship_id' => $internship->id, 'date' => '2026-08-05',
        'content' => 'Did work.', 'status' => LogbookEntry::STATUS_SUBMITTED, 'submitted_at' => now(),
    ]);

    $this->actingAs($supervisor)
        ->get('/supervisor/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Internships/Supervisor/Dashboard')
            ->where('stats.interns', 1)
            ->where('stats.pending_reviews', 1)
            ->has('attention', 1));
});

it('redirects a supervisor from the student dashboard to their own', function () {
    $supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);

    $this->actingAs($supervisor)
        ->get('/dashboard')
        ->assertRedirect('/supervisor/dashboard');
});

it('forbids a plain student from the supervisor dashboard', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    $this->actingAs($user)->get('/supervisor/dashboard')->assertStatus(403);
});

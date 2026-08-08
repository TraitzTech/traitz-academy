<?php

use App\Models\Internship;
use App\Models\Program;
use App\Models\User;

it('reports office days correctly for a program with a fixed schedule', function () {
    $program = Program::factory()->create(['office_days' => [2, 4]]); // Tue, Thu

    expect($program->isOfficeDay(Carbon\Carbon::parse('2026-08-04')))->toBeTrue(); // Tuesday
    expect($program->isOfficeDay(Carbon\Carbon::parse('2026-08-06')))->toBeTrue(); // Thursday
    expect($program->isOfficeDay(Carbon\Carbon::parse('2026-08-05')))->toBeFalse(); // Wednesday
    expect($program->officeDaysLabel())->toBe('Tue, Thu');
});

it('has no office-day schedule when office_days is unset', function () {
    $program = Program::factory()->create(['office_days' => null]);

    expect($program->isOfficeDay(Carbon\Carbon::parse('2026-08-04')))->toBeFalse();
    expect($program->officeDaysLabel())->toBeNull();
});

it('falls back to a null schedule on the intern dashboard when the program has no office_days', function () {
    $user = User::factory()->create();
    $program = Program::factory()->create(['office_days' => null, 'category' => 'professional-internship']);
    Internship::factory()->create(['user_id' => $user->id, 'status' => 'active', 'program_id' => $program->id]);

    $this->actingAs($user)
        ->get('/dashboard/internship')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('todayIsOfficeDay', null)
            ->where('officeDaysLabel', null)
        );
});

it('reflects the program office-day schedule on the intern dashboard', function () {
    $user = User::factory()->create();
    $program = Program::factory()->create(['office_days' => [2, 4], 'category' => 'professional-internship']);
    Internship::factory()->create(['user_id' => $user->id, 'status' => 'active', 'program_id' => $program->id]);

    Carbon\Carbon::setTestNow(Carbon\Carbon::parse('2026-08-04 09:00:00')); // a Tuesday

    $this->actingAs($user)
        ->get('/dashboard/internship')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('todayIsOfficeDay', true)
            ->where('officeDaysLabel', 'Tue, Thu')
        );

    Carbon\Carbon::setTestNow();
});

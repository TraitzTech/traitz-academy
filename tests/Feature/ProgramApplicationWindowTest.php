<?php

use App\Models\Program;

it('is open when there is no window and the program is active', function () {
    $program = Program::factory()->create(['is_active' => true, 'applications_open_at' => null, 'applications_close_at' => null]);

    expect($program->applicationsOpen())->toBeTrue()
        ->and($program->applicationStatus())->toBe('open');
});

it('is closed before the window opens', function () {
    $program = Program::factory()->create([
        'is_active' => true,
        'applications_open_at' => now()->addWeek(),
        'applications_close_at' => now()->addWeeks(4),
    ]);

    expect($program->applicationsOpen())->toBeFalse()
        ->and($program->applicationStatus())->toBe('not_yet');
});

it('is open within the window and closed after it', function () {
    $open = Program::factory()->create([
        'is_active' => true,
        'applications_open_at' => now()->subDay(),
        'applications_close_at' => now()->addWeek(),
    ]);
    $closed = Program::factory()->create([
        'is_active' => true,
        'applications_open_at' => now()->subWeeks(4),
        'applications_close_at' => now()->subDay(),
    ]);

    expect($open->applicationsOpen())->toBeTrue()
        ->and($closed->applicationsOpen())->toBeFalse()
        ->and($closed->applicationStatus())->toBe('closed');
});

it('is closed when the program is inactive regardless of window', function () {
    $program = Program::factory()->create([
        'is_active' => false,
        'applications_open_at' => now()->subDay(),
        'applications_close_at' => now()->addWeek(),
    ]);

    expect($program->applicationsOpen())->toBeFalse();
});

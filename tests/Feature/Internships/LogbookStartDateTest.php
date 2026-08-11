<?php

use App\Models\Internship;
use Carbon\Carbon;

it('counts missed days from logbook_starts_on, not the earlier start_date', function () {
    Carbon::setTestNow('2026-08-07'); // a Friday

    // Internship "began" months ago but logbook tracking only starts this week.
    $internship = Internship::factory()->create([
        'start_date' => '2026-01-05',
        'logbook_starts_on' => '2026-08-03', // Monday of the current week
        'status' => Internship::STATUS_ACTIVE,
    ]);

    // Mon–Fri of this week = 5 working days elapsed, none submitted.
    expect($internship->workingDaysElapsed())->toBe(5);
    expect($internship->missedLogbookDaysCount())->toBe(5);

    Carbon::setTestNow();
});

it('falls back to start_date when logbook_starts_on is null', function () {
    Carbon::setTestNow('2026-08-07');

    $internship = Internship::factory()->create([
        'start_date' => '2026-08-03',
        'logbook_starts_on' => null,
        'status' => Internship::STATUS_ACTIVE,
    ]);

    expect($internship->workingDaysElapsed())->toBe(5);

    Carbon::setTestNow();
});

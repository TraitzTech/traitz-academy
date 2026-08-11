<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('lms:send-instalment-reminders')->dailyAt('08:00');

Schedule::command('internship:send-logbook-reminders')
    ->dailyAt(config('internship.logbook.reminder_time', '20:00'))
    ->withoutOverlapping();

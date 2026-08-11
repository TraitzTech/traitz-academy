<?php

namespace App\Console\Commands;

use App\Models\Internship;
use App\Notifications\Internships\LogbookReminderNotification;
use Illuminate\Console\Command;

class SendLogbookReminders extends Command
{
    protected $signature = 'internship:send-logbook-reminders';

    protected $description = "Remind interns who haven't submitted today's logbook entry, on working days only";

    public function handle(): int
    {
        $count = 0;

        Internship::query()
            ->active()
            ->with('intern')
            ->chunkById(50, function ($internships) use (&$count) {
                foreach ($internships as $internship) {
                    $intern = $internship->intern;
                    if (! $intern) {
                        continue;
                    }

                    $today = now($internship->timezone());

                    // Don't nag before logbook expectations have begun.
                    $logbookStart = $internship->effectiveLogbookStart();
                    if ($logbookStart && $today->copy()->startOfDay()->lessThan($logbookStart->copy()->timezone($internship->timezone())->startOfDay())) {
                        continue;
                    }

                    if (! $internship->isWorkingDay($today)) {
                        continue;
                    }

                    if ($internship->hasLogbookEntryFor($today)) {
                        continue;
                    }

                    $intern->notify(new LogbookReminderNotification($internship));
                    $count++;
                }
            });

        $this->info("Sent {$count} logbook reminder(s).");

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Application;
use App\Models\Internship;
use App\Models\Program;
use Illuminate\Console\Command;

class BackfillInternshipsFromApplications extends Command
{
    protected $signature = 'internships:backfill-from-applications {--dry-run : Show what would be created without writing}';

    protected $description = 'Create standalone internship records for accepted internship applicants that predate auto-creation, so they can be placed into cohorts.';

    public function handle(): int
    {
        $internshipProgramIds = Program::query()->internships()->pluck('id');

        $applications = Application::query()
            ->where('status', 'accepted')
            ->whereIn('program_id', $internshipProgramIds)
            ->whereNotNull('user_id')
            ->whereDoesntHave('internship')
            ->get();

        if ($applications->isEmpty()) {
            $this->info('Nothing to backfill — every accepted internship applicant already has an internship record.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $this->info(($dryRun ? '[dry run] ' : '')."Found {$applications->count()} accepted applicant(s) without an internship record.");

        $created = 0;
        $skipped = 0;
        foreach ($applications as $application) {
            $this->line("  • {$application->first_name} {$application->last_name} — program #{$application->program_id} (accepted ".optional($application->reviewed_at)->toDateString().')');

            if ($dryRun) {
                continue;
            }

            // A user may have more than one accepted application for the same
            // program (a re-application) — only one of them should have ever
            // become an internship. Dedupe by (user, program), not just by
            // whether *this* application already has one linked.
            if (Internship::where('user_id', $application->user_id)->where('program_id', $application->program_id)->exists()) {
                $skipped++;

                continue;
            }

            // Left standalone (cohort_id null) so an admin places them into the
            // right cohort via the UI. The original acceptance date is
            // preserved through the linked application.
            Internship::query()->firstOrCreate(
                ['application_id' => $application->id],
                [
                    'program_id' => $application->program_id,
                    'user_id' => $application->user_id,
                    'cohort_id' => null,
                    'status' => Internship::STATUS_ACTIVE,
                ],
            );
            $created++;
        }

        if ($skipped > 0) {
            $this->comment("Skipped {$skipped} application(s) whose applicant already has an internship in that program.");
        }

        if ($dryRun) {
            $this->warn('Dry run — no records written. Re-run without --dry-run to apply.');
        } else {
            $this->info("Done. Created {$created} standalone internship record(s). They now appear under each cohort's \"From applications\" tab.");
        }

        return self::SUCCESS;
    }
}

<?php

namespace App\Actions\Internships;

use App\Models\Application;
use App\Models\Cohort;
use App\Models\Internship;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Turns an accepted internship application into an Internship engagement.
 *
 * Supervisor precedence: an explicit supervisor wins, otherwise the cohort's
 * lead supervisor is inherited (resolved lazily via Internship::effectiveSupervisorId,
 * so we only persist an override when one is given). Idempotent — re-running for
 * the same intern in the same cohort returns the existing record.
 */
class CreateInternshipFromApplication
{
    public function execute(Application $application, ?Cohort $cohort = null, ?int $supervisorId = null): Internship
    {
        $application->loadMissing('program');

        if ($application->program === null || ! $application->program->isInternship()) {
            throw new RuntimeException('Only internship-program applications can become internships.');
        }

        if ($application->user_id === null) {
            throw new RuntimeException('The applicant must have a user account before an internship can be created.');
        }

        if ($cohort !== null && ! $cohort->hasProgram((int) $application->program_id)) {
            throw new RuntimeException('The chosen cohort does not run this program.');
        }

        // No cohort given → auto-place into the current intake cohort, provided it
        // runs the applicant's program (so the intern inherits that program's
        // supervisor). Falls back to standalone otherwise.
        if ($cohort === null) {
            $cohort = Cohort::query()
                ->intake()
                ->openForIntake()
                ->whereHas('programs', fn ($q) => $q->where('programs.id', $application->program_id))
                ->latest('id')
                ->first();
        }

        return DB::transaction(function () use ($application, $cohort, $supervisorId) {
            $internship = Internship::query()->firstOrNew([
                'cohort_id' => $cohort?->id,
                'user_id' => $application->user_id,
            ]);

            // Preserve an existing record's data; only fill on first creation.
            if (! $internship->exists) {
                $internship->fill([
                    'program_id' => $application->program_id,
                    'application_id' => $application->id,
                    'supervisor_id' => $supervisorId,
                    'start_date' => $cohort?->start_date,
                    'end_date' => $cohort?->end_date,
                    'status' => Internship::STATUS_ACTIVE,
                ]);
                $internship->save();
            } elseif ($supervisorId !== null && $internship->supervisor_id === null) {
                // Allow assigning a per-intern supervisor on a later pass.
                $internship->update(['supervisor_id' => $supervisorId]);
            }

            return $internship;
        });
    }
}

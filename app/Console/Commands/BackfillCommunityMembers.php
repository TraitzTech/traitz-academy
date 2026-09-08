<?php

namespace App\Console\Commands;

use App\Services\Tac\CommunityEnrollmentService;
use App\Services\Tac\RegistrationMemberMapper;
use Illuminate\Console\Command;

/**
 * Brings everyone who already registered for anything at Traitz Academy into
 * the community, retroactively.
 *
 * Silent by default: nobody who registered months ago gets an unexpected email
 * out of the blue. Pass --notify only for a deliberate announcement.
 */
class BackfillCommunityMembers extends Command
{
    protected $signature = 'tac:backfill-members
                            {--notify : Send the TAC welcome email to each newly created member}
                            {--dry-run : Report what would happen without writing anything}';

    protected $description = 'Auto-include all existing applicants, event registrants, students and interns in the Traitz Academy Community';

    public function handle(RegistrationMemberMapper $mapper, CommunityEnrollmentService $enrollment): int
    {
        $notify = (bool) $this->option('notify');
        $dryRun = (bool) $this->option('dry-run');

        if ($notify && ! $dryRun && ! $this->confirmToProceed()) {
            return self::FAILURE;
        }

        $totals = ['scanned' => 0, 'enrolled' => 0, 'skipped' => 0];

        foreach (RegistrationMemberMapper::OBSERVED_MODELS as $modelClass) {
            $label = class_basename($modelClass);
            $count = $modelClass::query()->count();

            if ($count === 0) {
                $this->line("  <fg=gray>{$label}: nothing to do</>");

                continue;
            }

            $this->info("Processing {$count} {$label} record(s)...");
            $bar = $this->output->createProgressBar($count);

            $modelClass::query()
                ->with($this->eagerLoadsFor($modelClass))
                ->chunkById(200, function ($records) use ($mapper, $enrollment, $notify, $dryRun, &$totals, $bar) {
                    foreach ($records as $record) {
                        $totals['scanned']++;
                        $bar->advance();

                        $mapped = $mapper->map($record);

                        if ($mapped === null) {
                            $totals['skipped']++;

                            continue;
                        }

                        if ($dryRun) {
                            $totals['enrolled']++;

                            continue;
                        }

                        $member = $enrollment->record(
                            email: $mapped['email'],
                            attributes: $mapped['attributes'],
                            source: $mapped['source'],
                            sourceable: $record,
                            notify: $notify,
                            context: $mapped['context'],
                        );

                        $member ? $totals['enrolled']++ : $totals['skipped']++;
                    }
                });

            $bar->finish();
            $this->newLine(2);
        }

        $this->newLine();
        $this->table(
            ['Records scanned', 'Members created or enriched', 'Skipped (no usable email)'],
            [[$totals['scanned'], $totals['enrolled'], $totals['skipped']]],
        );

        if ($dryRun) {
            $this->comment('Dry run — nothing was written.');
        } else {
            $this->info('Backfill complete.'.($notify ? '' : ' No emails were sent.'));
        }

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    protected function eagerLoadsFor(string $modelClass): array
    {
        return match (class_basename($modelClass)) {
            'Application' => ['program'],
            'EventRegistration' => ['event'],
            'AiForgeRegistration' => ['event'],
            'Enrollment' => ['user', 'course'],
            'Internship' => ['intern', 'program'],
            default => [],
        };
    }

    protected function confirmToProceed(): bool
    {
        return $this->confirm(
            'This will email every person newly added to TAC. That could be a large, unannounced send. Continue?',
            false,
        );
    }
}

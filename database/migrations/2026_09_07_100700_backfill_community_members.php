<?php

use App\Services\Tac\CommunityEnrollmentService;
use App\Services\Tac\RegistrationMemberMapper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Retroactively include everyone who has already registered for anything at
     * Traitz Academy — applicants, event registrants, AI Forge registrants,
     * enrolled students and interns — in the community.
     *
     * Deliberately silent: existing people are added without an email so
     * nobody gets an unexpected blast on deploy. Announce on your own terms
     * from the admin panel, or run `php artisan tac:backfill-members --notify`.
     */
    public function up(): void
    {
        $mapper = app(RegistrationMemberMapper::class);
        $enrollment = app(CommunityEnrollmentService::class);

        foreach (RegistrationMemberMapper::OBSERVED_MODELS as $modelClass) {
            if (! Schema::hasTable((new $modelClass)->getTable())) {
                continue;
            }

            $modelClass::query()->chunkById(200, function ($records) use ($mapper, $enrollment) {
                foreach ($records as $record) {
                    $mapped = $mapper->map($record);

                    if ($mapped === null) {
                        continue;
                    }

                    $enrollment->record(
                        email: $mapped['email'],
                        attributes: $mapped['attributes'],
                        source: $mapped['source'],
                        sourceable: $record,
                        notify: false,
                        context: $mapped['context'],
                    );
                }
            });
        }
    }

    public function down(): void
    {
        // Members are dropped with the community_members table itself; leaving
        // this a no-op avoids deleting people an admin has since curated.
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catch-up data migration: create standalone internship records for
     * accepted applicants who predate auto-creation, so they can be placed
     * into cohorts. Delegates to the idempotent command, so re-running (or
     * running against a fresh production import) never duplicates records.
     */
    public function up(): void
    {
        if (! Schema::hasTable('internships') || ! Schema::hasTable('applications')) {
            return;
        }

        Artisan::call('internships:backfill-from-applications');
    }

    public function down(): void
    {
        // No-op: the created internship records are real operational data and
        // should not be torn down by rolling this migration back.
    }
};

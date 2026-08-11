<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internships', function (Blueprint $table) {
            // When logbook expectations begin. Separate from start_date (which
            // records when the engagement actually began, for duration/reporting)
            // so interns onboarded mid-stream — e.g. accepted before the logbook
            // feature existed — don't accrue retroactive "missed" days. Null =
            // fall back to start_date (the original behaviour).
            $table->date('logbook_starts_on')->nullable()->after('start_date');
        });
    }

    public function down(): void
    {
        Schema::table('internships', function (Blueprint $table) {
            $table->dropColumn('logbook_starts_on');
        });
    }
};

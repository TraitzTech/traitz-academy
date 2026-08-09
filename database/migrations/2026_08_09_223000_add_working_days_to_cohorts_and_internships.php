<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cohorts', function (Blueprint $table) {
            // Carbon ISO weekday numbers (1=Mon..7=Sun). Null = use config('internship.logbook.working_days').
            $table->json('working_days')->nullable()->after('timezone');
        });

        Schema::table('internships', function (Blueprint $table) {
            // Per-intern override. Null = use the cohort's working_days, then the config default.
            $table->json('working_days')->nullable()->after('work_mode');
        });
    }

    public function down(): void
    {
        Schema::table('cohorts', function (Blueprint $table) {
            $table->dropColumn('working_days');
        });

        Schema::table('internships', function (Blueprint $table) {
            $table->dropColumn('working_days');
        });
    }
};

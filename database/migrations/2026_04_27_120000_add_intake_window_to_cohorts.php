<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cohorts', function (Blueprint $table) {
            // Application window for the cohort's intake. Saving stamps these
            // onto the application window of every program in the cohort, so
            // internship dates are entered once (blank = leave programs as-is).
            $table->date('intake_opens_at')->nullable()->after('end_date');
            $table->date('intake_closes_at')->nullable()->after('intake_opens_at');
        });
    }

    public function down(): void
    {
        Schema::table('cohorts', function (Blueprint $table) {
            $table->dropColumn(['intake_opens_at', 'intake_closes_at']);
        });
    }
};

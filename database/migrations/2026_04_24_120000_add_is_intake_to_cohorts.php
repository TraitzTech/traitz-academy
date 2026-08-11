<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cohorts', function (Blueprint $table) {
            // The cohort that newly-accepted applicants for this program are
            // auto-placed into. At most one intake cohort per program.
            $table->boolean('is_intake')->default(false)->after('status');
            $table->index(['program_id', 'is_intake']);
        });
    }

    public function down(): void
    {
        Schema::table('cohorts', function (Blueprint $table) {
            $table->dropIndex(['program_id', 'is_intake']);
            $table->dropColumn('is_intake');
        });
    }
};

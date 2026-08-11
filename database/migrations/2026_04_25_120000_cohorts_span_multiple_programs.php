<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A cohort now spans multiple programs; each pairing has its own supervisor.
        Schema::create('cohort_program', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cohort_id')->constrained('cohorts')->cascadeOnDelete();
            $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['cohort_id', 'program_id']);
        });

        // Carry existing single-program cohorts into the pivot.
        foreach (DB::table('cohorts')->get() as $cohort) {
            if (! empty($cohort->program_id)) {
                DB::table('cohort_program')->insert([
                    'cohort_id' => $cohort->id,
                    'program_id' => $cohort->program_id,
                    'supervisor_id' => $cohort->supervisor_id ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        Schema::table('cohorts', function (Blueprint $table) {
            $table->dropForeign(['program_id']);
            $table->dropForeign(['supervisor_id']);
        });

        Schema::table('cohorts', function (Blueprint $table) {
            $table->dropUnique('cohorts_program_id_slug_unique');
            $table->dropIndex('cohorts_program_id_status_index');
            $table->dropIndex('cohorts_program_id_is_intake_index');
            $table->dropColumn(['program_id', 'supervisor_id', 'capacity']);
            $table->index(['is_intake']);
        });
    }

    public function down(): void
    {
        Schema::table('cohorts', function (Blueprint $table) {
            $table->dropIndex(['is_intake']);
            $table->foreignId('program_id')->nullable()->after('id')->constrained('programs')->cascadeOnDelete();
            $table->foreignId('supervisor_id')->nullable()->after('program_id')->constrained('users')->nullOnDelete();
            $table->unsignedInteger('capacity')->nullable();
        });

        Schema::dropIfExists('cohort_program');
    }
};

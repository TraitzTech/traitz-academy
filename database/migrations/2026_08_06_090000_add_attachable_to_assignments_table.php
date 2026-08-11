<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds a polymorphic attachable_type/attachable_id pair alongside the
     * existing course_id FK, so assignments can also target a Cohort or
     * Program. course_id is kept (and backfilled into attachable_*) during
     * the dual-write rollout window; it is dropped in a later cleanup
     * migration once every read/write path has switched over.
     */
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table): void {
            $table->string('attachable_type')->nullable()->after('course_id');
            $table->unsignedBigInteger('attachable_id')->nullable()->after('attachable_type');
            $table->index(['attachable_type', 'attachable_id']);
        });

        DB::table('assignments')
            ->whereNotNull('course_id')
            ->update([
                'attachable_type' => \App\Models\Course::class,
                'attachable_id' => DB::raw('course_id'),
            ]);
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table): void {
            $table->dropIndex(['attachable_type', 'attachable_id']);
            $table->dropColumn(['attachable_type', 'attachable_id']);
        });
    }
};

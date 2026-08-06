<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mirrors the assignments migration: adds attachable_type/attachable_id
     * alongside the existing nullable course_id FK, backfilled from it.
     */
    public function up(): void
    {
        Schema::table('lms_schedules', function (Blueprint $table): void {
            $table->string('attachable_type')->nullable()->after('course_id');
            $table->unsignedBigInteger('attachable_id')->nullable()->after('attachable_type');
            $table->index(['attachable_type', 'attachable_id']);
        });

        DB::table('lms_schedules')
            ->whereNotNull('course_id')
            ->update([
                'attachable_type' => \App\Models\Course::class,
                'attachable_id' => DB::raw('course_id'),
            ]);
    }

    public function down(): void
    {
        Schema::table('lms_schedules', function (Blueprint $table): void {
            $table->dropIndex(['attachable_type', 'attachable_id']);
            $table->dropColumn(['attachable_type', 'attachable_id']);
        });
    }
};

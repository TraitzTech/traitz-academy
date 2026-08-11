<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Polymorphic replacement for live_class_courses — lets a live class be
     * linked to any mix of Courses, Cohorts, and Programs (still
     * many-per-class). live_class_courses is kept and backfilled from
     * during the dual-write rollout window, dropped later once every
     * read/write path has switched to live_class_targets.
     */
    public function up(): void
    {
        Schema::create('live_class_targets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('live_class_id')->constrained('live_classes')->cascadeOnDelete();
            $table->string('target_type');
            $table->unsignedBigInteger('target_id');
            $table->timestamps();

            $table->index(['target_type', 'target_id']);
            $table->unique(['live_class_id', 'target_type', 'target_id']);
        });

        $courseLinks = DB::table('live_class_courses')->get(['live_class_id', 'course_id']);
        $now = now();

        if ($courseLinks->isNotEmpty()) {
            DB::table('live_class_targets')->insert(
                $courseLinks->map(fn ($link) => [
                    'live_class_id' => $link->live_class_id,
                    'target_type' => \App\Models\Course::class,
                    'target_id' => $link->course_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all()
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('live_class_targets');
    }
};

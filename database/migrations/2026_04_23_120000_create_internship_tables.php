<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A dated batch of interns for an internship program. Supervisors are
        // assigned here (inherited by each internship, overridable per intern).
        Schema::create('cohorts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('capacity')->nullable();
            // upcoming | active | completed | cancelled
            $table->string('status')->default('upcoming');
            $table->string('timezone')->default(config('app.timezone', 'UTC'));
            $table->decimal('expected_hours_per_day', 4, 1)->nullable();
            $table->timestamps();

            $table->unique(['program_id', 'slug']);
            $table->index(['program_id', 'status']);
            $table->index(['status', 'start_date']);
        });

        // One intern's engagement. cohort_id nullable so standalone (non-batch)
        // interns are supported; program_id is denormalised for direct querying.
        Schema::create('internships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cohort_id')->nullable()->constrained('cohorts')->nullOnDelete();
            $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('application_id')->nullable()->constrained('applications')->nullOnDelete();
            // Per-intern supervisor override; falls back to the cohort's supervisor.
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            // active | completed | terminated | paused | withdrawn
            $table->string('status')->default('active');
            // onsite | remote | hybrid
            $table->string('work_mode')->default('onsite');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['cohort_id', 'user_id']);
            $table->index(['user_id', 'status']);
            $table->index(['supervisor_id', 'status']);
            $table->index(['program_id', 'status']);
        });

        // One attendance record per intern per day. Timestamps are set
        // server-side (never trusted from the client).
        Schema::create('internship_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained('internships')->cascadeOnDelete();
            $table->date('date');
            $table->timestamp('clock_in_at')->nullable();
            $table->timestamp('clock_out_at')->nullable();
            $table->decimal('hours', 5, 2)->nullable();
            // present | late | absent | excused
            $table->string('status')->default('present');
            // self | supervisor | system
            $table->string('source')->default('self');
            $table->string('note')->nullable();
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['internship_id', 'date']);
        });

        // One logbook entry per intern per day.
        Schema::create('logbook_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained('internships')->cascadeOnDelete();
            $table->date('date');
            $table->longText('content');
            $table->decimal('hours_spent', 4, 1)->nullable();
            $table->text('learnings')->nullable();
            $table->text('blockers')->nullable();
            // draft | submitted | approved | needs_revision
            $table->string('status')->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->text('supervisor_feedback')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['internship_id', 'date']);
            $table->index(['internship_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logbook_entries');
        Schema::dropIfExists('internship_attendance');
        Schema::dropIfExists('internships');
        Schema::dropIfExists('cohorts');
    }
};

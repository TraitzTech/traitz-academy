<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lms_schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('audience', ['all_students', 'course_students', 'selected_students'])->default('course_students');
            $table->string('location')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->timestamps();

            $table->index(['starts_at', 'ends_at']);
            $table->index(['created_by', 'created_at']);
        });

        Schema::create('lms_schedule_student', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lms_schedule_id')->constrained('lms_schedules')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['lms_schedule_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lms_schedule_student');
        Schema::dropIfExists('lms_schedules');
    }
};

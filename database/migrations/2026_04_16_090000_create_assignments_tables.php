<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('instructions');
            $table->enum('audience', ['all_course_students', 'selected_students'])->default('all_course_students');
            $table->string('attachment_path')->nullable();
            $table->dateTime('due_at')->nullable();
            $table->timestamps();

            $table->index(['course_id', 'due_at']);
            $table->index(['created_by', 'created_at']);
        });

        Schema::create('assignment_student', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['assignment_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_student');
        Schema::dropIfExists('assignments');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_classes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('tutor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->dateTime('start_time');
            $table->unsignedInteger('duration');
            $table->string('room_name')->unique();
            $table->enum('access_type', ['course', 'custom'])->default('course');
            $table->timestamps();
        });

        Schema::create('live_class_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_class_id')->constrained('live_classes')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->unique(['live_class_id', 'course_id']);
        });

        Schema::create('live_class_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_class_id')->constrained('live_classes')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->unique(['live_class_id', 'student_id']);
        });

        Schema::create('live_class_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_class_id')->constrained('live_classes')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('joined_at');
            $table->dateTime('left_at')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->timestamps();
            $table->index(['live_class_id', 'student_id']);
        });

        Schema::create('live_class_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_class_id')->constrained('live_classes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('message');
            $table->timestamps();
            $table->index(['live_class_id', 'created_at']);
        });

        Schema::create('live_class_recordings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_class_id')->constrained('live_classes')->cascadeOnDelete();
            $table->string('file_path')->nullable();
            $table->string('youtube_url')->nullable();
            $table->enum('status', ['processing', 'uploaded', 'failed'])->default('processing');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_class_recordings');
        Schema::dropIfExists('live_class_messages');
        Schema::dropIfExists('live_class_attendance');
        Schema::dropIfExists('live_class_students');
        Schema::dropIfExists('live_class_courses');
        Schema::dropIfExists('live_classes');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained('course_lessons')->nullOnDelete(); // null = course-level final assessment
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->decimal('pass_mark_percentage', 5, 2)->default(60.00);
            $table->unsignedTinyInteger('max_attempts')->nullable(); // null = unlimited
            $table->boolean('is_required')->default(false);
            $table->boolean('reveal_answers')->default(true); // show correct answers after submission
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};

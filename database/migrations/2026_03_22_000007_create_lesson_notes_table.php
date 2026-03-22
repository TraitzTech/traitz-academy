<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_lesson_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->string('timestamp')->nullable(); // video timestamp e.g. "4:32"
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_notes');
    }
};

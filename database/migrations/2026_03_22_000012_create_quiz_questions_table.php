<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->text('question');
            $table->enum('type', ['multiple_choice', 'multiple_select', 'true_false', 'short_answer'])->default('multiple_choice');
            $table->json('options')->nullable();      // array of answer choices
            $table->json('correct_answer');           // single index, array of indices, "true"/"false", or text
            $table->text('explanation')->nullable();  // shown after answering
            $table->unsignedTinyInteger('points')->default(1);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_questions');
    }
};

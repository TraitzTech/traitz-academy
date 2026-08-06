<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discussions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained('course_lessons')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('discussions')->cascadeOnDelete(); // null = top-level post, set = reply
            $table->text('body');
            $table->boolean('is_accepted_answer')->default(false);
            $table->unsignedInteger('upvotes_count')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discussions');
    }
};

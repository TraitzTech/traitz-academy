<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->text('overview')->nullable();
            $table->string('category'); // e.g. Programming, Design, Data Science, Business, Marketing
            $table->enum('level', ['beginner', 'intermediate', 'advanced'])->default('beginner');
            $table->decimal('price', 10, 2)->default(0.00);
            $table->boolean('is_free')->default(true);
            $table->string('duration')->nullable(); // e.g. "8 weeks", "10 hours"
            $table->integer('total_lessons')->default(0);
            $table->string('thumbnail_url')->nullable();
            $table->string('intro_video_url')->nullable();
            $table->json('outcomes')->nullable();     // array of learning outcomes
            $table->json('requirements')->nullable(); // array of prerequisites
            $table->json('curriculum')->nullable();   // sections → lessons structure
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->integer('enrolled_count')->default(0);
            $table->decimal('rating', 3, 2)->default(0.00);
            $table->integer('review_count')->default(0);
            $table->foreignId('tutor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};

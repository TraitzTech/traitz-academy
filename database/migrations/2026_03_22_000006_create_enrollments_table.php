<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('instalment_plan_id')->nullable(); // FK added after course_instalment_plans exists
            $table->enum('payment_type', ['full', 'instalment', 'free', 'admin_granted'])->default('full');
            $table->enum('access_status', ['active', 'suspended', 'revoked', 'completed'])->default('active');
            $table->unsignedTinyInteger('progress')->default(0); // 0–100 percentage, derived from lesson_completions
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};

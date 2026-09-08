<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tac_leader_responsibilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tac_leader_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();

            // pending | in_progress | completed
            $table->string('status')->default('pending');
            $table->date('due_date')->nullable();

            // Academy staff who assigned it — kept even if that account is
            // later removed, so the history stays legible.
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();

            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['tac_leader_id', 'status']);
        });

        Schema::create('tac_leader_performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tac_leader_id')->constrained()->cascadeOnDelete();

            // 1-5 star rating plus free-text context — simple enough for a
            // quick staff review, expressive enough to mean something.
            $table->unsignedTinyInteger('rating');
            $table->string('period_label')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('tac_leader_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tac_leader_performance_reviews');
        Schema::dropIfExists('tac_leader_responsibilities');
    }
};

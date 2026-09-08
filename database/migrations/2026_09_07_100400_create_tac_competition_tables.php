<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Judging rubric for an activity of type "competition".
        Schema::create('tac_competition_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tac_activity_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('description')->nullable();
            $table->unsignedInteger('max_score')->default(10);
            $table->unsignedInteger('weight')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('tac_competition_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tac_activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('community_member_id')->constrained()->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('repo_url')->nullable();
            $table->string('demo_url')->nullable();
            $table->string('video_url')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('team_name')->nullable();
            $table->json('team_members')->nullable();

            // draft | submitted | under_review | scored | disqualified
            $table->string('status')->default('submitted');
            $table->timestamp('submitted_at')->nullable();

            // Denormalised from tac_competition_scores when judging is finalised.
            $table->decimal('total_score', 8, 2)->nullable();
            $table->unsignedInteger('rank')->nullable();
            $table->boolean('is_winner')->default(false);
            $table->string('award')->nullable();
            $table->text('judge_notes')->nullable();
            $table->timestamp('results_published_at')->nullable();
            $table->timestamps();

            $table->unique(['tac_activity_id', 'community_member_id'], 'tac_competition_entry_unique');
            $table->index(['tac_activity_id', 'status']);
        });

        Schema::create('tac_competition_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tac_competition_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tac_competition_criterion_id')->constrained()->cascadeOnDelete();
            $table->foreignId('judge_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('score', 8, 2);
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(
                ['tac_competition_entry_id', 'tac_competition_criterion_id', 'judge_id'],
                'tac_competition_score_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tac_competition_scores');
        Schema::dropIfExists('tac_competition_entries');
        Schema::dropIfExists('tac_competition_criteria');
    }
};

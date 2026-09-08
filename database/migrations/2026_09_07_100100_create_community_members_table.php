<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_members', function (Blueprint $table) {
            $table->id();

            // Optional link to a real account. Community membership is keyed by
            // email and stands on its own, so guests who register for an event
            // become members without ever having a login.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('school')->nullable();

            // student | past_intern | tech_enthusiast | professional | other
            $table->string('current_status')->default('student');
            $table->string('heard_about')->nullable();
            $table->text('bio')->nullable();
            $table->string('avatar_path')->nullable();
            $table->json('social_links')->nullable();

            // How they arrived: join_form | program_application | event |
            // ai_forge | course | internship | admin | import
            $table->string('source')->default('join_form');
            $table->nullableMorphs('sourceable');

            // Internal standing: member | contributor | mentor | lead | alumni
            $table->string('membership_status')->default('member');
            // active | dormant | unsubscribed | blocked
            $table->string('lifecycle_status')->default('active');
            $table->unsignedInteger('engagement_score')->default(0);

            $table->boolean('directory_opt_in')->default(false);
            $table->boolean('email_opt_in')->default(true);

            $table->timestamp('joined_at')->nullable();
            $table->timestamp('welcomed_at')->nullable();
            $table->timestamp('last_engaged_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->index(['membership_status', 'lifecycle_status']);
            $table->index('source');
            $table->index('school');
        });

        Schema::create('community_member_track', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tac_track_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['community_member_id', 'tac_track_id'], 'community_member_track_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_member_track');
        Schema::dropIfExists('community_members');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tac_leaders', function (Blueprint $table) {
            $table->id();

            // A leader is a structured entity, not hardcoded text. It may be
            // backed by an account (for scoped admin access) and/or a community
            // member record (today's member is tomorrow's lead).
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('community_member_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name');
            $table->string('photo_path')->nullable();

            // lead | co_lead | secretary | technical_lead | track_mentor |
            // school_lead | partnership_lead
            $table->string('role_type');
            $table->string('role_title')->nullable();

            $table->foreignId('tac_track_id')->nullable()->constrained()->nullOnDelete();
            $table->string('school')->nullable();

            $table->text('bio')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->json('social_links')->nullable();

            // Leadership rotates: a retired leader keeps their record with an
            // ended_on date so the public timeline can show alumni leaders.
            $table->date('started_on')->nullable();
            $table->date('ended_on')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['role_type', 'is_active']);
            $table->index(['tac_track_id', 'is_active']);
            $table->index(['school', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tac_leaders');
    }
};

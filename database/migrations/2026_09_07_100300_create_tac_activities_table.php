<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tac_activities', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();

            // event | workshop | training | bootcamp | internship | handout | competition
            $table->string('type')->default('event');
            $table->foreignId('tac_track_id')->nullable()->constrained()->nullOnDelete();

            // Internships link back to the main Traitz Academy program.
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();

            $table->string('summary')->nullable();
            $table->longText('description')->nullable();
            $table->string('cover_image')->nullable();

            // physical | virtual | hybrid
            $table->string('location_type')->default('physical');
            $table->string('location')->nullable();
            $table->string('meeting_url')->nullable();

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('timezone')->default('Africa/Douala');

            // Recurring series (e.g. a monthly workshop). `recurrence` holds the
            // rule; occurrences are generated from the parent.
            $table->boolean('is_recurring')->default(false);
            $table->json('recurrence')->nullable();
            $table->foreignId('parent_activity_id')->nullable()->constrained('tac_activities')->cascadeOnDelete();

            $table->unsignedInteger('capacity')->nullable();
            $table->boolean('registration_required')->default(true);
            $table->timestamp('registration_opens_at')->nullable();
            $table->timestamp('registration_closes_at')->nullable();

            $table->boolean('is_paid')->default(false);
            $table->unsignedInteger('price')->default(0);
            $table->string('currency', 8)->default('XAF');

            $table->foreignId('organizer_leader_id')->nullable()->constrained('tac_leaders')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // draft | published | cancelled | completed
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_featured')->default(false);

            // Archive of what happened: outcomes, winners, testimonials.
            $table->text('outcome_summary')->nullable();
            $table->json('highlights')->nullable();

            $table->unsignedInteger('rsvp_count')->default(0);
            $table->timestamps();

            $table->index(['status', 'starts_at']);
            $table->index(['type', 'status']);
            $table->index(['tac_track_id', 'status']);
        });

        Schema::create('tac_activity_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tac_activity_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('caption')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('tac_activity_rsvps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tac_activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('community_member_id')->constrained()->cascadeOnDelete();

            // registered | confirmed | waitlisted | attended | cancelled | no_show
            $table->string('status')->default('registered');

            // free | pending | paid | failed | refunded
            $table->string('payment_status')->default('free');
            $table->unsignedInteger('amount')->default(0);
            $table->string('currency', 8)->default('XAF');
            $table->string('payment_reference')->nullable();
            $table->string('payment_phone')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->text('note')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('reminded_at')->nullable();
            $table->timestamps();

            $table->unique(['tac_activity_id', 'community_member_id'], 'tac_activity_rsvp_unique');
            $table->index(['status', 'payment_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tac_activity_rsvps');
        Schema::dropIfExists('tac_activity_media');
        Schema::dropIfExists('tac_activities');
    }
};

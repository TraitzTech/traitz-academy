<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ai_forge_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_forge_event_id')->constrained('ai_forge_events')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('country')->nullable();
            $table->string('organization')->nullable();
            $table->text('motivation')->nullable();
            $table->string('status')->default('registered');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->unique(['ai_forge_event_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_forge_registrations');
    }
};

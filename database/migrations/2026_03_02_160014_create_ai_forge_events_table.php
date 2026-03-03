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
        Schema::create('ai_forge_events', function (Blueprint $table) {
            $table->id();
            $table->string('title')->default('AI Forge');
            $table->string('slug')->unique()->default('ai-forge');
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('location')->nullable();
            $table->boolean('is_online')->default(false);
            $table->string('event_url')->nullable();
            $table->integer('capacity')->nullable();
            $table->string('hero_image')->nullable();
            $table->string('logo_image')->nullable();
            $table->json('benefits')->nullable();
            $table->json('schedule')->nullable();
            $table->json('sponsors')->nullable();
            $table->json('faqs')->nullable();
            $table->json('stats')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('registration_open')->default(true);
            $table->boolean('swag_store_active')->default(true);
            $table->text('registration_note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_forge_events');
    }
};

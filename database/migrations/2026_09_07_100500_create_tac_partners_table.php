<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tac_partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo_path')->nullable();
            $table->string('website_url')->nullable();

            // platinum | gold | silver | community | academic
            $table->string('tier')->default('community');
            $table->text('description')->nullable();

            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();

            // The partnership lead who owns this relationship.
            $table->foreignId('partnership_lead_id')->nullable()->constrained('tac_leaders')->nullOnDelete();

            $table->date('started_on')->nullable();
            $table->date('ended_on')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'tier']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tac_partners');
    }
};

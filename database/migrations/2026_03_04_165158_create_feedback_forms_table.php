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
        Schema::create('feedback_forms', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('slug')->unique();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->boolean('allow_anonymous')->default(true);
            $table->boolean('send_thank_you_email')->default(true);
            $table->timestamp('closes_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback_forms');
    }
};

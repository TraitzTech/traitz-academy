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
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('programs')->onDelete('cascade');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone');
            $table->string('country');
            $table->text('bio')->nullable();
            $table->string('education_level')->nullable();
            $table->string('institution_name')->nullable(); // For academic internships
            $table->string('academic_duration')->nullable(); // For academic internships
            $table->text('motivation')->nullable();
            $table->text('experience')->nullable();
            $table->string('status')->default('pending'); // 'pending', 'accepted', 'rejected', 'withdrawn'
            $table->text('notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};

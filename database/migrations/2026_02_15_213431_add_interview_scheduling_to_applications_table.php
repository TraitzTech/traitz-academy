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
        Schema::table('applications', function (Blueprint $table) {
            $table->foreignId('interview_id')->nullable()->constrained('interviews')->nullOnDelete();
            $table->timestamp('interview_scheduled_at')->nullable();
            $table->string('interview_status')->nullable(); // scheduled, completed, expired
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropForeign(['interview_id']);
            $table->dropColumn(['interview_id', 'interview_scheduled_at', 'interview_status']);
        });
    }
};

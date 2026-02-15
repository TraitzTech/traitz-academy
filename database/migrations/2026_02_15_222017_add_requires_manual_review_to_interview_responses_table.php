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
        Schema::table('interview_responses', function (Blueprint $table) {
            $table->boolean('requires_manual_review')->default(false)->after('passed');
            $table->timestamp('reviewed_at')->nullable()->after('requires_manual_review');
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('reviewed_at');

            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interview_responses', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn(['requires_manual_review', 'reviewed_at', 'reviewed_by']);
        });
    }
};

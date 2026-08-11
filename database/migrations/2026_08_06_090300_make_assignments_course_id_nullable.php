<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * course_id was NOT NULL (course-only assignments). Now that assignments
     * can attach to a Cohort or Program instead, a row may have no course_id
     * at all — only attachable_type/attachable_id.
     */
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table): void {
            $table->foreignId('course_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table): void {
            $table->foreignId('course_id')->nullable(false)->change();
        });
    }
};

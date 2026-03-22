<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Removes the unique constraint on programs.slug to allow
     * multiple programs with the same title (e.g., academic-internship
     * and professional-internship sharing a slug base).
     * Uniqueness is enforced via slug + category combination instead.
     */
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->unique(['slug', 'category'], 'programs_slug_category_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropUnique('programs_slug_category_unique');
            $table->unique('slug');
        });
    }
};

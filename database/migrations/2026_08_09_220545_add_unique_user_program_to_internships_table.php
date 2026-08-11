<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A user may hold at most one engagement per program, full stop — whether
     * standalone or placed in a cohort. The existing unique(cohort_id, user_id)
     * doesn't catch this: MySQL treats every NULL cohort_id as distinct, so it
     * gave no protection against two standalone (cohort_id NULL) rows for the
     * same person+program, which is exactly how 9 users ended up with silently
     * duplicated internship records. Re-joining a program after withdrawing is
     * meant to reactivate the existing row, not create a second one.
     */
    public function up(): void
    {
        Schema::table('internships', function (Blueprint $table) {
            $table->unique(['user_id', 'program_id']);
        });
    }

    public function down(): void
    {
        Schema::table('internships', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'program_id']);
        });
    }
};

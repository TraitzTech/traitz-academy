<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logbook_entries', function (Blueprint $table) {
            // How the intern solved (or plans to solve) the difficulty recorded
            // in `blockers`. Kept separate so the problem and its resolution
            // read as a pair.
            $table->text('solution')->nullable()->after('blockers');
        });
    }

    public function down(): void
    {
        Schema::table('logbook_entries', function (Blueprint $table) {
            $table->dropColumn('solution');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            // ISO weekdays (1=Mon..7=Sun) interns in this program are expected at
            // the office. Null/empty = no fixed schedule (falls back to the
            // intern's own daily office/remote choice on the dashboard).
            $table->json('office_days')->nullable()->after('curriculum');
        });
    }

    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn('office_days');
        });
    }
};

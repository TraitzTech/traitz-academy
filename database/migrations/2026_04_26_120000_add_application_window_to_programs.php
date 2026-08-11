<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            // Application window. Both nullable: blank = rolling (apply anytime).
            $table->timestamp('applications_open_at')->nullable()->after('end_date');
            $table->timestamp('applications_close_at')->nullable()->after('applications_open_at');
        });
    }

    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn(['applications_open_at', 'applications_close_at']);
        });
    }
};

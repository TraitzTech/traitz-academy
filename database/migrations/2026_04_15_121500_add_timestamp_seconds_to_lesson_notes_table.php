<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_notes', function (Blueprint $table): void {
            $table->unsignedInteger('timestamp_seconds')->nullable()->after('timestamp');
        });
    }

    public function down(): void
    {
        Schema::table('lesson_notes', function (Blueprint $table): void {
            $table->dropColumn('timestamp_seconds');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internship_attendance', function (Blueprint $table) {
            // Captured GPS coordinates + server-computed distance to the office
            // at clock-in, kept for audit. 7 decimals ≈ centimetre precision.
            $table->decimal('clock_in_latitude', 10, 7)->nullable()->after('clock_in_at');
            $table->decimal('clock_in_longitude', 10, 7)->nullable()->after('clock_in_latitude');
            $table->unsignedInteger('clock_in_distance_m')->nullable()->after('clock_in_longitude');
        });
    }

    public function down(): void
    {
        Schema::table('internship_attendance', function (Blueprint $table) {
            $table->dropColumn(['clock_in_latitude', 'clock_in_longitude', 'clock_in_distance_m']);
        });
    }
};

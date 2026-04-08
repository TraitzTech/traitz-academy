<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_lessons', function (Blueprint $table) {
            $table->string('youtube_video_id')->nullable()->after('video_url');
            $table->enum('youtube_status', ['pending', 'processing', 'ready', 'failed'])->nullable()->after('youtube_video_id');
            $table->text('youtube_error')->nullable()->after('youtube_status');
        });
    }

    public function down(): void
    {
        Schema::table('course_lessons', function (Blueprint $table) {
            $table->dropColumn(['youtube_video_id', 'youtube_status', 'youtube_error']);
        });
    }
};

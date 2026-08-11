<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('live_classes', function (Blueprint $table) {
            // External meeting link (e.g. Google Meet) + the provider event id so
            // we can update/cancel it later. Nullable: room_name/Jitsi is the
            // parked alternative driver.
            $table->string('meeting_url')->nullable()->after('room_name');
            $table->string('meeting_provider')->nullable()->after('meeting_url');
            $table->string('meeting_event_id')->nullable()->after('meeting_provider');
        });
    }

    public function down(): void
    {
        Schema::table('live_classes', function (Blueprint $table) {
            $table->dropColumn(['meeting_url', 'meeting_provider', 'meeting_event_id']);
        });
    }
};

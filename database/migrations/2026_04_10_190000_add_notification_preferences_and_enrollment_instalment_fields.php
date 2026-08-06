<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('notification_preferences')->nullable()->after('remember_token');
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->unsignedTinyInteger('consecutive_failed_payments')->default(0)->after('completed_at');
            $table->timestamp('instalment_next_due_at')->nullable()->after('consecutive_failed_payments');
            $table->timestamp('last_instalment_reminder_sent_at')->nullable()->after('instalment_next_due_at');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn([
                'consecutive_failed_payments',
                'instalment_next_due_at',
                'last_instalment_reminder_sent_at',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('notification_preferences');
        });
    }
};

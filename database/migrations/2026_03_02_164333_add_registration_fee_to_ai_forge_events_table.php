<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ai_forge_events', function (Blueprint $table) {
            $table->unsignedInteger('registration_fee')->default(0)->after('registration_open');
            $table->unsignedInteger('early_bird_fee')->nullable()->after('registration_fee');
            $table->date('early_bird_deadline')->nullable()->after('early_bird_fee');
            $table->string('currency', 10)->default('XAF')->after('early_bird_deadline');
        });

        Schema::table('ai_forge_registrations', function (Blueprint $table) {
            $table->unsignedInteger('amount_paid')->default(0)->after('status');
            $table->string('payment_status')->default('pending')->after('amount_paid');
            $table->string('payment_reference')->nullable()->after('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_forge_events', function (Blueprint $table) {
            $table->dropColumn(['registration_fee', 'early_bird_fee', 'early_bird_deadline', 'currency']);
        });

        Schema::table('ai_forge_registrations', function (Blueprint $table) {
            $table->dropColumn(['amount_paid', 'payment_status', 'payment_reference']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('site_settings')->updateOrInsert(
            ['key' => 'online_payment_surcharge_percentage'],
            [
                'value' => '2',
                'type' => 'text',
                'group' => 'payments',
                'label' => 'Online Payment Surcharge (%)',
                'description' => 'Percentage surcharge added to online checkout payments (e.g. 2 for 2%).',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('site_settings')
            ->where('key', 'online_payment_surcharge_percentage')
            ->delete();
    }
};

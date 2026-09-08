<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            [
                'key' => 'tac_join_whatsapp_enabled',
                'label' => 'Include WhatsApp community link in TAC welcome emails',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'When on, every TAC join/welcome email invites the new member into the official community WhatsApp group.',
            ],
            [
                'key' => 'tac_join_whatsapp_link',
                'label' => 'TAC community WhatsApp group link',
                'value' => 'https://chat.whatsapp.com/DhRne7d98y8K2aM0wu8Rlr?s=cl&p=a&mlu=4&ilr=4',
                'type' => 'url',
                'description' => 'The invite link shared in TAC join/welcome emails when the toggle above is on.',
            ],
        ];

        foreach ($defaults as $setting) {
            SiteSetting::query()->firstOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'group' => 'social',
                    'label' => $setting['label'],
                    'description' => $setting['description'],
                ]
            );
        }
    }

    public function down(): void
    {
        SiteSetting::query()
            ->whereIn('key', ['tac_join_whatsapp_enabled', 'tac_join_whatsapp_link'])
            ->delete();
    }
};

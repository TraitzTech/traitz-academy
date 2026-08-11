<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            [
                'key' => 'office_latitude',
                'label' => 'Office latitude',
                'value' => null,
                'description' => 'Set automatically via "Use my current location".',
            ],
            [
                'key' => 'office_longitude',
                'label' => 'Office longitude',
                'value' => null,
                'description' => 'Set automatically via "Use my current location".',
            ],
            [
                'key' => 'office_radius_meters',
                'label' => 'Allowed radius (metres)',
                'value' => '150',
                'description' => 'How close to the office an intern must be to clock in. A ~100m safety buffer is added on top.',
            ],
        ];

        foreach ($defaults as $setting) {
            SiteSetting::query()->firstOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'type' => 'text',
                    'group' => 'internship',
                    'label' => $setting['label'],
                    'description' => $setting['description'],
                ]
            );
        }
    }

    public function down(): void
    {
        SiteSetting::query()
            ->whereIn('key', ['office_latitude', 'office_longitude', 'office_radius_meters'])
            ->delete();
    }
};

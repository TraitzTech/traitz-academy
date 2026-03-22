<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    /** @use HasFactory<\Database\Factories\SiteSettingFactory> */
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
    ];

    /**
     * Get a setting value by key with optional default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("site_setting_{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting?->value ?? $default;
        });
    }

    /**
     * Set a setting value by key.
     */
    public static function set(string $key, mixed $value, array $attributes = []): static
    {
        Cache::forget("site_setting_{$key}");

        return static::updateOrCreate(
            ['key' => $key],
            array_merge(['value' => $value], $attributes)
        );
    }

    /**
     * Get all settings by group.
     */
    public static function getByGroup(string $group): array
    {
        return Cache::remember("site_settings_group_{$group}", 3600, function () use ($group) {
            return static::where('group', $group)
                ->get()
                ->pluck('value', 'key')
                ->toArray();
        });
    }

    /**
     * Clear all settings cache.
     */
    public static function clearCache(): void
    {
        $settings = static::all();
        foreach ($settings as $setting) {
            Cache::forget("site_setting_{$setting->key}");
        }
        Cache::forget('site_settings_group_general');
        Cache::forget('site_settings_group_branding');
        Cache::forget('site_settings_group_content');
    }
}

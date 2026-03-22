<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(): Response
    {
        // Get all settings grouped as arrays of setting objects
        $settings = [
            'branding' => SiteSetting::where('group', 'branding')->get()->values(),
            'content' => SiteSetting::where('group', 'content')->get()->values(),
            'contact' => SiteSetting::where('group', 'contact')->get()->values(),
            'social' => SiteSetting::where('group', 'social')->get()->values(),
            'payments' => SiteSetting::where('group', 'payments')->get()->values(),
        ];

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'settings' => 'required|array',
        ]);

        foreach ($validated['settings'] as $key => $value) {
            if ($key === 'online_payment_surcharge_percentage') {
                $normalizedValue = ($value === null || $value === '') ? 2 : (float) $value;

                Validator::make(
                    ['value' => $normalizedValue],
                    ['value' => ['numeric', 'min:0', 'max:100']]
                )->validate();

                $value = (string) round((float) $normalizedValue, 2);
            }

            // Skip image fields - they're handled separately
            $setting = SiteSetting::where('key', $key)->first();
            if ($setting && $setting->type !== 'image') {
                $setting->update(['value' => $value]);
            }
        }

        SiteSetting::clearCache();

        return back()->with('success', 'Settings updated successfully.');
    }

    public function uploadImage(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'key' => 'required|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp,ico|max:2048',
        ]);

        $path = $request->file('image')->store('settings', 'public');

        // Delete old image if exists
        $oldSetting = SiteSetting::where('key', $validated['key'])->first();
        if ($oldSetting && $oldSetting->value && Storage::disk('public')->exists($oldSetting->value)) {
            Storage::disk('public')->delete($oldSetting->value);
        }

        if ($oldSetting) {
            $oldSetting->update(['value' => $path]);
        } else {
            SiteSetting::set(
                $validated['key'],
                $path,
                [
                    'type' => 'image',
                    'group' => $this->getGroupForKey($validated['key']),
                    'label' => ucwords(str_replace('_', ' ', $validated['key'])),
                ]
            );
        }

        SiteSetting::clearCache();

        return back()->with('success', 'Image uploaded successfully.');
    }

    public function deleteImage(string $key): RedirectResponse
    {
        $setting = SiteSetting::where('key', $key)->first();

        if ($setting && $setting->value && Storage::disk('public')->exists($setting->value)) {
            Storage::disk('public')->delete($setting->value);
        }

        if ($setting) {
            $setting->update(['value' => null]);
        }

        SiteSetting::clearCache();

        return back()->with('success', 'Image deleted successfully.');
    }

    private function getGroupForKey(string $key): string
    {
        $groups = [
            'branding' => ['site_logo', 'site_logo_dark', 'favicon', 'site_name'],
            'content' => ['youtube_video_url', 'hero_title', 'hero_subtitle'],
            'contact' => ['contact_email', 'contact_phone', 'contact_whatsapp', 'contact_address'],
            'social' => ['social_facebook', 'social_twitter', 'social_linkedin', 'social_instagram', 'social_youtube', 'social_whatsapp_community'],
            'payments' => ['online_payment_surcharge_percentage'],
        ];

        foreach ($groups as $group => $keys) {
            if (in_array($key, $keys)) {
                return $group;
            }
        }

        return 'general';
    }
}

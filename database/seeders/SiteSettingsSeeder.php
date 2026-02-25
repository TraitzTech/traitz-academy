<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // Branding
            [
                'key' => 'site_logo',
                'value' => null,
                'type' => 'image',
                'group' => 'branding',
                'label' => 'Site Logo',
                'description' => 'The main logo displayed in the header',
            ],
            [
                'key' => 'site_logo_dark',
                'value' => null,
                'type' => 'image',
                'group' => 'branding',
                'label' => 'Site Logo (Dark Mode)',
                'description' => 'Logo for dark backgrounds',
            ],
            [
                'key' => 'favicon',
                'value' => null,
                'type' => 'image',
                'group' => 'branding',
                'label' => 'Favicon',
                'description' => 'The small icon displayed in browser tabs',
            ],
            [
                'key' => 'site_name',
                'value' => 'Traitz Academy',
                'type' => 'text',
                'group' => 'branding',
                'label' => 'Site Name',
                'description' => 'The name of your website',
            ],

            // Content
            [
                'key' => 'youtube_video_url',
                'value' => null,
                'type' => 'url',
                'group' => 'content',
                'label' => 'YouTube Video URL',
                'description' => 'The YouTube video displayed on the landing page',
            ],
            [
                'key' => 'hero_title',
                'value' => 'Unlock Your Professional Potential',
                'type' => 'text',
                'group' => 'content',
                'label' => 'Hero Title',
                'description' => 'The main heading on the landing page',
            ],
            [
                'key' => 'hero_subtitle',
                'value' => 'Join our professional development programs designed to transform your career trajectory',
                'type' => 'textarea',
                'group' => 'content',
                'label' => 'Hero Subtitle',
                'description' => 'The subheading text on the landing page',
            ],

            // Contact
            [
                'key' => 'contact_email',
                'value' => 'info@traitzacademy.com',
                'type' => 'email',
                'group' => 'contact',
                'label' => 'Contact Email',
                'description' => 'Primary contact email address',
            ],
            [
                'key' => 'contact_phone',
                'value' => null,
                'type' => 'text',
                'group' => 'contact',
                'label' => 'Contact Phone',
                'description' => 'Primary contact phone number',
            ],
            [
                'key' => 'contact_address',
                'value' => null,
                'type' => 'textarea',
                'group' => 'contact',
                'label' => 'Contact Address',
                'description' => 'Physical address',
            ],

            // Social Media
            [
                'key' => 'social_facebook',
                'value' => null,
                'type' => 'url',
                'group' => 'social',
                'label' => 'Facebook URL',
                'description' => 'Your Facebook page URL',
            ],
            [
                'key' => 'social_twitter',
                'value' => null,
                'type' => 'url',
                'group' => 'social',
                'label' => 'Twitter/X URL',
                'description' => 'Your Twitter/X profile URL',
            ],
            [
                'key' => 'social_instagram',
                'value' => null,
                'type' => 'url',
                'group' => 'social',
                'label' => 'Instagram URL',
                'description' => 'Your Instagram profile URL',
            ],
            [
                'key' => 'social_linkedin',
                'value' => null,
                'type' => 'url',
                'group' => 'social',
                'label' => 'LinkedIn URL',
                'description' => 'Your LinkedIn page URL',
            ],
            [
                'key' => 'social_youtube',
                'value' => null,
                'type' => 'url',
                'group' => 'social',
                'label' => 'YouTube Channel URL',
                'description' => 'Your YouTube channel URL',
            ],
            [
                'key' => 'social_whatsapp_community',
                'value' => null,
                'type' => 'url',
                'group' => 'social',
                'label' => 'WhatsApp Community Link',
                'description' => 'Invite link for your WhatsApp community group',
            ],

            // Payments
            [
                'key' => 'online_payment_surcharge_percentage',
                'value' => '2',
                'type' => 'text',
                'group' => 'payments',
                'label' => 'Online Payment Surcharge (%)',
                'description' => 'Percentage surcharge added to online checkout payments (e.g. 2 for 2%).',
            ],
        ];

        foreach ($settings as $setting) {
            SiteSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // Branding
            [
                'key' => 'logo_url',
                'value' => null,
                'type' => 'image',
                'group' => 'branding',
                'label' => 'Logo URL',
                'description' => 'The main logo displayed in the header and footer',
            ],
            [
                'key' => 'logo_text',
                'value' => 'Traitz Academy',
                'type' => 'text',
                'group' => 'branding',
                'label' => 'Logo Text',
                'description' => 'Text to use if no logo image is set',
            ],
            [
                'key' => 'site_title',
                'value' => 'Traitz Academy',
                'type' => 'text',
                'group' => 'branding',
                'label' => 'Site Title',
                'description' => 'The main title of your website',
            ],
            [
                'key' => 'site_description',
                'value' => 'Preparing the next generation of tech talent through quality education and real-world experience.',
                'type' => 'textarea',
                'group' => 'branding',
                'label' => 'Site Description',
                'description' => 'A brief description of your website for meta tags and footer',
            ],
            [
                'key' => 'favicon_url',
                'value' => null,
                'type' => 'image',
                'group' => 'branding',
                'label' => 'Favicon URL',
                'description' => 'The favicon (icon) for the browser tab',
            ],

            // Content
            [
                'key' => 'hero_title',
                'value' => 'World-Class Tech Education',
                'type' => 'text',
                'group' => 'content',
                'label' => 'Hero Title',
                'description' => 'The main headline on the home page hero section',
            ],
            [
                'key' => 'hero_subtitle',
                'value' => 'Bridging the gap between academic learning and real-world industry needs.',
                'type' => 'textarea',
                'group' => 'content',
                'label' => 'Hero Subtitle',
                'description' => 'The subtitle on the home page hero section',
            ],
            [
                'key' => 'hero_image',
                'value' => null,
                'type' => 'image',
                'group' => 'content',
                'label' => 'Hero Image',
                'description' => 'Background image for the hero section',
            ],
            [
                'key' => 'about_title',
                'value' => null,
                'type' => 'text',
                'group' => 'content',
                'label' => 'About Title',
                'description' => 'The title for the about page',
            ],
            [
                'key' => 'about_description',
                'value' => null,
                'type' => 'textarea',
                'group' => 'content',
                'label' => 'About Description',
                'description' => 'The main description for the about page',
            ],
            [
                'key' => 'youtube_video_url',
                'value' => null,
                'type' => 'url',
                'group' => 'content',
                'label' => 'YouTube Video URL',
                'description' => 'URL to embed on the home page (e.g., https://www.youtube.com/embed/...)',
            ],

            // Contact
            [
                'key' => 'contact_email',
                'value' => 'hello@traitzacademy.com',
                'type' => 'email',
                'group' => 'contact',
                'label' => 'Contact Email',
                'description' => 'Email address for contact forms and footer',
            ],
            [
                'key' => 'contact_phone',
                'value' => null,
                'type' => 'text',
                'group' => 'contact',
                'label' => 'Contact Phone',
                'description' => 'Phone number for contact information',
            ],
            [
                'key' => 'contact_whatsapp',
                'value' => null,
                'type' => 'text',
                'group' => 'contact',
                'label' => 'WhatsApp Number',
                'description' => 'WhatsApp number with country code (e.g., +234xxxxxxxxxx)',
            ],
            [
                'key' => 'contact_address',
                'value' => null,
                'type' => 'textarea',
                'group' => 'contact',
                'label' => 'Physical Address',
                'description' => 'Physical address of your organization',
            ],

            // Social Media
            [
                'key' => 'social_facebook',
                'value' => null,
                'type' => 'url',
                'group' => 'social',
                'label' => 'Facebook URL',
                'description' => 'Link to your Facebook page',
            ],
            [
                'key' => 'social_twitter',
                'value' => null,
                'type' => 'url',
                'group' => 'social',
                'label' => 'Twitter URL',
                'description' => 'Link to your Twitter profile',
            ],
            [
                'key' => 'social_linkedin',
                'value' => null,
                'type' => 'url',
                'group' => 'social',
                'label' => 'LinkedIn URL',
                'description' => 'Link to your LinkedIn profile',
            ],
            [
                'key' => 'social_instagram',
                'value' => null,
                'type' => 'url',
                'group' => 'social',
                'label' => 'Instagram URL',
                'description' => 'Link to your Instagram profile',
            ],
            [
                'key' => 'social_youtube',
                'value' => null,
                'type' => 'url',
                'group' => 'social',
                'label' => 'YouTube URL',
                'description' => 'Link to your YouTube channel',
            ],
            [
                'key' => 'social_tiktok',
                'value' => null,
                'type' => 'url',
                'group' => 'social',
                'label' => 'TikTok URL',
                'description' => 'Link to your TikTok profile',
            ],

            // Footer
            [
                'key' => 'footer_copyright_text',
                'value' => '© 2024 Traitz Academy. All rights reserved.',
                'type' => 'text',
                'group' => 'content',
                'label' => 'Footer Copyright Text',
                'description' => 'Copyright text displayed in the footer',
            ],
            [
                'key' => 'footer_powered_by',
                'value' => 'Powered by Traitz Tech',
                'type' => 'text',
                'group' => 'content',
                'label' => 'Footer Powered By Text',
                'description' => 'Attribution text in the footer',
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

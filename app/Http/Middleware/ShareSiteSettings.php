<?php

namespace App\Http\Middleware;

use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShareSiteSettings
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Share all site settings globally with Inertia
        Inertia::share('siteSettings', fn () => $this->getSiteSettings());

        return $next($request);
    }

    /**
     * Get all site settings organized by group.
     */
    private function getSiteSettings(): array
    {
        return [
            // Branding
            'logo_url' => SiteSetting::get('logo_url'),
            'logo_text' => SiteSetting::get('logo_text', 'Traitz Academy'),
            'site_title' => SiteSetting::get('site_title', 'Traitz Academy'),
            'site_description' => SiteSetting::get('site_description', 'Preparing the next generation of tech talent through quality education and real-world experience.'),
            'favicon_url' => SiteSetting::get('favicon_url'),

            // Contact
            'contact_email' => SiteSetting::get('contact_email', 'hello@traitzacademy.com'),
            'contact_phone' => SiteSetting::get('contact_phone'),
            'contact_whatsapp' => SiteSetting::get('contact_whatsapp'),
            'contact_address' => SiteSetting::get('contact_address'),

            // Social Media
            'social_facebook' => SiteSetting::get('social_facebook'),
            'social_twitter' => SiteSetting::get('social_twitter'),
            'social_linkedin' => SiteSetting::get('social_linkedin'),
            'social_instagram' => SiteSetting::get('social_instagram'),
            'social_youtube' => SiteSetting::get('social_youtube'),
            'social_tiktok' => SiteSetting::get('social_tiktok'),

            // Content
            'hero_title' => SiteSetting::get('hero_title', 'World-Class Tech Education'),
            'hero_subtitle' => SiteSetting::get('hero_subtitle', 'Bridging the gap between academic learning and real-world industry needs.'),
            'hero_image' => SiteSetting::get('hero_image'),
            'about_title' => SiteSetting::get('about_title'),
            'about_description' => SiteSetting::get('about_description'),
            'youtube_video_url' => SiteSetting::get('youtube_video_url'),

            // Footer
            'footer_copyright_text' => SiteSetting::get('footer_copyright_text', '© 2024 Traitz Academy. All rights reserved.'),
            'footer_powered_by' => SiteSetting::get('footer_powered_by', 'Powered by Traitz Tech'),
        ];
    }
}

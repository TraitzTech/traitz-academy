<?php

namespace App\Helpers;

use App\Models\SiteSetting;

class SettingHelper
{
    /**
     * Get a site setting value by key with optional default.
     * This is a convenience wrapper around SiteSetting::get()
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return SiteSetting::get($key, $default);
    }

    /**
     * Get a contact setting value.
     */
    public static function contact(string $field): mixed
    {
        return self::get("contact_{$field}");
    }

    /**
     * Get a social media setting value.
     */
    public static function social(string $platform): mixed
    {
        return self::get("social_{$platform}");
    }

    /**
     * Get a branding setting value.
     */
    public static function branding(string $field): mixed
    {
        return self::get("{$field}");
    }

    /**
     * Get site email for contact forms.
     */
    public static function contactEmail(): string
    {
        return self::get('contact_email', 'hello@traitzacademy.com');
    }

    /**
     * Get site phone number.
     */
    public static function contactPhone(): ?string
    {
        return self::get('contact_phone');
    }

    /**
     * Get site WhatsApp number.
     */
    public static function contactWhatsApp(): ?string
    {
        return self::get('contact_whatsapp');
    }

    /**
     * Get site name/title.
     */
    public static function siteTitle(): string
    {
        return self::get('site_title', 'Traitz Academy');
    }

    /**
     * Get site description.
     */
    public static function siteDescription(): string
    {
        return self::get('site_description', 'Preparing the next generation of tech talent through quality education and real-world experience.');
    }

    /**
     * Get logo URL.
     */
    public static function logoUrl(): ?string
    {
        return self::get('logo_url');
    }

    /**
     * Get hero title.
     */
    public static function heroTitle(): string
    {
        return self::get('hero_title', 'World-Class Tech Education');
    }

    /**
     * Get hero subtitle.
     */
    public static function heroSubtitle(): string
    {
        return self::get('hero_subtitle', 'Bridging the gap between academic learning and real-world industry needs.');
    }

    /**
     * Get WhatsApp community link for joining.
     */
    public static function whatsAppCommunityLink(): ?string
    {
        return self::get('social_whatsapp_community');
    }
}

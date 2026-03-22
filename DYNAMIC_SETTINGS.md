# Dynamic Site Settings Implementation Guide

## Overview

The site now uses a fully dynamic backend system for managing website content including logos, social media links, contact information, and more. This means administrators can update these values without touching any code.

## Architecture

### 1. **SiteSetting Model** (`app/Models/SiteSetting.php`)
- Manages all site settings stored in the database
- Includes caching to improve performance
- Methods:
  - `SiteSetting::get(key, default)` - Get a setting value
  - `SiteSetting::set(key, value, attributes)` - Set/update a setting
  - `SiteSetting::getByGroup(group)` - Get all settings for a group
  - `SiteSetting::clearCache()` - Clear the settings cache

### 2. **ShareSiteSettings Middleware** (`app/Http/Middleware/ShareSiteSettings.php`)
- Automatically shares all site settings with every Inertia view
- Runs on every request to ensure settings are always available
- No manual passing needed in controllers
- Settings are accessible via `$page.props.siteSettings` in Vue components

### 3. **SettingHelper Class** (`app/Helpers/SettingHelper.php`)
- Convenience helper for accessing settings in PHP code
- Useful methods:
  - `SettingHelper::contactEmail()` - Get contact email
  - `SettingHelper::contactPhone()` - Get phone number
  - `SettingHelper::siteTitle()` - Get site title
  - `SettingHelper::social('facebook')` - Get social media links

### 4. **SiteSettingSeeder** (`database/seeders/SiteSettingSeeder.php`)
- Ensures all necessary settings exist in the database
- Run with: `php artisan db:seed --class=SiteSettingSeeder`

## Available Settings

### Branding Settings
- `site_title` - Main title of your website
- `site_description` - Brief description for meta tags and footer
- `logo_url` - URL/path to the main logo image
- `logo_text` - Text fallback if logo image isn't set
- `favicon_url` - Browser tab icon

### Contact Settings
- `contact_email` - Email address for contact forms
- `contact_phone` - Phone number
- `contact_whatsapp` - WhatsApp number with country code
- `contact_address` - Physical address

### Content Settings
- `hero_title` - Main headline on home page
- `hero_subtitle` - Subtitle on home page
- `hero_image` - Hero section background image
- `about_title` - Title for about page
- `about_description` - Description for about page
- `youtube_video_url` - Embed URL for YouTube videos
- `footer_copyright_text` - Copyright notice in footer
- `footer_powered_by` - Attribution text in footer

### Social Media Settings
- `social_facebook` - Facebook page URL
- `social_twitter` - Twitter profile URL
- `social_linkedin` - LinkedIn profile URL
- `social_instagram` - Instagram profile URL
- `social_youtube` - YouTube channel URL
- `social_tiktok` - TikTok profile URL

## How to Use

### In Vue Components (Frontend)

Settings are automatically available on every page via `$page.props.siteSettings`:

```vue
<template>
  <div>
    <!-- Using settings in templates -->
    <h1>{{ $page.props.siteSettings.site_title }}</h1>
    <img :src="$page.props.siteSettings.logo_url" alt="logo" />
    
    <!-- Contact info -->
    <a :href="`mailto:${$page.props.siteSettings.contact_email}`">
      {{ $page.props.siteSettings.contact_email }}
    </a>
    
    <!-- Social links -->
    <a 
      v-if="$page.props.siteSettings.social_facebook"
      :href="$page.props.siteSettings.social_facebook" 
      target="_blank"
    >
      Facebook
    </a>
  </div>
</template>
```

### In PHP Controllers/Classes

Use the `SettingHelper` class:

```php
use App\Helpers\SettingHelper;

// Get specific settings
$email = SettingHelper::contactEmail();
$title = SettingHelper::siteTitle();
$logo = SettingHelper::logoUrl();

// Or use the direct method
use App\Models\SiteSetting;

$email = SiteSetting::get('contact_email', 'default@example.com');
```

### In Blade Templates (if needed)

```blade
<h1>{{ SettingHelper::siteTitle() }}</h1>
<img src="{{ SettingHelper::logoUrl() }}" alt="logo">
```

## Admin Settings Page

The `/admin/settings` page allows administrators to manage all these settings without code changes.

Settings are organized into tabs:
- **Branding** - Logo, site name, description
- **Content** - Hero section, footer text, pages content
- **Contact** - Email, phone, address
- **Social Media** - All social media links

## Updated Components

The following components now use dynamic settings:

1. **PublicLayout** (`resources/js/layouts/PublicLayout.vue`)
   - Dynamic logo in header
   - Dynamic footer with contact info and social links
   - Dynamic site title and description

2. **AdminLayout** (`resources/js/layouts/AdminLayout.vue`)
   - Dynamic logo in sidebar

3. **Home Page** (`resources/js/pages/Home.vue`)
   - Uses settings for hero section content

## Database Schema

The `site_settings` table has the following structure:

```sql
CREATE TABLE site_settings (
    id INTEGER PRIMARY KEY,
    key VARCHAR(255) UNIQUE,
    value LONGTEXT,
    type VARCHAR(50), -- text, textarea, email, url, image
    group VARCHAR(50), -- branding, contact, content, social
    label VARCHAR(255),
    description TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

## Caching

Settings are cached for 1 hour (3600 seconds) for performance. When settings are updated via the admin panel, the cache is automatically cleared.

To manually clear the cache:

```php
SiteSetting::clearCache();
```

## Examples

### Add a new setting programmatically

```php
SiteSetting::set(
    'my_custom_setting',
    'Some value',
    [
        'type' => 'text',
        'group' => 'content',
        'label' => 'My Custom Setting',
        'description' => 'A description of this setting',
    ]
);
```

### Get and display a setting

In a Vue component:
```vue
<script setup>
import { usePage } from '@inertiajs/vue3'

const page = usePage()
const contactEmail = computed(() => page.props.siteSettings.contact_email)
</script>

<template>
  <p>Contact us: {{ contactEmail }}</p>
</template>
```

In PHP:
```php
$contactEmail = SettingHelper::contactEmail();
// Use it in your business logic
```

## Maintenance

### When to clear cache
- After bulk updates to settings
- If settings aren't reflecting changes immediately
- During debugging

```bash
php artisan cache:forget site_setting_*
```

### Seeding default values

If you need to reset settings to defaults, re-run the seeder:

```bash
php artisan db:seed --class=SiteSettingSeeder
```

This will not overwrite existing values, only add missing ones.

## Best Practices

1. **Always use the admin panel** for managing settings - don't modify the database directly
2. **Use meaningful setting names** that describe their purpose
3. **Provide good descriptions** in the label and description fields
4. **Cache bust** after making changes by running migrations or clearing cache
5. **Group related settings** together using the group field
6. **Use appropriate field types** (email, url, image) for validation and UI

## Future Enhancements

Potential improvements to the system:
- Setting translations for multi-language support
- Environment-specific overrides
- Settings versioning/history
- Bulk setting exports/imports
- Custom setting field types with validation

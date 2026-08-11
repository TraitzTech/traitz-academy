<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'mesomb' => [
        'application_key' => env('MESOMB_APPLICATION_KEY'),
        'access_key' => env('MESOMB_ACCESS_KEY'),
        'secret_key' => env('MESOMB_SECRET_KEY'),
        'country' => env('MESOMB_COUNTRY', 'CM'),
        'currency' => env('MESOMB_CURRENCY', 'XAF'),
        // Optional: set to the same product category string as working program payments (e.g. professional-internship).
        // When empty, LMS uses the course category slug or fallback professional-training.
        'course_product_category' => env('MESOMB_COURSE_PRODUCT_CATEGORY'),
    ],

    'live_class' => [
        // 'meet' = external Google Meet links (works on shared hosting, $0);
        // 'jitsi' = the parked embedded-room driver.
        'driver' => env('LIVE_CLASS_DRIVER', 'meet'),
    ],

    'google_meet' => [
        // One central "academy" Google account creates every class's Meet link
        // via the Calendar API. Reuses the YouTube OAuth client by default — the
        // same Google Cloud project just needs the Calendar API enabled and a
        // refresh token authorized with the calendar scope (see `meet:auth`).
        'client_id' => env('GOOGLE_MEET_CLIENT_ID', env('YOUTUBE_CLIENT_ID')),
        'client_secret' => env('GOOGLE_MEET_CLIENT_SECRET', env('YOUTUBE_CLIENT_SECRET')),
        'redirect_uri' => env('GOOGLE_MEET_REDIRECT_URI', 'http://localhost'),
        'refresh_token' => env('GOOGLE_MEET_REFRESH_TOKEN'),
        'calendar_id' => env('GOOGLE_MEET_CALENDAR_ID', 'primary'),
    ],

    'youtube' => [
        'enabled' => (bool) env('YOUTUBE_ENABLED', false),
        'channel_id' => env('YOUTUBE_CHANNEL_ID'),
        'client_id' => env('YOUTUBE_CLIENT_ID'),
        'client_secret' => env('YOUTUBE_CLIENT_SECRET'),
        'redirect_uri' => env('YOUTUBE_REDIRECT_URI', 'http://localhost'),
        'refresh_token' => env('YOUTUBE_REFRESH_TOKEN'),
        'privacy_status' => env('YOUTUBE_PRIVACY_STATUS', 'unlisted'),
        'category_id' => env('YOUTUBE_DEFAULT_CATEGORY', '27'),
        'notify_subscribers' => (bool) env('YOUTUBE_UPLOAD_NOTIFY_SUBSCRIBERS', false),
    ],

    'jitsi' => [
        'domain' => env('JITSI_DOMAIN', 'meet.jit.si'),
        'app_id' => env('JITSI_APP_ID'),
        'key_id' => env('JITSI_KEY_ID'),
        'private_key' => env('JITSI_PRIVATE_KEY'),
        'private_key_path' => env('JITSI_PRIVATE_KEY_PATH'),
        'app_secret' => env('JITSI_APP_SECRET'),
    ],

    'google_calendar' => [
        'client_id' => env('GOOGLE_CALENDAR_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CALENDAR_CLIENT_SECRET'),
        'redirect_uri' => env('GOOGLE_CALENDAR_REDIRECT_URI'),
    ],

];

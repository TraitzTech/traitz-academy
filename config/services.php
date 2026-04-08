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

    'youtube' => [
        'enabled' => (bool) env('YOUTUBE_ENABLED', false),
        'channel_id' => env('YOUTUBE_CHANNEL_ID'),
        'client_id' => env('YOUTUBE_CLIENT_ID'),
        'client_secret' => env('YOUTUBE_CLIENT_SECRET'),
        'refresh_token' => env('YOUTUBE_REFRESH_TOKEN'),
        'privacy_status' => env('YOUTUBE_PRIVACY_STATUS', 'unlisted'),
        'category_id' => env('YOUTUBE_DEFAULT_CATEGORY', '27'),
        'notify_subscribers' => (bool) env('YOUTUBE_UPLOAD_NOTIFY_SUBSCRIBERS', false),
    ],

];

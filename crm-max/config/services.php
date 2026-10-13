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
        'token' => env('POSTMARK_TOKEN'),
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

    // Candle history for the MT4 chart (App\Services\CandleService). Same practice-account
    // token the asset-data-service price feed uses.
    'oanda' => [
        'key' => env('OANDA_API_KEY'),
        'url' => env('OANDA_API_URL', 'https://api-fxpractice.oanda.com/v3'),
    ],

    'openexchange' => [
        'api_key' => env('OPENEXCHANGE_API_KEY'),
    ],

    'telegram-bot-api' => [
        'token' => env('TELEGRAM_BOT_TOKEN', 'YOUR BOT TOKEN HERE'),
        'username' => env('TELEGRAM_BOT_USERNAME', 'livetrader_engine_bot'),
        'webhook_url' => env('TELEGRAM_WEBHOOK_URL'),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
    ],

    'mailwizz' => [
        'api_key' => env('EMS_API_KEY'),
        'api_url' => env('EMS_API_URL'),
    ],

    'affilio' => [
        'url'      => env('AFFILIO_API_URL'),
        'api_key'  => env('AFFILIO_API_KEY'),
        'username' => env('AFFILIO_USERNAME'),
        'password' => env('AFFILIO_PASSWORD'),
        'lid'      => env('AFFILIO_LID', 302),
    ],

    /*
     | Google Drive — one OAuth client for the whole platform; each company links its own
     | Google account through it (tokens live in google_drive_connections, encrypted).
     |
     | SCOPES: deliberately the narrow set.
     |   drive.file  — the app can only touch files the user explicitly hands it through the
     |                 Google Picker, plus files the app itself created. It CANNOT read the
     |                 rest of the user's Drive. That is exactly what import / export / logo
     |                 need, and it keeps us out of Google's restricted-scope tier.
     |   userinfo.email + openid — so Settings can show which account is connected.
     | Widen this only with a deliberate decision: the broader Drive scopes are classed as
     | restricted by Google and pull the whole app into an annual third-party security
     | assessment before it may be published.
     |
     | The picker also needs a browser API key and the Cloud project number (app id).
    */
    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        // Optional. Unset means "derive from the host being served", which is what a
        // multi-tenant install needs — see GoogleOAuth::redirectUri().
        'redirect'      => env('GOOGLE_REDIRECT_URI'),
        'picker_api_key' => env('GOOGLE_PICKER_API_KEY'),
        'project_number' => env('GOOGLE_PROJECT_NUMBER'),
        'scopes' => [
            'https://www.googleapis.com/auth/drive.file',
            'https://www.googleapis.com/auth/userinfo.email',
            'openid',
        ],
        // Hard ceiling on anything pulled out of Drive, mirroring the existing upload limits
        // so the Drive path can never be the lax way in. Bytes.
        'max_import_bytes' => (int) env('GOOGLE_MAX_IMPORT_BYTES', 20 * 1024 * 1024),
        'max_logo_bytes'   => (int) env('GOOGLE_MAX_LOGO_BYTES', 2 * 1024 * 1024),
    ],

    /*
     | IP-to-country lookups (activity history, login history, language pre-selection) read a local
     | DB-IP Lite country database, refreshed monthly by `php artisan leadmax:geoip-update`.
     | Licence: CC BY 4.0, attribution "IP Geolocation by DB-IP". Unset means
     | storage/app/geoip/dbip-country-lite.mmdb.
    */
    'geoip' => [
        'database' => env('GEOIP_DATABASE'),
    ],

];

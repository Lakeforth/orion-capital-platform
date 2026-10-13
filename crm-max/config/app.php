<?php

use Illuminate\Support\Facades\Facade;

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'domain' => env('APP_DOMAIN', 'trading_new.test'),

    // Comma-separated proxy IPs/CIDRs (or *) whose X-Forwarded-For is believed; unset trusts none.
    'trusted_proxies' => env('TRUSTED_PROXIES'),

    'telegram_chat_id' => env('TELEGRAM_CHAT_ID', '1787743924'),

    'name' => env('APP_NAME'),

    'asset_api_key' => env('ASSET_API_KEY', '9e37abad-04e9-47fb-bbd5-b8e344ff7e5a'),
    'asset_api_base_url' => env('ASSET_API_BASE_URL', 'https://your-market-data-host.example/api'),

    // Second, redundant forex price source. Off by default -- see the note on
    // the schedule entry in routes/console.php for why.
    'forex_updater_enabled' => (bool) env('FOREX_UPDATER_ENABLED', false),

    // Live tick stream behind the trade room. The back office subscribes to
    // the same socket so both surfaces price open positions off one feed
    // rather than one streaming and the other polling a persisted column.
    'asset_feed_socket_url' => env('ASSET_FEED_SOCKET_URL', 'https://your-market-data-host.example'),


    'site_url' => env('SITE_URL'),


    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    'forge_token' => env('FORGE_TOKEN'),
    'custom_domain' => env('CUSTOM_DOMAIN','custom.your-platform-host.example'),
    'server_id' => env('SERVER_ID','985335'),
    'site_id' => env('SITE_ID','2921352'),
    'forge_org' => env('FORGE_ORG','queek-services'),

    'onda_api_key' => env('ONDA_API_KEY','45a68744a7d51608ed4177c4e8d548ad-a396f9135670bd7373b82bb90ed2aea7'),


    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    'use_frontend' => (bool) env('USE_FRONTEND', false),

    'is_mini' => (bool) env('IS_MINI', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'Africa/Lagos'),



    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],


];

<?php

return [
    'url_domain' => env('PLATFORM_URL_DOMAIN', env('APP_DOMAIN', 'trading_new.test')),
    'url_scheme' => env('PLATFORM_URL_SCHEME', parse_url(env('APP_URL', 'https://localhost'), PHP_URL_SCHEME) ?: 'https'),
    'plans_embed_path' => env('PLATFORM_PLANS_EMBED_PATH', '/embed/plans'),

    'urls' => [
        'client_area' => [
            'title' => env('PLATFORM_CLIENT_AREA_TITLE', 'Client Area'),
            'prefix' => env('PLATFORM_CLIENT_AREA_PREFIX', 'client-area'),
        ],
        'crm_backoffice' => [
            'title' => env('PLATFORM_CRM_BACKOFFICE_TITLE', 'CRM Backoffice'),
            'prefix' => env('PLATFORM_CRM_BACKOFFICE_PREFIX', 'crm-backoffice'),
        ],
        'webtrader' => [
            'title' => env('PLATFORM_WEBTRADER_TITLE', 'Webtrader'),
            'prefix' => env('PLATFORM_WEBTRADER_PREFIX', 'webtrader'),
        ],
    ],
];

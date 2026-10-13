<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Title and meta tags (SEO)
    |--------------------------------------------------------------------------
    |
    | You may use the SEO facade to set your page's title, description, and keywords.
    | @see https://splade.dev/docs/title-meta
    |
    */

    'defaults' => [
        'title'       => 'Live Trading',
        'description' => 'A World-Class Investing And Trading Experience',
        'keywords'    => ['investing'],
    ],

    'title_prefix'    => '',
    'title_separator' => '',
    'title_suffix'    => '',

    'auto_canonical_link' => true,

    'open_graph' => [
        'auto_fill' => false,
        'image'     => '/images/banner.png',
        'site_name' => 'Live Trading',
        'title'     => 'Live Trading',
        'type'      => 'WebPage', // 'WebPage'
        'url'       => env('APP_URL'),
    ],

    'twitter' => [
        'auto_fill'   => false,
        'card'        => '/images/banner.png', // 'summary_large_image',
        'description' => 'A World-Class Investing And Trading Experience',
        'image'       => '/images/banner.png',
        'title'       => 'Live Trading',
    ],

];

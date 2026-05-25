<?php

return [
    'themes' => [
        'theme-cream' => 'Cream (Luxury)',
        'theme-onyx' => 'Onyx (Dark)',
        'theme-editorial' => 'Editorial (Minimal)',
    ],

    'organization' => [
        'name' => env('SEO_ORG_NAME', 'LEVANT Parfums'),
        'logo' => env('SEO_ORG_LOGO', '/images/og/logo.png'),
        'phone' => env('SEO_ORG_PHONE'),
        'email' => env('SEO_ORG_EMAIL'),
        'address' => [
            'country' => env('SEO_ORG_COUNTRY', 'UA'),
            'locality' => env('SEO_ORG_CITY'),
            'street' => env('SEO_ORG_STREET'),
        ],
        'same_as' => array_values(array_filter(array_map('trim', explode(',', (string) env('SEO_ORG_SAME_AS', ''))))),
    ],

    'seo' => [
        'default_og_image' => '/images/og/default.jpg',
        'title_suffix' => 'LEVANT Parfums',
        'twitter_card' => 'summary_large_image',
    ],
];

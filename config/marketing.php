<?php

return [

    'url' => rtrim((string) env('MARKETING_URL', env('APP_URL', 'http://localhost')), '/'),

    'brand' => 'Azshrtr',
    'tagline' => 'Short links. Claim or expire.',
    'organization' => 'Azodik Consulting Private Limited',
    'organization_url' => 'https://azodik.com',
    'github' => 'https://github.com/azodik/azshrtr',
    'github_issues' => 'https://github.com/azodik/azshrtr/issues/new/choose',
    'sponsor' => env('MARKETING_SPONSOR', 'https://github.com/sponsors/azodik'),
    'linkedin' => env('MARKETING_LINKEDIN', 'https://www.linkedin.com/company/azodik'),
    'instagram' => env('MARKETING_INSTAGRAM', 'https://www.instagram.com/azodikhq'),
    'twitter' => env('MARKETING_TWITTER', '@azodikhq'),
    'locale' => 'en_US',

    'keywords' => [
        'url shortener',
        'open source url shortener',
        'self-hosted short links',
        'QR code generator',
        'custom short domain',
        'dub.sh alternative',
        'bitly alternative',
        'link analytics',
        'Azshrtr',
    ],

    'google_site_verification' => env('GOOGLE_SITE_VERIFICATION', ''),
    'facebook_domain_verification' => env('FACEBOOK_DOMAIN_VERIFICATION', ''),
    'bing_site_verification' => env('BING_SITE_VERIFICATION', ''),

    'gtm_id' => env('GOOGLE_TAG_MANAGER_ID', ''),
    'ga4_id' => env('GOOGLE_ANALYTICS_ID', ''),
    'meta_pixel_id' => env('META_PIXEL_ID', ''),

    'og_image' => '/images/seo/og.png',
    'twitter_image' => '/images/seo/twitter.png',
    'og_image_width' => 1200,
    'og_image_height' => 630,

];

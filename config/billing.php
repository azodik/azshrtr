<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cloud billing (Dodo Payments) — optional for self-host
    |--------------------------------------------------------------------------
    */

    'enabled' => filter_var(env('AZSHRTR_BILLING_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

    'currency' => env('AZSHRTR_BILLING_CURRENCY', 'USD'),

    'dodo' => [
        'api_key' => env('DODO_PAYMENTS_API_KEY'),
        'webhook_id' => env('DODO_PAYMENTS_WEBHOOK_ID'),
        'webhook_secret' => env('DODO_PAYMENTS_WEBHOOK_SECRET'),
        'environment' => env('DODO_PAYMENTS_ENVIRONMENT', 'test_mode'),
        'base_url' => env('DODO_PAYMENTS_BASE_URL', 'https://test.dodopayments.com'),
        'return_url' => env('DODO_PAYMENTS_RETURN_URL', env('APP_URL').'/console/{organization_id}/billing'),
        'product_pro' => env('DODO_PRODUCT_PRO'),
    ],

];

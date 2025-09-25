<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PayChangu API Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration options for the PayChangu payment
    | gateway integration. PayChangu is the primary payment gateway for
    | mobile money, card payments, and bank transfers in Malawi.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    |
    | Set to 'test' for sandbox mode or 'live' for production mode.
    |
    */
    'environment' => env('PAYCHANGU_ENVIRONMENT', 'test'),

    /*
    |--------------------------------------------------------------------------
    | API Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for PayChangu API endpoints.
    |
    */
    'base_url' => env('PAYCHANGU_BASE_URL', 'https://api.paychangu.com'),

    /*
    |--------------------------------------------------------------------------
    | API Keys
    |--------------------------------------------------------------------------
    |
    | Your PayChangu API keys. Get these from your PayChangu dashboard.
    |
    */
    'public_key' => env('PAYCHANGU_PUBLIC_KEY'),
    'secret_key' => env('PAYCHANGU_SECRET_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for handling PayChangu webhook callbacks.
    |
    */
    'webhook_secret' => env('PAYCHANGU_WEBHOOK_SECRET'),
    'webhook_url' => env('APP_URL') . '/api/webhooks/payments/paychangu',

    /*
    |--------------------------------------------------------------------------
    | Currency Configuration
    |--------------------------------------------------------------------------
    |
    | Supported currencies for payments.
    |
    */
    'default_currency' => 'MWK',
    'supported_currencies' => ['MWK', 'USD'],

    /*
    |--------------------------------------------------------------------------
    | Payment Limits
    |--------------------------------------------------------------------------
    |
    | Minimum and maximum payment amounts.
    |
    */
    'min_amount' => [
        'MWK' => 100,
        'USD' => 1,
    ],
    'max_amount' => [
        'MWK' => 1000000,
        'USD' => 10000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Mobile Money Providers
    |--------------------------------------------------------------------------
    |
    | Configuration for mobile money providers supported by PayChangu.
    |
    */
    'mobile_money' => [
        'airtel_money' => [
            'name' => 'Airtel Money',
            'provider_code' => 'airtel',
            'enabled' => true,
            'prefixes' => ['26599', '26588'],
            'logo' => '/images/providers/airtel.png',
        ],
        'tnm_mpamba' => [
            'name' => 'TNM Mpamba',
            'provider_code' => 'mpamba',
            'enabled' => true,
            'prefixes' => ['26577'],
            'logo' => '/images/providers/tnm.png',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Card Payment Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for card payments through PayChangu.
    |
    */
    'cards' => [
        'enabled' => true,
        'supported_brands' => ['visa', 'mastercard', 'american_express'],
        'test_cards' => [
            '4242424242424242', // Test Visa card
            '5555555555554444', // Test Mastercard
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Bank Transfer Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for bank transfer payments.
    |
    */
    'bank_transfer' => [
        'enabled' => true,
        'supported_banks' => [
            'National Bank of Malawi',
            'Standard Bank',
            'First Capital Bank',
            'NBS Bank',
            'CDH Investment Bank',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Enable/disable logging of PayChangu transactions and webhooks.
    |
    */
    'logging' => [
        'enabled' => env('PAYCHANGU_LOGGING_ENABLED', true),
        'log_requests' => env('PAYCHANGU_LOG_REQUESTS', true),
        'log_responses' => env('PAYCHANGU_LOG_RESPONSES', true),
        'log_webhooks' => env('PAYCHANGU_LOG_WEBHOOKS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Timeout Configuration
    |--------------------------------------------------------------------------
    |
    | HTTP timeout settings for PayChangu API calls.
    |
    */
    'timeout' => [
        'connect' => 10, // seconds
        'request' => 30, // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for retrying failed API calls.
    |
    */
    'retry' => [
        'max_attempts' => 3,
        'delay' => 1000, // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Customization
    |--------------------------------------------------------------------------
    |
    | Default customization options for PayChangu checkout pages.
    |
    */
    'ui_customization' => [
        'default_title' => 'AdPro Payment',
        'default_description' => 'Secure payment via PayChangu',
        'logo' => env('APP_URL') . '/images/logo.png',
        'theme_color' => '#2563eb',
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for customer notifications.
    |
    */
    'notifications' => [
        'email_receipts' => true,
        'sms_notifications' => true,
        'webhook_notifications' => true,
    ],
];
<?php

declare(strict_types=1);

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

    'my8k' => [
        'base_url' => env('MY8K_API_BASE_URL', 'https://api.my8k.com'),
        'api_key' => env('MY8K_API_KEY'),
        'timeout' => env('MY8K_API_TIMEOUT', 30),
    ],

    'woocommerce' => [
        'store_url' => env('WOOCOMMERCE_STORE_URL'),
        'consumer_key' => env('WOOCOMMERCE_CONSUMER_KEY'),
        'consumer_secret' => env('WOOCOMMERCE_CONSUMER_SECRET'),
        'webhook_secret' => env('WOOCOMMERCE_WEBHOOK_SECRET'),
        'version' => env('WOOCOMMERCE_API_VERSION', 'wc/v3'),
    ],

    'paystack' => [
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        'webhook_secret' => env('PAYSTACK_WEBHOOK_SECRET'),
        'base_url' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),
        'currency' => env('PAYSTACK_CURRENCY', 'GHS'),
    ],

    'stripe' => [
        'public_key' => env('STRIPE_PUBLIC_KEY'),
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'currency' => env('STRIPE_CURRENCY', 'USD'),
    ],

    'alerts' => [
        'enabled' => env('ALERTS_ENABLED', false),
        'slack_webhook_url' => env('ALERTS_SLACK_WEBHOOK_URL'),
        'slack_channel' => env('ALERTS_SLACK_CHANNEL', '#alerts'),
        'slack_username' => env('ALERTS_SLACK_USERNAME', 'System Alerts'),
    ],

];

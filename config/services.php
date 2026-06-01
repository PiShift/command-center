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
        'bot_token'       => env('SLACK_BOT_TOKEN'),
        'default_channel' => env('SLACK_DEFAULT_CHANNEL', '#general'),
        'enabled'         => (bool) env('SLACK_NOTIFICATIONS_ENABLED', true),
        // Legacy webhook (used by test button only)
        'webhook_url' => env('SLACK_WEBHOOK_URL'),
    ],

    'api' => [
        'secret_key' => env('API_SECRET_KEY'),
    ],

];

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

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'dolar_api' => [
        'base_url' => env('DOLAR_API_BASE_URL', 'https://ve.dolarapi.com'),
        'timeout' => env('DOLAR_API_TIMEOUT', 10),
    ],

    'bcv' => [
        'base_url' => env('BCV_BASE_URL', 'https://www.bcv.org.ve'),
        'timeout' => env('BCV_TIMEOUT', 10),
        'verify_ssl' => env('BCV_VERIFY_SSL', true),
    ],

];

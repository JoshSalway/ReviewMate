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
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'workos' => [
        'client_id' => env('WORKOS_CLIENT_ID'),
        'secret' => env('WORKOS_API_KEY'),
        'redirect_url' => env('WORKOS_REDIRECT_URL'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'stripe' => [
        'price_starter' => env('STRIPE_PRICE_STARTER'),
        'price_pro' => env('STRIPE_PRICE_PRO'),
    ],

    'sms' => [
        'driver' => env('SMS_DRIVER', 'clicksend'),
    ],

    'clicksend' => [
        'username' => env('CLICKSEND_USERNAME'),
        'api_key'  => env('CLICKSEND_API_KEY'),
        'from'     => env('CLICKSEND_FROM', 'ReviewMate'),
    ],

    'twilio' => [
        'sid' => env('TWILIO_ACCOUNT_SID'),
        'token' => env('TWILIO_AUTH_TOKEN'),
        'from' => env('TWILIO_FROM_NUMBER'),
    ],

    'servicem8' => [
        'client_id'     => env('SERVICEM8_CLIENT_ID'),
        'client_secret' => env('SERVICEM8_CLIENT_SECRET'),
    ],

    'xero' => [
        'client_id'     => env('XERO_CLIENT_ID'),
        'client_secret' => env('XERO_CLIENT_SECRET'),
        'webhook_key'   => env('XERO_WEBHOOK_KEY'),
    ],

    'timely' => [
        'client_id'     => env('TIMELY_CLIENT_ID'),
        'client_secret' => env('TIMELY_CLIENT_SECRET'),
    ],

];

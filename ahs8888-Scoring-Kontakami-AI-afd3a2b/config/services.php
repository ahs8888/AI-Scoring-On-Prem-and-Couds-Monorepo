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

    'AI' => [
        'key' => env('KONTAKAMI_AI_KEY'),
        'model' => env('KONTAKAMI_AI_MODEL')
    ],
    
    'gemini' => [
        'key' => env('GEMINI_KEY'),
    ],


    'socket_broadcast' => [
        'url' => env('SOCKET_BROADCAST_URL'),
        'token' => env('SOCKET_BROADCAST_TOKEN'),
    ],

    'domain' => [
        'admin' => env('ADMIN_DOMAIN'),
        'client' => env('CLIENT_DOMAIN'),
    ],



    'default_avatar' => 'https://yelow-app-storage.s3.ap-southeast-1.amazonaws.com/cnako525c6GTGkUq1nefIJ38mXinpV5JovDMuuws.png',
    'session-user-prefix' => 'ai-kontakami-cloud',
    'session-admin-prefix' => 'ai-kontakami-admin-cloud',

    'PHP_LOCATION' => env('PHP_LOCATION')
];

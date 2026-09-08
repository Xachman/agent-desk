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

        'platform' => [
            'config_token' => env('SLACK_PLATFORM_CONFIG_TOKEN'),
            'refresh_token' => env('SLACK_PLATFORM_REFRESH_TOKEN'),
            'team_id' => env('SLACK_PLATFORM_TEAM_ID'),
            'user_id' => env('SLACK_PLATFORM_USER_ID'),
            'config_token_expires_at' => env('SLACK_PLATFORM_CONFIG_TOKEN_EXPIRES_AT'),
        ],

        'oauth' => [
            'redirect_url' => env('SLACK_OAUTH_REDIRECT_URL', config('app.url') . '/slack/oauth/callback'),
            'agent_callback_url' => env('SLACK_AGENT_CALLBACK_URL', config('app.url') . '/slack/agent/callback'),
        ],

        'events' => [
            'enabled' => env('SLACK_EVENTS_ENABLED', true),
        ],

        'scopes' => explode(',', env('SLACK_BOT_SCOPES', 'app_mentions:read,chat:write,im:read,im:write,users:read')),
    ],

];

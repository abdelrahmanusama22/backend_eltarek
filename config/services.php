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

    // SMS Misr — sms.com.eg. driver: log (dev) | smsmisr (live)
    'smsmisr' => [
        'driver' => env('SMS_DRIVER', 'log'),
        'otp_url' => env('SMSMISR_OTP_URL', 'https://smsmisr.com/api/OTP/'),
        'sms_url' => env('SMSMISR_SMS_URL', 'https://smsmisr.com/api/SMS/'),
        // 1 = live, 2 = test (test does not deliver but validates integration)
        'environment' => env('SMSMISR_ENVIRONMENT', 2),
        'username' => env('SMSMISR_USERNAME'),
        'password' => env('SMSMISR_PASSWORD'),
        'sender' => env('SMSMISR_SENDER'),
        'otp_template' => env('SMSMISR_OTP_TEMPLATE'),
    ],

    'otp' => [
        'length' => (int) env('OTP_LENGTH', 4),
        'ttl_seconds' => (int) env('OTP_TTL_SECONDS', 300),
        'resend_seconds' => (int) env('OTP_RESEND_SECONDS', 60),
        'max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'web_client_id' => env('GOOGLE_WEB_CLIENT_ID'),
        'android_client_id' => env('GOOGLE_ANDROID_CLIENT_ID'),
    ],

];

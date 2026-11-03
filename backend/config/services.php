<?php

return [
    'sms' => [
        'gateway'      => env('SMS_GATEWAY', 'ssl_wireless'),
        'ssl_wireless' => [
            'url'    => env('SMS_API_URL', 'https://sms.sslwireless.com/api/v3/send-sms'),
            'token'  => env('SMS_API_TOKEN'),
            'sid'    => env('SMS_SID'),
            'sender' => env('SMS_DEFAULT_SENDER', 'BazarBD'),
        ],
        'bulk_sms_bd' => [
            'url'       => env('BULK_SMS_URL', 'https://bulksmsbd.net/api/smsapi'),
            'api_key'   => env('BULK_SMS_API_KEY'),
            'sender_id' => env('BULK_SMS_SENDER_ID', 'BazarBD'),
        ],
    ],
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
    ],
    'facebook' => [
        'app_id'     => env('FACEBOOK_APP_ID'),
        'app_secret' => env('FACEBOOK_APP_SECRET'),
    ],

    'zinipay' => [
        'api_key'     => env('ZINIPAY_API_KEY'),
        'base_url'    => env('ZINIPAY_BASE_URL', 'https://api.zinipay.com'),
        'webhook_url' => env('APP_URL').'/api/v1/payment/zinipay/webhook',
    ],
    'firebase' => [
        'server_key' => env('FIREBASE_SERVER_KEY'),
        'project_id' => env('FIREBASE_PROJECT_ID'),
    ],
    'mailgun' => [
        'domain'   => env('MAILGUN_DOMAIN'),
        'secret'   => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme'   => 'https',
    ],
    'tracking' => [
        'url' => env('TRACKING_WORKER_URL'),
        'key' => env('TRACKING_SERVER_KEY'),
    ],
];

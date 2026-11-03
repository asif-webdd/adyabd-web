<?php

/*
 | Only the storefront may call the API from a browser.
 | Set FRONTEND_URL in .env (comma-separated for several origins, e.g. www + non-www).
 | Local development origins are added automatically when APP_ENV=local.
 */
$origins = array_filter(array_map('trim', explode(',', (string) env('FRONTEND_URL', 'http://localhost:3000'))));

if (env('APP_ENV') === 'local') {
    $origins = array_unique(array_merge($origins, [
        'http://localhost:3000', 'http://127.0.0.1:3000', 'http://localhost:3001',
    ]));
}

return [
    'paths'                    => ['api/*'],
    'allowed_methods'          => ['*'],
    'allowed_origins'          => array_values($origins),
    'allowed_origins_patterns' => [],
    'allowed_headers'          => ['*'],
    'exposed_headers'          => [],
    'max_age'                  => 3600,
    'supports_credentials'     => false,
];

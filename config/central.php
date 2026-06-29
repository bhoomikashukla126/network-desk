<?php

$appUrl = rtrim((string) env('APP_URL', 'http://127.0.0.1:3009'), '/');

return [
    'url' => rtrim((string) env('CENTRAL_URL', 'http://127.0.0.1:8000'), '/'),
    'client_id' => env('CLIENT_ID'),
    'client_secret' => env('CLIENT_SECRET'),
    'redirect_uri' => env('REDIRECT_URI') ?: $appUrl.'/auth/callback',
    'scopes' => 'openid profile email workspace',
    'slug' => env('EXTENSION_SLUG', 'network-desk'),
];

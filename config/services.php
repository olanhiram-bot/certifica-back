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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'ssl_pem_path' => env('SSL_PEM_PATH'),

    'provedores' => [
        'django_base_url' => env('PROVEEDORES_DJANGO_BASE_URL', 'http://localhost:8000'),
    ],

    'sso' => [
        'base_url' => env('SSO_BASE_URL', 'http://localhost:8000'),
        'client_id' => env('SSO_CLIENT_ID', ''),
        'client_secret' => env('SSO_CLIENT_SECRET', ''),
    ],

    'auth_offline' => [
        'enabled' => env('AUTH_OFFLINE_MODE', false),
        'provider_username' => env('AUTH_OFFLINE_PROVIDER_USERNAME', 'proveedor'),
        'provider_password' => env('AUTH_OFFLINE_PROVIDER_PASSWORD', 'demo'),
        'admin_username' => env('AUTH_OFFLINE_ADMIN_USERNAME', 'admin'),
        'admin_password' => env('AUTH_OFFLINE_ADMIN_PASSWORD', 'demo'),
    ],
];

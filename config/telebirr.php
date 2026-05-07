<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    |
    | Supported values: test, production.
    |
    */
    'environment' => env('TELEBIRR_ENV', 'test'),

    'base_urls' => [
        'test' => env('TELEBIRR_TEST_BASE_URL', 'https://developerportal.ethiotelebirr.et:38443/apiaccess/payment/gateway'),
        'production' => env('TELEBIRR_PRODUCTION_BASE_URL', 'https://telebirrappcube.ethiomobilemoney.et:38443/apiaccess/payment/gateway'),
    ],

    'base_url' => env('TELEBIRR_BASE_URL'),

    /*
    |--------------------------------------------------------------------------
    | Credentials
    |--------------------------------------------------------------------------
    |
    | Keep all values on the backend. Never expose app_secret or private_key
    | to Flutter, JavaScript, mobile apps, or client logs.
    |
    */
    'fabric_app_id' => env('TELEBIRR_FABRIC_APP_ID'),
    'app_secret' => env('TELEBIRR_APP_SECRET'),
    'merchant_app_id' => env('TELEBIRR_MERCHANT_APP_ID'),
    'merchant_code' => env('TELEBIRR_SHORT_CODE', env('TELEBIRR_MERCHANT_CODE')),
    'private_key_path' => env('TELEBIRR_PRIVATE_KEY_PATH', storage_path('app/private/telebirr/private_key.pem')),

    /*
    |--------------------------------------------------------------------------
    | API Paths
    |--------------------------------------------------------------------------
    */
    'paths' => [
        'token' => env('TELEBIRR_TOKEN_PATH', '/payment/v1/token'),
        'create_order' => env('TELEBIRR_CREATE_ORDER_PATH', '/payment/v1/inapp/createOrder'),
        'query_order' => env('TELEBIRR_QUERY_ORDER_PATH', '/payment/v1/merchant/queryOrder'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Order Defaults
    |--------------------------------------------------------------------------
    */
    'notify_url' => env('TELEBIRR_NOTIFY_URL', env('APP_URL').'/api/telebirr/notify'),
    'redirect_url' => env('TELEBIRR_REDIRECT_URL'),
    'currency' => env('TELEBIRR_CURRENCY', 'ETB'),
    'timeout_express' => env('TELEBIRR_TIMEOUT_EXPRESS', '120m'),
    'business_type' => env('TELEBIRR_BUSINESS_TYPE', 'BuyGoods'),
    'trade_type' => env('TELEBIRR_TRADE_TYPE', 'InApp'),
    'payee_identifier' => env('TELEBIRR_PAYEE_IDENTIFIER'),
    'payee_identifier_type' => env('TELEBIRR_PAYEE_IDENTIFIER_TYPE', '04'),
    'payee_type' => env('TELEBIRR_PAYEE_TYPE', '5000'),

    /*
    |--------------------------------------------------------------------------
    | HTTP and Routes
    |--------------------------------------------------------------------------
    */
    'http_timeout' => (int) env('TELEBIRR_HTTP_TIMEOUT', 45),
    'verify_ssl' => filter_var(env('TELEBIRR_VERIFY_SSL', true), FILTER_VALIDATE_BOOLEAN),
    'route_prefix' => env('TELEBIRR_ROUTE_PREFIX', 'api/telebirr'),
    'routes_enabled' => filter_var(env('TELEBIRR_ROUTES_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    'route_middleware' => array_filter(explode(',', env('TELEBIRR_ROUTE_MIDDLEWARE', 'api'))),

    /*
    |--------------------------------------------------------------------------
    | Safety
    |--------------------------------------------------------------------------
    */
    'log_channel' => env('TELEBIRR_LOG_CHANNEL'),
    'allow_client_merchant_order_id' => filter_var(env('TELEBIRR_ALLOW_CLIENT_ORDER_ID', false), FILTER_VALIDATE_BOOLEAN),
];

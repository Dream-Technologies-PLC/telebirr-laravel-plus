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
    'private_key' => env('TELEBIRR_PRIVATE_KEY'),
    'private_key_path' => env('TELEBIRR_PRIVATE_KEY_PATH', storage_path('app/private/telebirr/private_key.pem')),
    'public_key' => env('TELEBIRR_PUBLIC_KEY'),
    'public_key_path' => env('TELEBIRR_PUBLIC_KEY_PATH'),
    'callback_signature_required' => filter_var(
        env('TELEBIRR_CALLBACK_SIGNATURE_REQUIRED', true),
        FILTER_VALIDATE_BOOLEAN
    ),

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
    'method' => env('TELEBIRR_METHOD', 'payment.preorder'),
    'order_title' => env('TELEBIRR_ORDER_TITLE'),
    'order_amount' => env('TELEBIRR_ORDER_AMOUNT'),
    'currency' => env('TELEBIRR_CURRENCY', 'ETB'),
    'timeout_express' => env('TELEBIRR_TIMEOUT_EXPRESS', '120m'),
    'business_type' => env('TELEBIRR_BUSINESS_TYPE', 'BuyGoods'),
    'trade_type' => env('TELEBIRR_TRADE_TYPE', 'InApp'),
    'payee_identifier' => env('TELEBIRR_PAYEE_IDENTIFIER'),
    'payee_identifier_type' => env('TELEBIRR_PAYEE_IDENTIFIER_TYPE', '04'),
    'payee_type' => env('TELEBIRR_PAYEE_TYPE', '3000'),

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
    'client_route_middleware' => array_filter(explode('|', env('TELEBIRR_CLIENT_ROUTE_MIDDLEWARE', 'api|auth|throttle:30,1'))),
    'notify_route_middleware' => array_filter(explode('|', env('TELEBIRR_NOTIFY_ROUTE_MIDDLEWARE', 'api|throttle:60,1'))),

    /*
    |--------------------------------------------------------------------------
    | Safety
    |--------------------------------------------------------------------------
    */
    'log_channel' => env('TELEBIRR_LOG_CHANNEL'),
    'allow_client_merchant_order_id' => filter_var(env('TELEBIRR_ALLOW_CLIENT_ORDER_ID', false), FILTER_VALIDATE_BOOLEAN),
    'allow_client_notify_url' => filter_var(env('TELEBIRR_ALLOW_CLIENT_NOTIFY_URL', false), FILTER_VALIDATE_BOOLEAN),
    'allow_client_redirect_url' => filter_var(env('TELEBIRR_ALLOW_CLIENT_REDIRECT_URL', false), FILTER_VALIDATE_BOOLEAN),
    'allow_client_callback_info' => filter_var(env('TELEBIRR_ALLOW_CLIENT_CALLBACK_INFO', false), FILTER_VALIDATE_BOOLEAN),
];

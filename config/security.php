<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Security Headers Configuration
    |--------------------------------------------------------------------------
    |
    | Configure security headers for your application
    |
    */

    'headers_enabled' => env('SECURITY_HEADERS_ENABLED', true),

    'hsts_enabled' => env('SECURITY_HSTS_ENABLED', true),

    'csp_enabled' => env('SECURITY_CSP_ENABLED', true),

    'xss_protection' => env('SECURITY_XSS_PROTECTION', true),

    'frame_options' => env('SECURITY_FRAME_OPTIONS', 'DENY'),

    /*
    |--------------------------------------------------------------------------
    | HTTPS Enforcement
    |--------------------------------------------------------------------------
    |
    | Force HTTPS redirects in production
    |
    */

    'force_https' => env('FORCE_HTTPS', false),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for different endpoints
    |
    */

    'rate_limit' => [
        'login' => env('RATE_LIMIT_LOGIN', 5),
        'api' => env('RATE_LIMIT_API', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Configure trusted proxies for your application
    |
    */

    'trusted_proxies' => env('TRUSTED_PROXIES'),

    'trusted_hosts' => env('TRUSTED_HOSTS'),

];

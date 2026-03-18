<?php

return [
    /*
    |--------------------------------------------------------------------------
    | JWT Secret Key
    |--------------------------------------------------------------------------
    |
    | This key is used for signing JWT tokens. It should be set to a random,
    | 32 character string, otherwise these signatures will not be secure.
    | Make sure to set this in your .env file and keep it secret!
    |
    */
    'secret_key' => env('JWT_SECRET_KEY'),

    /*
    |--------------------------------------------------------------------------
    | JWT Signature Algorithm
    |--------------------------------------------------------------------------
    |
    | The algorithm used to sign the JWT tokens. Supported algorithms:
    | - HS256 (HMAC SHA-256) - Recommended for most use cases
    | - HS384 (HMAC SHA-384) - Higher security, slightly slower
    | - HS512 (HMAC SHA-512) - Maximum security, slowest performance
    |
    */
    'algorithm' => env('JWT_ALGORITHM', 'HS256'),

    /*
    |--------------------------------------------------------------------------
    | Access Token Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the behavior of access tokens including their lifetime.
    |
    */
    'access_token' => [
        /*
        |--------------------------------------------------------------------------
        | Token Lifetime
        |--------------------------------------------------------------------------
        |
        | The lifetime of access tokens in seconds. After this time expires,
        | the token will no longer be valid and users will need to refresh
        | or re-authenticate. Default is 3600 seconds (1 hour).
        |
        */
        'lifetime' => env('JWT_ACCESS_TOKEN_LIFETIME', 3600),
    ],

    /*
    |--------------------------------------------------------------------------
    | Refresh Token Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the behavior of refresh tokens including their lifetime.
    | Refresh tokens typically have a much longer lifetime than access tokens.
    |
    */
    'refresh_token' => [
        /*
        |--------------------------------------------------------------------------
        | Token Lifetime
        |--------------------------------------------------------------------------
        |
        | The lifetime of refresh tokens in seconds. After this time expires,
        | the refresh token will no longer be valid and users will need to
        | re-authenticate. Default is 3600 seconds (1 hour).
        |
        */
        'lifetime' => env('JWT_REFRESH_TOKEN_LIFETIME', 3600),
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto Refresh Configuration
    |--------------------------------------------------------------------------
    |
    | Configure automatic token refresh behavior. When enabled, tokens will
    | be automatically refreshed when they are close to expiration or recently
    | expired within the grace period.
    |
    */
    'auto_refresh' => [
        /*
        |--------------------------------------------------------------------------
        | Enable Auto Refresh
        |--------------------------------------------------------------------------
        |
        | When enabled, tokens will be automatically refreshed if they are
        | close to expiration. This provides a seamless experience for users
        | without requiring manual token refresh.
        |
        */
        'enabled' => env('JWT_AUTO_REFRESH', false),

        /*
        |--------------------------------------------------------------------------
        | Grace Period After Expiration
        |--------------------------------------------------------------------------
        |
        | Time in seconds after token expiration during which auto-refresh
        | is still allowed. Default is 3600 seconds (1 hour).
        |
        */
        'grace_period' => env('JWT_AUTO_REFRESH_GRACE_PERIOD', 3600),

        /*
        |--------------------------------------------------------------------------
        | Cache TTL
        |--------------------------------------------------------------------------
        |
        | Time in seconds to cache auto-refreshed tokens to prevent duplicate
        | refresh requests. Default is 10 seconds.
        |
        */
        'cache_ttl' => env('JWT_AUTO_REFRESH_CACHE_TTL', 10),

        /*
        |--------------------------------------------------------------------------
        | Preemptive Refresh Window
        |--------------------------------------------------------------------------
        |
        | Time in seconds before token expiration when preemptive refresh starts.
        | For example, if token lifetime is 3600 seconds (60 minutes) and this is
        | set to 600 seconds (10 minutes), tokens will be automatically refreshed
        | for requests made in the last 600 seconds before expiration.
        | Set to 0 to disable preemptive refresh (only refresh after expiration).
        | Default is 1800 seconds (30 minutes).
        |
        */
        'preemptive_refresh' => env('JWT_AUTO_REFRESH_PREEMPTIVE', 0),
    ],
];

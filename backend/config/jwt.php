<?php

/*
|--------------------------------------------------------------------------
| JWT Configuration
|--------------------------------------------------------------------------
|
| This file configures the JWT authentication for the application.
| The tymon/jwt-auth package requires this configuration file.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | JWT Secret
    |--------------------------------------------------------------------------
    |
    | The secret key used to sign tokens. This should be kept secure and not shared.
    | It is used to verify the authenticity of the token.
    |
    | Generated with: php artisan jwt:secret
    |
    */
    'secret' => env('JWT_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | JWT Public Key
    |--------------------------------------------------------------------------
    |
    | The public key used to verify tokens. This is optional and is used
    | when using asymmetric signing algorithms.
    |
    */
    'public_key' => env('JWT_PUBLIC_KEY'),

    /*
    |--------------------------------------------------------------------------
    | JWT Private Key
    |--------------------------------------------------------------------------
    |
    | The private key used to sign tokens. This is optional and is used
    | when using asymmetric signing algorithms.
    |
    */
    'private_key' => env('JWT_PRIVATE_KEY'),

    /*
    |--------------------------------------------------------------------------
    | JWT Password
    |--------------------------------------------------------------------------
    |
    | The password used to encrypt the private key. This is optional.
    |
    */
    'password' => env('JWT_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | Token Time to Live (TTL)
    |--------------------------------------------------------------------------
    |
    | The number of minutes a token is valid for. Default is 60 minutes.
    | You can set this to a longer duration for better UX.
    |
    | 1440 minutes = 24 hours
    |
    */
    'ttl' => env('JWT_TTL', 1440),

    /*
    |--------------------------------------------------------------------------
    | Refresh Token Time to Live (TTL)
    |--------------------------------------------------------------------------
    |
    | The number of minutes a refresh token is valid for. Default is 20160 minutes (2 weeks).
    | This is the window within which a token can be refreshed.
    |
    | 20160 minutes = 14 days
    |
    */
    'refresh_ttl' => env('JWT_REFRESH_TTL', 20160),

    /*
    |--------------------------------------------------------------------------
    | Token Algorithm
    |--------------------------------------------------------------------------
    |
    | The algorithm used to sign the token. HS256 is the default and is
    | secure for most applications.
    |
    | Supported: HS256, HS384, HS512, RS256, RS384, RS512, ES256, ES384, ES512
    |
    */
    'algo' => env('JWT_ALGO', 'HS256'),

    /*
    |--------------------------------------------------------------------------
    | Required Claims
    |--------------------------------------------------------------------------
    |
    | The claims that are required in the token.
    |
    */
    'required_claims' => [
        'iss',
        'iat',
        'exp',
        'nbf',
        'sub',
        'jti',
    ],

    /*
    |--------------------------------------------------------------------------
    | Persistent Claims
    |--------------------------------------------------------------------------
    |
    | The claims that should persist when refreshing a token.
    |
    */
    'persistent_claims' => [],

    /*
    |--------------------------------------------------------------------------
    | Lock Subject
    |--------------------------------------------------------------------------
    |
    | Whether to lock the subject (sub claim) when generating tokens.
    |
    */
    'lock_subject' => true,

    /*
    |--------------------------------------------------------------------------
    | Leeway
    |--------------------------------------------------------------------------
    |
    | The number of seconds to add to the expiration time (exp claim) to
    | account for clock skew.
    |
    */
    'leeway' => env('JWT_LEEWAY', 0),

    /*
    |--------------------------------------------------------------------------
    | Blacklist Enabled
    |--------------------------------------------------------------------------
    |
    | Whether to enable the blacklist. When enabled, invalidated
    | tokens are added to the blacklist to prevent reuse.
    |
    */
    'blacklist_enabled' => env('JWT_BLACKLIST_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Blacklist Grace Period
    |--------------------------------------------------------------------------
    |
    | The number of seconds to wait before adding a token to the
    | blacklist after it has been invalidated.
    |
    */
    'blacklist_grace_period' => env('JWT_BLACKLIST_GRACE_PERIOD', 0),

    /*
    |--------------------------------------------------------------------------
    | Decrypt Cookies
    |--------------------------------------------------------------------------
    |
    | The cookies that should be decrypted.
    |
    */
    'decrypt_cookies' => false,

    /*
    |--------------------------------------------------------------------------
    | Providers
    |--------------------------------------------------------------------------
    |
    | The JWT providers used by the application.
    |
    */
    'providers' => [

        /*
        |--------------------------------------------------------------------------
        | JWT Provider
        |--------------------------------------------------------------------------
        |
        | The provider used to create and decode tokens.
        |
        */
        'jwt' => Tymon\JWTAuth\Providers\JWT\Lcobill::class,

        /*
        |--------------------------------------------------------------------------
        | Authentication Provider
        |--------------------------------------------------------------------------
        |
        | The provider used to authenticate users.
        |
        */
        'auth' => Tymon\JWTAuth\Providers\Auth\Illuminate::class,

        /*
        |--------------------------------------------------------------------------
        | Storage Provider
        |--------------------------------------------------------------------------
        |
        | The provider used to store tokens in the blacklist.
        |
        */
        'storage' => Tymon\JWTAuth\Providers\Storage\Illuminate::class,

    ],

];
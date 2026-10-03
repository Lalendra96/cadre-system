<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Personnel data protection
    |--------------------------------------------------------------------------
    | Keep these keys separate from APP_KEY. PII_ENCRYPTION_KEY is used only
    | for reversible application-layer encryption. PII_SEARCH_KEY is used only
    | for deterministic HMAC-SHA256 blind indexes (exact-match search).
    |
    | Generate 32 random bytes for each key and store as base64:<value> in .env.
    */
    'enabled' => env('PII_ENCRYPTION_ENABLED', false),
    'encryption_key' => env('PII_ENCRYPTION_KEY'),
    'search_key' => env('PII_SEARCH_KEY'),
    'cipher' => env('PII_CIPHER', 'AES-256-CBC'),
    'prefix' => 'pii:v1:',
];

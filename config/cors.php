<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Explicit allowlist of origins permitted to make cross-origin requests
    | to this API. Only the known prod, demo, and local dev frontends are
    | listed here — no wildcard, no pattern matching. See CORS-01.
    |
    | supports_credentials is enabled so the allowlisted frontends may send
    | and receive the httpOnly auth cookie cross-origin (CORS-02). This is
    | safe only because the allowlist above stays exact-origin literals with
    | no wildcard or pattern matching — widening it while credentials are on
    | would let any matching host read authenticated responses.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://underpass.up.railway.app',
        'https://underpass-demo.up.railway.app',
        'http://localhost:5173',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];

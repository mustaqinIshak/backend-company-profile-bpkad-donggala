<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS)
    |--------------------------------------------------------------------------
    |
    | supports_credentials MUST be true when the frontend sends cookies
    | (withCredentials: true). allowed_origins MUST NOT be '*' when
    | credentials are involved — list each frontend origin explicitly.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // Add additional frontend origins separated by commas in FRONTEND_URLS env var
    'allowed_origins' => array_filter(
        explode(',', env('FRONTEND_URLS', 'http://localhost:3000,http://localhost:5173'))
    ),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'X-Requested-With', 'Accept', 'Authorization'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Required for httpOnly cookie auth — allows browser to send credentials cross-origin
    'supports_credentials' => true,

];

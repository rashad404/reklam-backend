<?php

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    'allowed_origins' => array_filter(explode(',', env('CORS_ALLOWED_ORIGINS', env('FRONTEND_URL', 'https://reklam.biz')))),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Content-Type', 'Accept', 'Authorization'],
    'exposed_headers' => [], 'max_age' => 3600, 'supports_credentials' => false,
];

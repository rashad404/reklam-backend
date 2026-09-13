<?php

return [
    'node_binary' => env('NODE_BINARY', 'node'),
    'frontend_url' => env('FRONTEND_URL', 'https://reklam.biz'),
    'embed_url' => env('EMBED_URL', rtrim(env('APP_URL', 'https://api.reklam.biz'), '/').'/serve.js'),
    'delivery_enabled' => (bool) env('AD_DELIVERY_ENABLED', false),
    'platform_commission' => (float) env('PLATFORM_COMMISSION', 0.30),
    'timezone' => 'Asia/Baku',
    'raw_retention_days' => (int) env('TRAFFIC_RETENTION_DAYS', 90),
];

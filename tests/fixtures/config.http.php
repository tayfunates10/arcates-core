<?php
return [
    'app' => [
        'name' => 'Arcates HTTP Smoke',
        'url' => 'http://127.0.0.1:8088',
        'env' => 'testing',
        'debug' => false,
        'timezone' => 'Europe/Istanbul',
        'session_name' => 'arcates_http_smoke',
        'admin_path' => 'yonetim-ci',
        'default_locale' => 'tr',
        'supported_locales' => ['tr', 'en', 'de', 'ar'],
    ],
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'arcates_http',
        'user' => 'root',
        'pass' => 'root',
        'charset' => 'utf8mb4',
    ],
    'security' => [
        'force_https' => false,
        'max_upload_bytes' => 5242880,
        'form_retention_days' => 180,
        'trusted_proxies' => [],
        'abandoned_order_minutes' => 1440,
        'abandoned_order_batch' => 100,
    ],
    'analytics' => [
        'daily_path_limit' => 50,
    ],
    'integrations' => [
        'payment_provider' => null,
        'ai' => ['enabled' => false],
    ],
];

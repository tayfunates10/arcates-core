<?php
return [
    'app' => [
        'name' => 'Arcates CI',
        'url' => 'http://localhost',
        'env' => 'testing',
        'debug' => false,
        'timezone' => 'Europe/Istanbul',
        'session_name' => 'arcates_ci',
        'admin_path' => 'yonetim-ci',
        'default_locale' => 'tr',
        'supported_locales' => ['tr', 'en', 'de', 'ar'],
    ],
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'arcates',
        'user' => 'root',
        'pass' => 'root',
        'charset' => 'utf8mb4',
    ],
    'security' => [
        'force_https' => false,
        'max_upload_bytes' => 5242880,
        'form_retention_days' => 180,
        'trusted_proxies' => [],
    ],
    'analytics' => [
        'daily_path_limit' => 50,
    ],
];

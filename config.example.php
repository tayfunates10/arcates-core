<?php
return [
    'app' => [
        'name' => 'Arcates Core',
        'url' => 'http://localhost',
        'env' => 'production',
        'debug' => false,
        'timezone' => 'Europe/Istanbul',
        'session_name' => 'arcates_session',
        'admin_path' => 'yonetim',
        'default_locale' => 'tr',
        'supported_locales' => ['tr', 'en', 'de', 'ar'],
    ],
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'arcates',
        'user' => 'arcates',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    'mail' => [
        'from' => 'noreply@example.com',
        'to' => 'info@example.com',
    ],
    'contact' => [
        'whatsapp_phone' => '',
        'whatsapp_message' => 'Merhaba, bilgi almak istiyorum.',
    ],
    'security' => [
        'force_https' => true,
        'max_upload_bytes' => 5242880,
        'form_retention_days' => 180,
    ],
    'integrations' => [
        'payment_provider' => null,
        'payment_sdk_path' => __DIR__ . '/integrations/iyzipay/IyzipayBootstrap.php',
        'iyzico' => [
            'api_key' => '',
            'secret_key' => '',
            'base_url' => 'https://sandbox-api.iyzipay.com',
        ],
        'marketplace' => [],
        'einvoice' => [],
    ],
];

<?php
declare(strict_types=1);

namespace Arcates\Core;

final class Security
{
    public static function startSession(array $config): void
    {
        if (session_status() === PHP_SESSION_ACTIVE || PHP_SAPI === 'cli') { return; }
        $secure = self::isHttps() || (bool)($config['security']['force_https'] ?? false);
        session_name((string)($config['app']['session_name'] ?? 'arcates_session'));
        session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Lax']);
        ini_set('session.use_strict_mode', '1');
        session_start();
    }

    public static function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    }

    public static function escape(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function clientIp(): string { return substr((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45); }
    public static function randomToken(int $bytes = 32): string { return bin2hex(random_bytes($bytes)); }
}

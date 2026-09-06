<?php
declare(strict_types=1);

namespace Arcates\Core;

final class Security
{
    public static function startSession(array $config): void
    {
        if (session_status() === PHP_SESSION_ACTIVE || PHP_SAPI === 'cli') {
            return;
        }
        $secure = self::isHttps() || (bool) ($config['security']['force_https'] ?? false);
        session_name((string) ($config['app']['session_name'] ?? 'arcates_session'));
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        ini_set('session.use_strict_mode', '1');
        session_start();
    }

    public static function isHttps(): bool
    {
        $direct = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        if ($direct) {
            return true;
        }
        $remote = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        if (!self::trustedProxy($remote)) {
            return false;
        }
        return strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    }

    public static function escape(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function clientIp(): string
    {
        $remote = substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
        if (!self::trustedProxy($remote)) {
            return $remote;
        }

        $candidates = [];
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $candidates[] = trim((string) $_SERVER['HTTP_CF_CONNECTING_IP']);
        }
        foreach (explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '')) as $candidate) {
            $candidate = trim($candidate);
            if ($candidate !== '') {
                $candidates[] = $candidate;
            }
        }
        foreach ($candidates as $candidate) {
            if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                return substr($candidate, 0, 45);
            }
        }
        return $remote;
    }

    public static function randomToken(int $bytes = 32): string
    {
        return bin2hex(random_bytes($bytes));
    }

    private static function trustedProxy(string $ip): bool
    {
        $trusted = (array) App::config('security.trusted_proxies', []);
        return $ip !== '' && in_array($ip, $trusted, true);
    }
}

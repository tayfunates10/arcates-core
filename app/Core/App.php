<?php
declare(strict_types=1);

namespace Arcates\Core;

final class App
{
    private static ?Database $db = null;

    public static function config(?string $key = null, mixed $default = null): mixed
    {
        $config = $GLOBALS['arcates_config'] ?? [];
        if ($key === null) { return $config; }
        $value = $config;
        foreach (explode('.', $key) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) { return $default; }
            $value = $value[$part];
        }
        return $value;
    }

    public static function db(): Database { return self::$db ??= new Database((array)self::config()); }

    public static function auth(): Auth
    {
        $db = self::db();
        return new Auth($db, new RateLimiter($db));
    }
}

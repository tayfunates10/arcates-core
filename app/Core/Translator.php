<?php
declare(strict_types=1);

namespace Arcates\Core;

final class Translator
{
    private static array $cache = [];

    public static function t(string $key, ?string $locale = null): string
    {
        $locale ??= self::requestLocale();
        $messages = self::messages($locale);
        if (array_key_exists($key, $messages)) {
            return (string) $messages[$key];
        }
        $fallback = self::messages('tr');
        return (string) ($fallback[$key] ?? $key);
    }

    public static function requestLocale(): string
    {
        $default = (string) App::config('app.default_locale', 'tr');
        $path = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');
        $first = explode('/', trim($path, '/'))[0] ?? '';
        return Locale::valid($first) ? $first : (Locale::valid($default) ? $default : 'tr');
    }

    private static function messages(string $locale): array
    {
        if (isset(self::$cache[$locale])) {
            return self::$cache[$locale];
        }
        $file = ARCATES_ROOT . '/lang/' . $locale . '.php';
        $messages = is_file($file) ? require $file : [];
        return self::$cache[$locale] = is_array($messages) ? $messages : [];
    }
}

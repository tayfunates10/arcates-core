<?php
declare(strict_types=1);

namespace Arcates\Core;

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) { $_SESSION['_csrf'] = Security::randomToken(); }
        return (string)$_SESSION['_csrf'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . Security::escape(self::token()) . '">';
    }

    public static function validate(?string $token): bool
    {
        $known = (string)($_SESSION['_csrf'] ?? '');
        return $known !== '' && is_string($token) && hash_equals($known, $token);
    }

    public static function requireValid(?string $token): void
    {
        if (!self::validate($token)) {
            http_response_code(419);
            throw new \RuntimeException('Geçersiz CSRF token.');
        }
    }
}

<?php
declare(strict_types=1);
namespace Arcates\Core;
final class Locale
{
    public static function supported(): array { return (array)App::config('app.supported_locales',['tr','en','de','ar']); }
    public static function valid(string $locale): bool { return in_array($locale,self::supported(),true); }
    public static function rtl(string $locale): bool { return $locale==='ar'; }
    public static function translations(string $locale): array
    {
        $locale=self::valid($locale)?$locale:(string)App::config('app.default_locale','tr'); $file=ARCATES_ROOT.'/lang/'.$locale.'.php'; return is_file($file)?(array)require $file:[];
    }
}

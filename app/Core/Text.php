<?php
declare(strict_types=1);

namespace Arcates\Core;

final class Text
{
    public static function slug(string $value): string
    {
        $map = [
            'ş' => 's', 'Ş' => 's', 'ı' => 'i', 'İ' => 'i', 'ğ' => 'g', 'Ğ' => 'g',
            'ü' => 'u', 'Ü' => 'u', 'ö' => 'o', 'Ö' => 'o', 'ç' => 'c', 'Ç' => 'c',
            'ß' => 'ss', 'ä' => 'a', 'Ä' => 'a', 'ë' => 'e', 'Ë' => 'e',
        ];
        $value = mb_strtolower(strtr(trim($value), $map), 'UTF-8');
        $value = preg_replace('/[^\p{L}\p{N}]+/u', '-', $value) ?? '';
        $value = trim($value, '-');
        if ($value === '') {
            return 'icerik-' . bin2hex(random_bytes(4));
        }
        return mb_substr($value, 0, 190);
    }
}

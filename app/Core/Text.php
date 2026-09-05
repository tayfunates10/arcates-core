<?php
declare(strict_types=1);
namespace Arcates\Core;
final class Text
{
    public static function slug(string $value): string
    {
        $map=['ş'=>'s','Ş'=>'s','ı'=>'i','İ'=>'i','ğ'=>'g','Ğ'=>'g','ü'=>'u','Ü'=>'u','ö'=>'o','Ö'=>'o','ç'=>'c','Ç'=>'c'];
        $value=strtr(trim($value),$map); $value=strtolower($value); $value=preg_replace('/[^a-z0-9]+/','-',$value)??'';
        return trim($value,'-')?:'sayfa';
    }
}

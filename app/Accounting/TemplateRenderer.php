<?php
declare(strict_types=1);
namespace Arcates\Accounting;

final class TemplateRenderer
{
    public static function render(array $template,array $order,array $items): array
    {
        $value=self::node($template,$order,$items,null);
        if(!is_array($value))throw new \RuntimeException('Muhasebe şablonu JSON nesnesi üretmeli.');
        return $value;
    }
    private static function node(mixed $value,array $order,array $items,?array $item): mixed
    {
        if(is_array($value)){
            if(isset($value['$each'])&&$value['$each']==='items'&&array_key_exists('template',$value)){
                $out=[];foreach($items as $row)$out[]=self::node($value['template'],$order,$items,$row);return $out;
            }
            $out=[];foreach($value as $k=>$v)$out[$k]=self::node($v,$order,$items,$item);return $out;
        }
        if(!is_string($value))return $value;
        if(preg_match('/^\{\{(order|item)\.([a-zA-Z0-9_]+)\}\}$/',$value,$m)){
            $src=$m[1]==='order'?$order:($item??[]);return $src[$m[2]]??null;
        }
        return preg_replace_callback('/\{\{(order|item)\.([a-zA-Z0-9_]+)\}\}/',function(array $m)use($order,$item): string{$src=$m[1]==='order'?$order:($item??[]);$v=$src[$m[2]]??'';return is_scalar($v)?(string)$v:'';},$value)??$value;
    }
}

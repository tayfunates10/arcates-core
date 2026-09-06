<?php
declare(strict_types=1);
namespace Arcates\Core;
final class Router
{
    private array $routes=[];
    public function get(string $path,callable|array $handler): void{$this->add('GET',$path,$handler);} public function post(string $path,callable|array $handler): void{$this->add('POST',$path,$handler);}
    public function add(string $method,string $path,callable|array $handler): void
    {
        $normalized=$this->normalize($path);$parts=preg_split('/(\{[a-zA-Z_][a-zA-Z0-9_]*\})/',$normalized,-1,PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY)?:[];$pattern='';
        foreach($parts as $part){if(preg_match('/^\{([a-zA-Z_][a-zA-Z0-9_]*)\}$/',$part,$m)){$pattern.='(?P<'.$m[1].'>[^/]+)';}else{$pattern.=preg_quote($part,'#');}}
        $this->routes[strtoupper($method)][]=['pattern'=>'#^'.$pattern.'$#u','handler'=>$handler];
    }
    public function dispatch(string $method,string $uri): mixed
    {
        $path=$this->normalize((string)(parse_url($uri,PHP_URL_PATH)?:'/'));
        foreach($this->routes[strtoupper($method)]??[] as $route){if(!preg_match($route['pattern'],$path,$matches))continue;$params=array_filter($matches,'is_string',ARRAY_FILTER_USE_KEY);$handler=$route['handler'];if(is_array($handler)&&is_string($handler[0])){$instance=new $handler[0]();return $instance->{$handler[1]}(...array_values($params));}return $handler(...array_values($params));}
        http_response_code(404);echo '<!doctype html><html lang="tr"><meta charset="utf-8"><title>404</title><body><h1>404</h1><p>Sayfa bulunamadı.</p></body></html>';return null;
    }
    private function normalize(string $path): string{$path='/'.trim($path,'/');return $path==='//'?'/':$path;}
}

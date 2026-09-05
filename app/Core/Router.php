<?php
declare(strict_types=1);

namespace Arcates\Core;

final class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $handler): void { $this->add('GET', $path, $handler); }
    public function post(string $path, callable|array $handler): void { $this->add('POST', $path, $handler); }

    public function add(string $method, string $path, callable|array $handler): void
    {
        $this->routes[strtoupper($method)][$this->normalize($path)] = $handler;
    }

    public function dispatch(string $method, string $uri): mixed
    {
        $path = $this->normalize((string)(parse_url($uri, PHP_URL_PATH) ?: '/'));
        $handler = $this->routes[strtoupper($method)][$path] ?? null;
        if ($handler === null) {
            http_response_code(404);
            return $this->render404();
        }
        if (is_array($handler) && is_string($handler[0])) {
            $class = $handler[0];
            $instance = new $class();
            return $instance->{$handler[1]}();
        }
        return $handler();
    }

    private function normalize(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '//' ? '/' : $path;
    }

    private function render404(): void
    {
        echo '<!doctype html><html lang="tr"><meta charset="utf-8"><title>404</title><body><h1>404</h1><p>Sayfa bulunamadı.</p></body></html>';
    }
}

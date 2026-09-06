<?php
declare(strict_types=1);

namespace Arcates\Core;

final class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function add(string $method, string $path, callable|array $handler): void
    {
        $normalized = $this->normalize($path);
        $parts = preg_split(
            '/(\{[a-zA-Z_][a-zA-Z0-9_]*\})/',
            $normalized,
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        ) ?: [];
        $pattern = '';
        foreach ($parts as $part) {
            if (preg_match('/^\{([a-zA-Z_][a-zA-Z0-9_]*)\}$/', $part, $matches)) {
                $pattern .= '(?P<' . $matches[1] . '>[^/]+)';
            } else {
                $pattern .= preg_quote($part, '#');
            }
        }
        $this->routes[strtoupper($method)][] = [
            'pattern' => '#^' . $pattern . '$#u',
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $uri): mixed
    {
        $method = strtoupper($method);
        if ($method === 'HEAD') {
            ob_start();
            try {
                return $this->dispatch('GET', $uri);
            } finally {
                ob_end_clean();
            }
        }
        if ($method === 'OPTIONS') {
            if (!headers_sent()) {
                header('Allow: GET, HEAD, POST, OPTIONS');
            }
            http_response_code(204);
            return null;
        }

        $path = $this->normalize((string) (parse_url($uri, PHP_URL_PATH) ?: '/'));
        foreach ($this->routes[$method] ?? [] as $route) {
            if (!preg_match($route['pattern'], $path, $matches)) {
                continue;
            }
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            $handler = $route['handler'];
            if (is_array($handler) && is_string($handler[0])) {
                $instance = new $handler[0]();
                return $instance->{$handler[1]}(...array_values($params));
            }
            return $handler(...array_values($params));
        }

        ErrorPage::render(404);
        return null;
    }

    private function normalize(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '//' ? '/' : $path;
    }
}

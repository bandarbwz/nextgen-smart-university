<?php

declare(strict_types=1);

namespace App\Helpers;

class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function put(string $path, callable $handler): void
    {
        $this->add('PUT', $path, $handler);
    }

    public function delete(string $path, callable $handler): void
    {
        $this->add('DELETE', $path, $handler);
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = rtrim(parse_url($uri, PHP_URL_PATH) ?? '', '/');

        if ($path === '') {
            $path = '/';
        }

        $pathMatched = false;

        foreach ($this->routes as $route) {
            $pattern = '#^' . preg_replace('#\{[a-zA-Z_]+\}#', '([^/]+)', $route['path']) . '$#';

            if (preg_match($pattern, $path, $matches) !== 1) {
                continue;
            }

            $pathMatched = true;

            if ($route['method'] !== $method) {
                continue;
            }

            array_shift($matches);

            $route['handler'](...$matches);

            return;
        }

        if ($pathMatched) {
            Response::error('This HTTP method is not supported for the requested endpoint.', 405);
        }

        Response::error('The requested endpoint was not found.', 404);
    }

    private function add(string $method, string $path, callable $handler): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => rtrim($path, '/') ?: '/',
            'handler' => $handler,
        ];
    }
}

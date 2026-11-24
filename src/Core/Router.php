<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<int, array{method: string, path: string, handler: array{0: class-string, 1: string}, middleware: array<int, mixed>}> */
    private array $routes = [];

    public function get(string $path, array $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    private function add(string $method, string $path, array $handler, array $middleware): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(): void
    {
        $method = Request::method();
        $path = Request::path();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = $this->compile($route['path']);

            if (preg_match($pattern, $path, $matches) !== 1) {
                continue;
            }

            array_shift($matches);

            foreach ($route['middleware'] as $middleware) {
                $instance = is_string($middleware) ? new $middleware() : $middleware;

                if (method_exists($instance, 'handle') && $instance->handle() === false) {
                    return;
                }
            }

            [$controllerClass, $action] = $route['handler'];
            $controller = new $controllerClass();
            $controller->{$action}(...$matches);

            return;
        }

        Response::abort(404, 'La página que buscás no existe.');
    }

    private function compile(string $path): string
    {
        $path = '/' . trim($path, '/');
        $regex = preg_replace('#\{[a-zA-Z_][a-zA-Z0-9_]*\}#', '([^/]+)', $path) ?? $path;

        return '#^' . $regex . '$#';
    }
}

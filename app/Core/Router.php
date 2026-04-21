<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function get(string $pattern, string|callable $action, array $options = []): void
    {
        $this->match(['GET'], $pattern, $action, $options);
    }

    public function post(string $pattern, string|callable $action, array $options = []): void
    {
        $this->match(['POST'], $pattern, $action, $options);
    }

    public function match(array $methods, string $pattern, string|callable $action, array $options = []): void
    {
        $this->routes[] = [
            'methods' => array_map('strtoupper', $methods),
            'pattern' => $pattern,
            'regex' => $this->compilePattern($pattern),
            'action' => $action,
            'roles' => $options['roles'] ?? [],
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        $baseUrl = parse_url((string) AppContext::config('app.url', ''), PHP_URL_PATH) ?: '';
        if ($baseUrl !== '' && $baseUrl !== '/' && str_starts_with($path, $baseUrl)) {
            $path = substr($path, strlen($baseUrl)) ?: '/';
        }

        foreach ($this->routes as $route) {
            if (!in_array(strtoupper($method), $route['methods'], true)) {
                continue;
            }

            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }

            $params = array_filter(
                $matches,
                static fn ($key): bool => !is_int($key),
                ARRAY_FILTER_USE_KEY
            );

            $this->authorize($route['roles']);
            $this->invoke($route['action'], $params);
            return;
        }

        http_response_code(404);
        echo View::renderContent('errors/404', ['title' => 'Page Not Found']);
    }

    private function compilePattern(string $pattern): string
    {
        $regex = preg_replace_callback('/\{(\w+)(?::([^}]+))?\}/', static function (array $matches): string {
            $name = $matches[1];
            $rule = $matches[2] ?? '[^/]+';
            return sprintf('(?P<%s>%s)', $name, $rule);
        }, $pattern);

        return '#^' . $regex . '$#';
    }

    private function authorize(array $roles): void
    {
        if ($roles === []) {
            return;
        }

        if (!Auth::check()) {
            Flash::error('Please log in to continue.');
            redirect('/login');
        }

        if (!Auth::hasAnyRole($roles)) {
            http_response_code(403);
            echo View::renderContent('errors/403', ['title' => 'Access denied']);
            exit;
        }
    }

    private function invoke(string|callable $action, array $params): void
    {
        if (is_callable($action)) {
            $action(...array_values($params));
            return;
        }

        [$controller, $method] = explode('@', $action, 2);
        $class = 'App\\Controllers\\' . $controller;
        $instance = new $class();
        $instance->{$method}(...array_values($params));
    }
}


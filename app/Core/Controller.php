<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function render(string $view, array $data = [], string $layout = 'main'): void
    {
        View::render($view, $data, $layout);
    }

    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function redirect(string $path): void
    {
        redirect($path);
    }

    protected function validateCsrf(): void
    {
        Security::validateCsrf($_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
    }

    protected function isPost(): bool
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }
}


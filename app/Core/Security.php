<?php

declare(strict_types=1);

namespace App\Core;

final class Security
{
    public static function bootstrap(): void
    {
        if (!isset($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    public static function csrfToken(): string
    {
        return (string) ($_SESSION['_csrf_token'] ?? '');
    }

    public static function validateCsrf(?string $token): void
    {
        $sessionToken = (string) ($_SESSION['_csrf_token'] ?? '');
        if ($token === null || !hash_equals($sessionToken, $token)) {
            http_response_code(419);
            exit('CSRF validation failed.');
        }
    }
}


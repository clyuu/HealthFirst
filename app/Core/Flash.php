<?php

declare(strict_types=1);

namespace App\Core;

final class Flash
{
    public static function success(string $message): void
    {
        self::push('success', $message);
    }

    public static function error(string $message): void
    {
        self::push('error', $message);
    }

    public static function info(string $message): void
    {
        self::push('info', $message);
    }

    public static function pullAll(): array
    {
        $messages = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $messages;
    }

    private static function push(string $type, string $message): void
    {
        $_SESSION['_flash'][] = compact('type', 'message');
    }
}


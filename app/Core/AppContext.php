<?php

declare(strict_types=1);

namespace App\Core;

final class AppContext
{
    private static array $config = [];
    private static string $basePath = '';

    public static function init(array $config, string $basePath): void
    {
        self::$config = $config;
        self::$basePath = rtrim($basePath, DIRECTORY_SEPARATOR);
    }

    public static function config(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return self::$config;
        }

        $segments = explode('.', $key);
        $value = self::$config;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    public static function basePath(string $path = ''): string
    {
        $clean = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
        return $clean === '' ? self::$basePath : self::$basePath . DIRECTORY_SEPARATOR . $clean;
    }

    public static function storagePath(string $path = ''): string
    {
        return self::basePath('storage' . ($path !== '' ? DIRECTORY_SEPARATOR . $path : ''));
    }

    public static function publicPath(string $path = ''): string
    {
        return self::basePath('public' . ($path !== '' ? DIRECTORY_SEPARATOR . $path : ''));
    }
}


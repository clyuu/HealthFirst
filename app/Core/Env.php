<?php

declare(strict_types=1);

namespace App\Core;

final class Env
{
    public static function load(string $file): void
    {
        if (!is_file($file)) {
            return;
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if (($value[0] ?? '') === '"' && substr($value, -1) === '"') {
                $value = substr($value, 1, -1);
            }

            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
            putenv(sprintf('%s=%s', $name, $value));
        }
    }
}


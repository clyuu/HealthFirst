<?php

declare(strict_types=1);

use App\Core\AppContext;
use App\Core\Flash;
use App\Core\Security;

function env_value(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    return $value === false || $value === null || $value === '' ? $default : $value;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function config_value(string $key, mixed $default = null): mixed
{
    return AppContext::config($key, $default);
}

function base_path(string $path = ''): string
{
    return AppContext::basePath($path);
}

function storage_path(string $path = ''): string
{
    return AppContext::storagePath($path);
}

function public_path(string $path = ''): string
{
    return AppContext::publicPath($path);
}

function url(string $path = ''): string
{
    $base = rtrim((string) AppContext::config('app.url', ''), '/');
    $path = '/' . ltrim($path, '/');
    return $base . ($path === '/' ? '' : $path);
}

function asset(string $path): string
{
    return url('/assets/' . ltrim($path, '/'));
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(Security::csrfToken()) . '">';
}

function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['_old'][$key] ?? $default;
}

function flash_messages(): array
{
    return Flash::pullAll();
}

function remember_old_input(array $input): void
{
    $_SESSION['_old'] = $input;
}

function clear_old_input(): void
{
    unset($_SESSION['_old']);
}

function format_datetime(?string $value): string
{
    if ($value === null || $value === '') {
        return '-';
    }

    return date('Y-m-d H:i', strtotime($value));
}

function incident_tile_class(string $status): string
{
    return match ($status) {
        'verified_unassigned' => 'tile-red',
        'ambulance_assigned', 'en_route_scene' => 'tile-yellow',
        'patient_picked_up', 'en_route_hospital' => 'tile-green',
        default => 'tile-neutral',
    };
}


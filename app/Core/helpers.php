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

function app_base_uri(): string
{
    $configured = (string) AppContext::config('app.url', '');
    $path = (string) (parse_url($configured, PHP_URL_PATH) ?? '');
    $normalized = trim($path, '/');
    return $normalized === '' ? '' : '/' . $normalized;
}

function request_origin(): ?string
{
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? null;
    if ($host === null || $host === '') {
        return null;
    }

    $scheme = 'http';
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $scheme = explode(',', (string) $_SERVER['HTTP_X_FORWARDED_PROTO'])[0];
    } elseif (!empty($_SERVER['REQUEST_SCHEME'])) {
        $scheme = (string) $_SERVER['REQUEST_SCHEME'];
    } elseif (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        $scheme = 'https';
    }

    return $scheme . '://' . $host;
}

function url(string $path = ''): string
{
    $path = '/' . ltrim($path, '/');
    $pathSuffix = $path === '/' ? '' : $path;
    $baseUri = app_base_uri();
    $origin = request_origin();

    if ($origin !== null) {
        return rtrim($origin, '/') . $baseUri . $pathSuffix;
    }

    $base = rtrim((string) AppContext::config('app.url', ''), '/');
    return $base . $pathSuffix;
}

function asset(string $path): string
{
    $relative = ltrim($path, '/');
    $assetUrl = url('/assets/' . $relative);
    $fullPath = public_path('assets/' . $relative);

    if (is_file($fullPath)) {
        return $assetUrl . '?v=' . rawurlencode((string) filemtime($fullPath));
    }

    return $assetUrl;
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

function ui_icon(string $name): string
{
    $icons = [
        'activity' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12h4l2-4 3 8 2-4h7"></path></svg>',
        'ambulance' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 17V7h10l4 4h2v6"></path><path d="M14 7v10"></path><path d="M6 17a2 2 0 1 0 4 0"></path><path d="M16 17a2 2 0 1 0 4 0"></path><path d="M8 10v4M6 12h4"></path></svg>',
        'arrow' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14"></path><path d="M13 6l6 6-6 6"></path></svg>',
        'building' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 21V7h6v14"></path><path d="M14 21V3h6v18"></path><path d="M17 7v6M14 10h6M7 11v6M4 14h6"></path></svg>',
        'calendar' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="3"></rect><path d="M16 3v4M8 3v4M3 10h18"></path></svg>',
        'check' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>',
        'clipboard' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="4" width="14" height="17" rx="2"></rect><path d="M9 4a3 3 0 0 1 6 0"></path><path d="M9 12h6M9 16h4"></path></svg>',
        'doctor' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="7" r="4"></circle><path d="M5 21c1.4-4 3.8-6 7-6s5.6 2 7 6"></path><path d="M12 16v4M10 18h4"></path></svg>',
        'download' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4v10"></path><path d="M8.5 10.5 12 14l3.5-3.5"></path><path d="M5 19h14"></path></svg>',
        'file' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 3h6l5 5v13H8z"></path><path d="M14 3v5h5"></path><path d="M10 13h6M10 17h4"></path></svg>',
        'hospital' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 21V5h16v16"></path><path d="M9 21v-5h6v5"></path><path d="M12 8v5M9.5 10.5h5"></path></svg>',
        'map' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 6 6-3 6 3 6-3v15l-6 3-6-3-6 3z"></path><path d="M9 3v15M15 6v15"></path></svg>',
        'navigation' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 2 8 20-8-4-8 4z"></path><path d="M12 2v16"></path></svg>',
        'patient' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M4 21c1.8-4 5-6 8-6s6.2 2 8 6"></path></svg>',
        'plus' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>',
        'report' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l4 4v14H7z"></path><path d="M14 3v5h4"></path><path d="M9 13h6M9 17h4"></path></svg>',
        'shield' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 5 6v5c0 5 3 8 7 10 4-2 7-5 7-10V6z"></path><path d="m9 12 2 2 4-5"></path></svg>',
        'speed' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 15a8 8 0 1 1 16 0"></path><path d="m12 15 4-5"></path><path d="M8 19h8"></path></svg>',
        'staff' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"></circle><circle cx="17" cy="9" r="2.5"></circle><path d="M3 20c1-4 3-6 6-6s5 2 6 6"></path><path d="M14 15c2.8.2 4.6 1.9 5.5 5"></path></svg>',
        'stethoscope' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 4v5a4 4 0 0 0 8 0V4"></path><path d="M10 13v2a5 5 0 0 0 10 0v-2"></path><circle cx="20" cy="10" r="2"></circle></svg>',
        'timer' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="13" r="8"></circle><path d="M12 13 15 9"></path><path d="M9 2h6"></path></svg>',
        'truck' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h12v10H3z"></path><path d="M15 11h3l3 3v3h-6"></path><circle cx="7" cy="17" r="2"></circle><circle cx="18" cy="17" r="2"></circle></svg>',
        'upload' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V5"></path><path d="m8.5 8.5 3.5-3.5 3.5 3.5"></path><path d="M5 19h14"></path></svg>',
        'user-check' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="4"></circle><path d="M3 21c1.4-4 3.4-6 6-6"></path><path d="m14 17 2 2 5-6"></path></svg>',
        'users' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"></circle><circle cx="17" cy="9" r="2.5"></circle><path d="M3 20c1-4 3-6 6-6s5 2 6 6"></path><path d="M14 15c2.8.2 4.6 1.9 5.5 5"></path></svg>',
    ];

    return $icons[$name] ?? '';
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

function eta_remaining_seconds(int|string|null $etaSeconds, ?string $startedAt = null): int
{
    $remaining = max((int) $etaSeconds, 0);
    if ($remaining === 0 || $startedAt === null || $startedAt === '') {
        return $remaining;
    }

    $startedAtUnix = strtotime($startedAt);
    if ($startedAtUnix === false) {
        return $remaining;
    }

    return max($remaining - max(time() - $startedAtUnix, 0), 0);
}

<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => env_value('APP_NAME', 'HealthFirst'),
        'env' => env_value('APP_ENV', 'local'),
        'debug' => filter_var(env_value('APP_DEBUG', true), FILTER_VALIDATE_BOOL),
        'url' => env_value('APP_URL', 'http://localhost/HealthFirst'),
        'timezone' => env_value('APP_TIMEZONE', 'Asia/Colombo'),
    ],
    'db' => [
        'host' => env_value('DB_HOST', '127.0.0.1'),
        'port' => (int) env_value('DB_PORT', 3306),
        'database' => env_value('DB_NAME', 'healthfirst'),
        'username' => env_value('DB_USER', 'root'),
        'password' => env_value('DB_PASS', ''),
        'charset' => 'utf8mb4',
    ],
    'services' => [
        'google_maps_api_key' => env_value('GOOGLE_MAPS_API_KEY', ''),
        'google_routes_api_key' => env_value('GOOGLE_ROUTES_API_KEY', env_value('GOOGLE_MAPS_API_KEY', '')),
        'ai_service_url' => env_value('AI_SERVICE_URL', 'http://127.0.0.1:5001'),
        'python_bin' => env_value('PYTHON_BIN', 'python'),
    ],
];

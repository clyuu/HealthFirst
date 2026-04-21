<?php

declare(strict_types=1);

use App\Core\AppContext;
use App\Core\Database;
use App\Core\Env;
use App\Core\Security;

define('BASE_PATH', dirname(__DIR__));

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = BASE_PATH . '/app/' . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

require BASE_PATH . '/app/Core/helpers.php';

Env::load(BASE_PATH . '/.env');
$config = require BASE_PATH . '/config/app.php';

date_default_timezone_set($config['app']['timezone']);
error_reporting(E_ALL);
ini_set('display_errors', $config['app']['debug'] ? '1' : '0');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

AppContext::init($config, BASE_PATH);
Database::init($config['db']);
Security::bootstrap();


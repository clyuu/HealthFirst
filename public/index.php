<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\AppContext;
use App\Core\Router;

$router = new Router();
$routeRegistrar = require AppContext::basePath('config/routes.php');
$routeRegistrar($router);
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');


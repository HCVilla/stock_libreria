<?php

declare(strict_types=1);

/**
 * Router del servidor embebido de PHP:
 *   php -S 127.0.0.1:8080 -t public public/index.php
 *
 * Si el archivo solicitado existe (CSS, JS, imágenes), lo sirve directamente.
 */
if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $real = realpath(__DIR__ . $path);

    if (
        $path !== '/'
        && $real !== false
        && is_file($real)
        && str_starts_with(strtolower($real), strtolower(__DIR__))
    ) {
        return false;
    }
}

require dirname(__DIR__) . '/bootstrap.php';

$router = new App\Core\Router();

/** @var callable(App\Core\Router): void $registerRoutes */
$registerRoutes = require BASE_PATH . '/config/routes.php';
$registerRoutes($router);

$router->dispatch();

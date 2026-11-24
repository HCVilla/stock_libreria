<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'Librería Stock'),
    'version' => env('APP_VERSION', '1.0.0'),
    'author' => env('APP_AUTHOR', 'Ingeniero en Sistemas: Villanueva Cesar'),
    'env' => env('APP_ENV', 'local'),
    'debug' => env('APP_DEBUG', 'false') === 'true',
    'timezone' => env('APP_TIMEZONE', 'America/Argentina/Buenos_Aires'),
    'session_lifetime' => (int) env('SESSION_LIFETIME', '7200'),
];

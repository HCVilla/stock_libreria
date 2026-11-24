<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'Librería Stock'),
    'env' => env('APP_ENV', 'local'),
    'debug' => env('APP_DEBUG', 'false') === 'true',
    'timezone' => env('APP_TIMEZONE', 'America/Argentina/Buenos_Aires'),
    'session_lifetime' => (int) env('SESSION_LIFETIME', '7200'),
];

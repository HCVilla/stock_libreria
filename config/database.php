<?php

declare(strict_types=1);

return [
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => (int) env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'libreria_stock'),
    'username' => env('DB_USERNAME', 'stock_app'),
    'password' => env('DB_PASSWORD', ''),
    'admin_user' => env('DB_ADMIN_USER', 'root'),
    'admin_password' => env('DB_ADMIN_PASSWORD', ''),
];

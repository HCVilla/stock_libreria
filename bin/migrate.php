<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Config;

$config = (array) Config::get('database', []);
$database = (string) ($config['database'] ?? 'libreria_stock');
$username = (string) ($config['username'] ?? 'stock_app');
$password = (string) ($config['password'] ?? '');

if (preg_match('/^[A-Za-z0-9_]+$/', $database) !== 1) {
    fwrite(STDERR, "Nombre de base de datos inválido.\n");
    exit(1);
}

if (preg_match('/^[A-Za-z0-9_]+$/', $username) !== 1) {
    fwrite(STDERR, "Usuario de base de datos inválido.\n");
    exit(1);
}

$dsn = sprintf(
    'mysql:host=%s;port=%d;charset=utf8mb4',
    (string) ($config['host'] ?? '127.0.0.1'),
    (int) ($config['port'] ?? 3306)
);

try {
    $pdo = new PDO(
        $dsn,
        (string) ($config['admin_user'] ?? 'root'),
        (string) ($config['admin_password'] ?? ''),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    $schema = file_get_contents(BASE_PATH . '/database/schema.sql');

    if ($schema === false) {
        throw new RuntimeException('No se pudo leer database/schema.sql.');
    }

    $pdo->exec($schema);
    echo "OK  Esquema aplicado en la base '{$database}'.\n";

    $quotedPassword = $pdo->quote($password);

    foreach (['localhost', '127.0.0.1'] as $host) {
        $pdo->exec("CREATE USER IF NOT EXISTS '{$username}'@'{$host}' IDENTIFIED BY {$quotedPassword}");
        $pdo->exec("ALTER USER '{$username}'@'{$host}' IDENTIFIED BY {$quotedPassword}");
        $pdo->exec("GRANT SELECT, INSERT, UPDATE, DELETE, SHOW VIEW ON `{$database}`.* TO '{$username}'@'{$host}'");
    }

    $pdo->exec('FLUSH PRIVILEGES');
    echo "OK  Usuario de aplicación '{$username}' verificado con permisos de aplicación.\n";

    $seeds = glob(BASE_PATH . '/database/seeds/*.sql') ?: [];

    foreach ($seeds as $seed) {
        $sql = file_get_contents($seed);

        if ($sql !== false) {
            $pdo->exec($sql);
            echo 'OK  Seed aplicado: ' . basename($seed) . "\n";
        }
    }

    echo "\nListo. Siguiente paso:\n";
    echo "  php bin/crear_admin.php --usuario=admin --password=\"Admin123!\" --nombre=\"Administrador\"\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'ERROR: ' . $exception->getMessage() . "\n");
    exit(1);
}

<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Database;

$options = [];

foreach (array_slice($argv, 1) as $argument) {
    if (preg_match('/^--([a-z_]+)=(.*)$/i', $argument, $matches) === 1) {
        $options[strtolower($matches[1])] = $matches[2];
    }
}

$usuario = trim((string) ($options['usuario'] ?? 'admin'));
$nombre = trim((string) ($options['nombre'] ?? 'Administrador'));
$rol = trim((string) ($options['rol'] ?? 'admin'));
$password = (string) ($options['password'] ?? '');
$generated = false;

if (!in_array($rol, ['admin', 'encargado', 'vendedor', 'consulta'], true)) {
    fwrite(STDERR, "Rol inválido. Usá admin, encargado, vendedor o consulta.\n");
    exit(1);
}

if ($usuario === '' || mb_strlen($usuario) > 60) {
    fwrite(STDERR, "El usuario es obligatorio y no puede superar 60 caracteres.\n");
    exit(1);
}

if ($nombre === '' || mb_strlen($nombre) > 120) {
    fwrite(STDERR, "El nombre es obligatorio y no puede superar 120 caracteres.\n");
    exit(1);
}

if ($password === '') {
    $password = bin2hex(random_bytes(6));
    $generated = true;
}

if (strlen($password) < 8) {
    fwrite(STDERR, "La contraseña debe tener al menos 8 caracteres.\n");
    exit(1);
}

try {
    $pdo = Database::pdo();
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare(
        'INSERT INTO usuarios (nombre, usuario, password_hash, rol, activo)
         VALUES (?, ?, ?, ?, 1)
         ON DUPLICATE KEY UPDATE
            nombre = VALUES(nombre),
            password_hash = VALUES(password_hash),
            rol = VALUES(rol),
            activo = 1'
    );
    $stmt->execute([$nombre, $usuario, $hash, $rol]);

    echo "OK  Usuario '{$usuario}' creado o actualizado.\n";
    echo "    Nombre: {$nombre}\n";
    echo "    Rol: {$rol}\n";

    if ($generated) {
        echo "    Contraseña generada: {$password}\n";
        echo "    Guardala ahora; no se vuelve a mostrar.\n";
    }

    echo "\nYa podés iniciar sesión en la aplicación.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'ERROR: ' . $exception->getMessage() . "\n");
    exit(1);
}

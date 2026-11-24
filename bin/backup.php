<?php

declare(strict_types=1);

/**
 * Copia de seguridad rápida de la base de datos.
 * Uso: php bin/backup.php
 */

require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Config;

$config = (array) Config::get('database', []);
$backupDir = BASE_PATH . '/storage/backups';

if (!is_dir($backupDir)) {
    mkdir($backupDir, 0775, true);
}

$file = $backupDir . '/libreria_stock_' . date('Y-m-d_His') . '.sql';
$mysqldump = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';

if (!is_file($mysqldump)) {
    fwrite(STDERR, "No se encontró mysqldump en {$mysqldump}.\n");
    exit(1);
}

$command = [
    $mysqldump,
    '--host=' . (string) ($config['host'] ?? '127.0.0.1'),
    '--port=' . (int) ($config['port'] ?? 3306),
    '--user=' . (string) ($config['username'] ?? 'stock_app'),
    '--password=' . (string) ($config['password'] ?? ''),
    '--single-transaction',
    '--default-character-set=utf8mb4',
    '--no-tablespaces',
    (string) ($config['database'] ?? 'libreria_stock'),
];

$descriptors = [
    0 => ['pipe', 'r'],
    1 => ['file', $file, 'w'],
    2 => ['pipe', 'w'],
];

$process = proc_open($command, $descriptors, $pipes);

if (!is_resource($process)) {
    fwrite(STDERR, "No se pudo iniciar mysqldump.\n");
    exit(1);
}

fclose($pipes[0]);
$stderr = stream_get_contents($pipes[2]);
fclose($pipes[2]);
$code = proc_close($process);

if ($code !== 0) {
    @unlink($file);
    fwrite(STDERR, "La copia de seguridad falló con código {$code}.\n{$stderr}");
    exit(1);
}

$size = filesize($file);

if ($size === false || $size < 100) {
    @unlink($file);
    fwrite(STDERR, "El archivo de backup quedó vacío o incompleto.\n");
    exit(1);
}

echo "Backup creado: {$file} (" . number_format($size / 1024, 1, ',', '.') . " KB)\n";

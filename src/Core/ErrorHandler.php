<?php

declare(strict_types=1);

namespace App\Core;

final class ErrorHandler
{
    public static function register(bool $debug): void
    {
        set_exception_handler(static function (\Throwable $e) use ($debug): void {
            $logDir = BASE_PATH . '/storage/logs';

            if (!is_dir($logDir)) {
                @mkdir($logDir, 0775, true);
            }

            $line = sprintf(
                "[%s] %s: %s in %s:%d\n%s\n\n",
                date('Y-m-d H:i:s'),
                $e::class,
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getTraceAsString()
            );

            @file_put_contents($logDir . '/app.log', $line, FILE_APPEND);

            if (!headers_sent()) {
                http_response_code(500);
            }

            if ($debug) {
                echo '<!doctype html><html lang="es"><head><meta charset="utf-8">';
                echo '<title>Error del sistema</title></head>';
                echo '<body style="font-family:Segoe UI,sans-serif;padding:2rem;background:#0f172a;color:#e2e8f0">';
                echo '<h1>Error 500</h1>';
                echo '<p><strong>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</strong></p>';
                echo '<p>' . htmlspecialchars($e->getFile() . ':' . $e->getLine(), ENT_QUOTES, 'UTF-8') . '</p>';
                echo '<pre style="overflow:auto;background:#1e293b;padding:1rem;border-radius:8px">';
                echo htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8');
                echo '</pre></body></html>';
            } else {
                echo 'Ocurrió un error interno. Intentá nuevamente en unos minutos.';
            }
        });
    }
}

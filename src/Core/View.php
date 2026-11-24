<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $view, array $data = [], string $layout = 'app'): void
    {
        $content = self::partial($view, $data);
        $title = (string) ($data['title'] ?? Config::get('app.name', 'Librería Stock'));

        extract($data, EXTR_SKIP);

        ob_start();
        require BASE_PATH . '/resources/views/layouts/' . $layout . '.php';
        echo (string) ob_get_clean();
    }

    public static function partial(string $view, array $data = []): string
    {
        $file = BASE_PATH . '/resources/views/' . str_replace('.', '/', $view) . '.php';

        if (!is_file($file)) {
            throw new \RuntimeException('Vista no encontrada: ' . $view);
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $file;

        return (string) ob_get_clean();
    }
}

<?php

declare(strict_types=1);

namespace App\Core;

final class Url
{
    public static function base(): string
    {
        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $base = preg_replace('#/index\.php$#', '', $script) ?? '';

        if ($base === '/' || $base === '.') {
            $base = '';
        }

        return rtrim($base, '/');
    }

    public static function to(string $path = ''): string
    {
        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }

        return self::base() . '/' . ltrim($path, '/');
    }
}

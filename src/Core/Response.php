<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function redirect(string $path): never
    {
        header('Location: ' . Url::to($path));
        exit;
    }

    public static function back(string $fallback = '/'): never
    {
        $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');

        if ($referer !== '' && preg_match('#^https?://#i', $referer) === 1) {
            header('Location: ' . $referer);
            exit;
        }

        self::redirect($fallback);
    }

    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function abort(int $code, string $message): never
    {
        http_response_code($code);

        if (Request::wantsJson()) {
            self::json(['error' => $message], $code);
        }

        View::render('errors.error', [
            'code' => $code,
            'message' => $message,
            'title' => 'Error ' . $code,
        ], 'auth');

        exit;
    }
}

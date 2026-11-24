<?php

declare(strict_types=1);

namespace App\Core;

use App\Infrastructure\Persistence\UsuarioRepository;

final class Auth
{
    private static ?array $user = null;
    private static bool $loaded = false;

    public static function attempt(string $usuario, string $password): bool
    {
        $repository = new UsuarioRepository();
        $user = $repository->findByUsuario($usuario);

        if ($user === null || (int) $user['activo'] !== 1) {
            return false;
        }

        if (!password_verify($password, (string) $user['password_hash'])) {
            return false;
        }

        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
            $repository->updatePassword((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
        }

        Session::regenerate();
        Session::put('auth_id', (int) $user['id']);
        Session::put('last_activity', time());
        $repository->touchUltimoAcceso((int) $user['id']);

        self::$user = $user;
        self::$loaded = true;

        return true;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function user(): ?array
    {
        if (self::$loaded) {
            return self::$user;
        }

        self::$loaded = true;
        $id = (int) Session::get('auth_id', 0);

        if ($id <= 0) {
            return self::$user = null;
        }

        $user = (new UsuarioRepository())->findById($id);

        if ($user === null || (int) $user['activo'] !== 1) {
            self::logout();

            return self::$user = null;
        }

        return self::$user = $user;
    }

    public static function id(): int
    {
        return (int) (self::user()['id'] ?? 0);
    }

    public static function role(): string
    {
        return (string) (self::user()['rol'] ?? '');
    }

    public static function logout(): void
    {
        Session::forget('auth_id');
        Session::forget('last_activity');
        Session::regenerate();

        self::$user = null;
        self::$loaded = true;
    }
}

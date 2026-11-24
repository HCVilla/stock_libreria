<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;

final class AuthController
{
    public function showLogin(): void
    {
        View::render('auth.login', [
            'title' => 'Iniciar sesión',
        ], 'auth');
    }

    public function login(): void
    {
        $usuario = trim((string) Request::input('usuario', ''));
        $password = (string) Request::input('password', '');

        if ($usuario === '' || $password === '') {
            View::render('auth.login', [
                'title' => 'Iniciar sesión',
                'error' => 'Completá tu usuario y contraseña.',
                'old' => ['usuario' => $usuario],
            ], 'auth');

            return;
        }

        if (!Auth::attempt($usuario, $password)) {
            View::render('auth.login', [
                'title' => 'Iniciar sesión',
                'error' => 'Usuario o contraseña incorrectos.',
                'old' => ['usuario' => $usuario],
            ], 'auth');

            return;
        }

        $user = Auth::user();
        Session::flash('success', 'Bienvenido/a, ' . ($user['nombre'] ?? $usuario) . '.');

        Response::redirect('/');
    }

    public function logout(): void
    {
        Auth::logout();
        Session::flash('info', 'Cerraste sesión correctamente.');

        Response::redirect('/login');
    }
}

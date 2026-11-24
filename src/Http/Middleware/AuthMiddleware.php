<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

final class AuthMiddleware
{
    public function handle(): bool
    {
        if (!Auth::check()) {
            if (Request::wantsJson()) {
                Response::json(['error' => 'Sesión no iniciada.'], 401);
            }

            Session::flash('warning', 'Iniciá sesión para continuar.');

            Response::redirect('/login');
        }

        $lifetime = (int) Config::get('app.session_lifetime', 7200);
        $lastActivity = (int) Session::get('last_activity', time());

        if ($lifetime > 0 && (time() - $lastActivity) > $lifetime) {
            Auth::logout();

            if (Request::wantsJson()) {
                Response::json(['error' => 'Sesión expirada.'], 401);
            }

            Session::flash('warning', 'Tu sesión expiró. Volvé a ingresar.');

            Response::redirect('/login');
        }

        Session::put('last_activity', time());

        return true;
    }
}

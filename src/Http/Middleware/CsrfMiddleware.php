<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;

final class CsrfMiddleware
{
    public function handle(): bool
    {
        $token = Request::input('_token');

        if (!Csrf::verify(is_string($token) ? $token : null)) {
            Response::abort(419, 'El formulario expiró o el token de seguridad no es válido. Recargá la página e intentá nuevamente.');
        }

        return true;
    }
}

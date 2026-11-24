<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;

final class RoleMiddleware
{
    /**
     * @param array<int, string> $roles
     */
    public function __construct(private readonly array $roles)
    {
    }

    public function handle(): bool
    {
        if (!in_array(Auth::role(), $this->roles, true)) {
            if (Request::wantsJson()) {
                Response::json(['error' => 'No tenés permiso para realizar esta acción.'], 403);
            }

            Response::abort(403, 'No tenés permiso para acceder a esta sección.');
        }

        return true;
    }
}

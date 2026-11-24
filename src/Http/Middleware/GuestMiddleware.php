<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Auth;
use App\Core\Response;

final class GuestMiddleware
{
    public function handle(): bool
    {
        if (Auth::check()) {
            Response::redirect('/');
        }

        return true;
    }
}

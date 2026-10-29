<?php
declare(strict_types=1);

namespace Mokko\Http\Middleware;

use Mokko\Http\Request;
use Mokko\Http\Response;
use Mokko\Security\Auth;

final class RequireGuest implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        if (Auth::check()) {
            return Response::redirect(BASE_URL . (Auth::isAdmin() ? '/panel' : '/crm'));
        }

        return $next($request);
    }
}

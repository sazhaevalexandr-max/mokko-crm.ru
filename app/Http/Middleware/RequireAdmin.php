<?php
declare(strict_types=1);

namespace Mokko\Http\Middleware;

use Mokko\Http\Request;
use Mokko\Http\Response;
use Mokko\Security\Auth;

final class RequireAdmin implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        if (Auth::isAdmin()) {
            return $next($request);
        }

        if (Auth::check()) {
            return Response::redirect(BASE_URL . '/crm');
        }

        return Response::redirect(BASE_URL . '/login');
    }
}

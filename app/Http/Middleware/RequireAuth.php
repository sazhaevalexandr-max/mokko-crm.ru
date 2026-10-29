<?php
declare(strict_types=1);

namespace Mokko\Http\Middleware;

use Mokko\Http\Request;
use Mokko\Http\Response;
use Mokko\Security\Auth;

final class RequireAuth implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        if (!Auth::check()) {
            return Response::redirect(BASE_URL . '/login');
        }

        return $next($request);
    }
}

<?php
declare(strict_types=1);

namespace Mokko\Controllers;

use Mokko\Http\Request;
use Mokko\Http\Response;
use Mokko\Security\Auth;

final class AuthController
{
    public function logout(Request $request): Response
    {
        Auth::logout();

        return Response::redirect(BASE_URL . '/login');
    }
}

<?php
declare(strict_types=1);

namespace Mokko\Controllers;

use Mokko\Http\Request;
use Mokko\Http\Response;
use Mokko\Repositories\PlaceRepository;
use Mokko\Security\Auth;
use Mokko\Support\View;

/**
 * Страницы приложения. Проверки доступа — в middleware, здесь только
 * сценарий страницы и рендер шаблона.
 */
final class PageController
{
    public function home(Request $request): Response
    {
        return View::response('main');
    }

    public function subscribe(Request $request): Response
    {
        return View::response('subscribe');
    }

    public function panel(Request $request): Response
    {
        return View::response('panel');
    }

    public function login(Request $request): Response
    {
        return View::response('login');
    }

    public function signup(Request $request): Response
    {
        // Коробочная версия: регистрация отключена.
        return Response::redirect(BASE_URL . '/login');
    }

    public function password(Request $request): Response
    {
        return View::response('password');
    }

    public function crm(Request $request): Response
    {
        if (empty($_SESSION['place'])) {
            return Response::redirect(BASE_URL . '/enter');
        }

        return View::response('crm');
    }

    public function enter(Request $request): Response
    {
        $place = $request->query('place');

        if ($place !== null && $place !== '' && is_scalar($place)) {
            $placeId = (int)$place;
            if ((new PlaceRepository())->belongsToUser($placeId, Auth::id())) {
                $_SESSION['place'] = $placeId;
            }

            return Response::redirect(BASE_URL . (!empty($_SESSION['place']) ? '/crm' : '/enter'));
        }

        return View::response('select_place');
    }
}

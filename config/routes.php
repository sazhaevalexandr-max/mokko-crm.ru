<?php
declare(strict_types=1);

/**
 * Таблица маршрутов.
 *
 * Ключ — значение ?view=..., которое приходит из rewrite-правил .htaccess
 * (/login, /panel, /crm...). Пустая строка — главная.
 *
 * Раздел /updates намеренно не описан здесь: модуль обновлений остаётся
 * самостоятельным и подключается в index.php отдельной веткой.
 */

use Mokko\Controllers\PageController;
use Mokko\Http\Middleware\RequireAdmin;
use Mokko\Http\Middleware\RequireAuth;
use Mokko\Http\Middleware\RequireGuest;

return array(
    ''            => array('handler' => array(PageController::class, 'home')),

    'subscribe'   => array('handler' => array(PageController::class, 'subscribe')),

    'panel'       => array(
        'handler'    => array(PageController::class, 'panel'),
        'middleware' => array(RequireAdmin::class),
    ),

    'login'       => array(
        'handler'    => array(PageController::class, 'login'),
        'middleware' => array(RequireGuest::class),
    ),

    'signup'      => array('handler' => array(PageController::class, 'signup')),

    'password'    => array(
        'handler'    => array(PageController::class, 'password'),
        'middleware' => array(RequireGuest::class),
    ),

    'crm'         => array(
        'handler'    => array(PageController::class, 'crm'),
        'middleware' => array(RequireAuth::class),
    ),

    'enter'       => array(
        'handler'    => array(PageController::class, 'enter'),
        'middleware' => array(RequireAuth::class),
    ),
);

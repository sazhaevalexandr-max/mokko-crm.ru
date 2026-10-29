<?php
declare(strict_types=1);

namespace Mokko\Api;

use Mokko\Api\Controllers\CatalogController;
use Mokko\Api\Controllers\InitController;
use Mokko\Api\Controllers\ShiftController;
use Mokko\Api\Controllers\StaffController;
use Mokko\Api\Controllers\StatController;
use Mokko\Api\Controllers\VenueController;

/**
 * Таблица module => [контроллер, метод].
 */
final class ApiRouter
{
    /** @var array */
    private const ROUTES = array(
        'ini'        => array(InitController::class, 'ini'),
        'places'     => array(VenueController::class, 'places'),
        'lager'      => array(VenueController::class, 'lager'),
        'guests'     => array(VenueController::class, 'guests'),
        'category'   => array(CatalogController::class, 'category'),
        'items'      => array(CatalogController::class, 'items'),
        'item'       => array(CatalogController::class, 'item'),
        'staff'      => array(StaffController::class, 'staff'),
        'user'       => array(StaffController::class, 'user'),
        'startshift' => array(ShiftController::class, 'startShift'),
        'shift'      => array(ShiftController::class, 'shift'),
        'buy'        => array(ShiftController::class, 'buy'),
        'stat'       => array(StatController::class, 'stat'),
    );

    public function dispatch(ApiContext $context): void
    {
        $route = self::ROUTES[$context->module] ?? null;
        if ($route === null) {
            \mokko_json(array('error' => 'unknown module'));
        }

        $class = $route[0];
        $method = $route[1];

        $controller = new $class($context);
        $controller->$method();
    }
}

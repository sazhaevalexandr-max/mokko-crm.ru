<?php
declare(strict_types=1);

namespace Mokko\Http;

use Mokko\Http\Middleware\Middleware;
use RuntimeException;

/**
 * Роутер без зависимостей.
 *
 * Формат маршрута:
 *   'panel' => array(
 *       'handler'    => array(PageController::class, 'panel'),
 *       'middleware' => array(RequireAdmin::class),
 *   )
 *
 * Middleware выполняются в порядке объявления.
 */
final class Router
{
    /** @var array */
    private $routes;

    /** @var callable|null */
    private $fallback;

    public function __construct(array $routes, ?callable $fallback = null)
    {
        $this->routes = $routes;
        $this->fallback = $fallback;
    }

    public function dispatch(Request $request): Response
    {
        $route = $this->routes[$request->route()] ?? null;

        if ($route === null) {
            if ($this->fallback === null) {
                return Response::html('Not Found', 404);
            }

            return $this->normalize(call_user_func($this->fallback, $request));
        }

        $handler = $this->makeHandler($route['handler'] ?? null);
        $middleware = isset($route['middleware']) && is_array($route['middleware'])
            ? $route['middleware']
            : array();

        foreach (array_reverse($middleware) as $class) {
            $next = $handler;
            $handler = static function (Request $req) use ($class, $next): Response {
                $instance = new $class();
                if (!$instance instanceof Middleware) {
                    throw new RuntimeException('Middleware must implement ' . Middleware::class . ': ' . $class);
                }
                return $instance->handle($req, $next);
            };
        }

        return $this->normalize(call_user_func($handler, $request));
    }

    /**
     * @param mixed $handler
     */
    private function makeHandler($handler): callable
    {
        if (is_array($handler) && count($handler) === 2 && is_string($handler[0])) {
            $class = $handler[0];
            $method = $handler[1];

            return static function (Request $request) use ($class, $method): Response {
                $controller = new $class();
                return $controller->$method($request);
            };
        }

        if (is_callable($handler)) {
            return static function (Request $request) use ($handler): Response {
                return call_user_func($handler, $request);
            };
        }

        throw new RuntimeException('Invalid route handler.');
    }

    /**
     * @param mixed $result
     */
    private function normalize($result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        return Response::html((string)$result);
    }
}

<?php
declare(strict_types=1);

/**
 * Минимальные проверки нового слоя без PHPUnit.
 *
 * Запуск: C:\xampp\php\php.exe tests\run.php
 */

require dirname(__DIR__) . '/app/autoload.php';

use Mokko\Api\CrmSupport;
use Mokko\Http\Middleware\Middleware;
use Mokko\Http\Request;
use Mokko\Http\Response;
use Mokko\Http\Router;
use Mokko\Support\View;

if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost');
}

$passed = 0;
$failed = 0;

function check(string $name, bool $condition): void
{
    global $passed, $failed;

    if ($condition) {
        $passed++;
        echo "  ok  " . $name . PHP_EOL;
        return;
    }

    $failed++;
    echo "  FAIL " . $name . PHP_EOL;
}

final class BlockMiddleware implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        return Response::html('blocked');
    }
}

echo "Router" . PHP_EOL;

$router = new Router(array(
    'hello' => array(
        'handler' => static function (Request $request): Response {
            return Response::html('hello');
        },
    ),
    'blocked' => array(
        'handler' => static function (Request $request): Response {
            return Response::html('unreachable');
        },
        'middleware' => array(BlockMiddleware::class),
    ),
));

check('exact route', $router->dispatch(Request::create('hello'))->body() === 'hello');
check('middleware short-circuit', $router->dispatch(Request::create('blocked'))->body() === 'blocked');
check('fallback', (new Router(array(), static function (): Response {
    return Response::html('home');
}))->dispatch(Request::create('unknown'))->body() === 'home');

echo PHP_EOL . "Request" . PHP_EOL;

$request = Request::create('enter', array('place' => '7'));
check('route', $request->route() === 'enter');
check('query', $request->query('place') === '7');
check('query default', $request->query('missing', 'x') === 'x');

echo PHP_EOL . "View" . PHP_EOL;

$blocked = false;
try {
    View::render('updates');
} catch (InvalidArgumentException $e) {
    $blocked = true;
}
check('updates not renderable through View', $blocked);

echo PHP_EOL . "CrmSupport" . PHP_EOL;

check('valid date', CrmSupport::date('2026-10-28', 'x') === '2026-10-28');
check('invalid date fallback', CrmSupport::date('2026-02-31', 'x') === 'x');
check('phone clean', CrmSupport::phoneClean('+7 (999) 123-45-67') === '+79991234567');
check('requisites valid', CrmSupport::requisitesError('', '', '') === '');
check('requisites invalid inn', CrmSupport::requisitesError('123', '', '') === 'invalid inn');

echo PHP_EOL;
echo 'Passed: ' . $passed . ', failed: ' . $failed . PHP_EOL;

exit($failed === 0 ? 0 : 1);

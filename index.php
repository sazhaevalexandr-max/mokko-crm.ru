<?php
/**
 * MOKKO CRM — единая точка входа.
 *
 * Bootstrapping (сессия, заголовки, установка, обслуживание) остаётся здесь,
 * а маршрутизация и страницы вынесены в app/ и config/routes.php.
 *
 * Раздел /updates намеренно не проходит через новый роутер: модуль обновлений
 * должен оставаться самостоятельным и не зависеть от app/.
 */

use Mokko\Controllers\AuthController;
use Mokko\Controllers\PageController;
use Mokko\Http\Request;
use Mokko\Http\Router;

define('MOKKO', true);

if (is_file(__DIR__ . '/config/version.php')) {
    require_once __DIR__ . '/config/version.php';
}

require_once __DIR__ . '/config/security.php';
mokko_harden_errors();
mokko_session_start();
mokko_send_security_headers();

$base = rtrim(str_replace('\\', '/', dirname(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/')), '/');
define('BASE_PATH', $base);
define('BASE_URL', (mokko_is_https() ? 'https' : 'http') . '://' . mokko_host() . $base);
unset($base);

// Система ещё не установлена — ведём в установщик.
if (!mokko_is_installed()) {
    header('Location: ' . BASE_URL . '/setup/install.php');
    exit;
}

// Режим обслуживания: администратору сайт доступен, остальные видят 503.
if (mokko_in_maintenance() && !mokko_is_admin_session()) {
    http_response_code(503);
    header('Retry-After: 120');
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="ru"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<meta name="robots" content="noindex"><title>Обслуживание — MOKKO CRM</title>'
        . '<style>body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;'
        . 'background:#0f0d0b;color:#f2ece4;font:16px/1.6 Manrope,-apple-system,Segoe UI,Roboto,Arial,sans-serif}'
        . '.b{max-width:460px;padding:32px;text-align:center}.b h1{font-size:24px;margin:0 0 10px}'
        . '.b p{color:#bdb2a6;margin:0}.b span{display:inline-block;margin-top:18px;color:#d9a46a;font-size:13px}'
        . '</style></head><body><div class="b"><h1>Идёт обновление</h1>'
        . '<p>Мы ненадолго закрыли доступ, чтобы обновить систему. Загляните через минуту.</p>'
        . '<span>MOKKO CRM</span></div></body></html>';
    exit;
}

// Модули подключаются после проверки установки: их bootstrap может работать с БД.
require_once __DIR__ . '/lib/modules.php';
mokko_modules_boot();

$view = isset($_GET['view']) && is_string($_GET['view']) ? $_GET['view'] : '';

// ── Модуль обновлений: отдельная ветка, не связанная с app/ ────────────────
if ($view === 'updates') {
    if (mokko_is_admin_session()) {
        require __DIR__ . '/lib/updates.php';
    } else {
        header('Location: ' . BASE_URL . '/login');
    }
    exit;
}

require_once __DIR__ . '/app/autoload.php';

$request = Request::fromGlobals();

// ── Выход ──────────────────────────────────────────────────────────────────
if (isset($_GET['do']) && (string)$_GET['do'] === 'exit') {
    (new AuthController())->logout($request)->send();
    exit;
}

// ── Страницы ───────────────────────────────────────────────────────────────
$routes = require __DIR__ . '/config/routes.php';
$router = new Router($routes, array(new PageController(), 'home'));
$router->dispatch($request)->send();

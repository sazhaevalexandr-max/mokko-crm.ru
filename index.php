<?php
/**
 * MOKKO CRM — единая точка входа.
 *
 * Безопасность:
 *  - сессия стартует только с безопасными атрибутами cookie;
 *  - никакого «входа» через GET/POST-параметры (удалён старый баг
 *    ?access=...&uid=...&sid=..., позволявший войти под любым uid);
 *  - подключаемые шаблоны — только из белого списка;
 *  - ошибки не показываются посетителю.
 */

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

// Система ещё не установлена (нет базы, таблиц или пользователей) —
// не показываем интерфейс с ошибками БД, а сразу ведём в установщик.
if (!mokko_is_installed()) {
    header('Location: ' . BASE_URL . '/setup/install.php');
    exit;
}

// Режим обслуживания на время обновления: администратору сайт доступен,
// остальные видят 503 вместо полуобновлённого интерфейса.
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

/**
 * Нормализация пользовательской строки.
 * ВАЖНО: для SQL всегда используются prepared statements, эта функция
 * больше не «защищает» от инъекций (addslashes был вредным).
 */
function clean($val)
{
    return mokko_str($val, 255);
}

/**
 * Рендер шаблона из lib/ по белому списку.
 */
function mokko_render($view)
{
    static $allowed = array(
        'subscribe', 'panel', 'login', 'signup',
        'password', 'crm', 'enter', 'select_place',
    );
    if (!in_array($view, $allowed, true)) {
        return;
    }
    require __DIR__ . '/lib/' . $view . '.php';
}

$view = isset($_GET['view']) && is_string($_GET['view']) ? $_GET['view'] : '';

if ($view !== '') {
    switch ($view) {
        case 'subscribe':
            mokko_render('subscribe');
            break;

        case 'panel':
            if (mokko_is_authenticated() && (isset($_SESSION['sid']) && $_SESSION['sid'] === 'uid')) {
                mokko_render('panel');
            } elseif (mokko_is_authenticated()) {
                header('Location: ' . BASE_URL . '/crm');
                exit;
            } else {
                header('Location: ' . BASE_URL . '/login');
                exit;
            }
            break;

        case 'updates':
            if (mokko_is_admin_session()) {
                require __DIR__ . '/lib/updates.php';
            } else {
                header('Location: ' . BASE_URL . '/login');
            }
            break;

        case 'login':
            if (mokko_is_authenticated()) {
                header('Location: ' . BASE_URL . '/panel');
                exit;
            }
            mokko_render($view);
            break;

        // Коробочная версия: регистрация отключена — администратор создаётся
        // установщиком (один на копию). Страница lib/signup.php сохранена, но
        // не открывается: заход на /signup перенаправляется на вход. Чтобы
        // вернуть регистрацию — верните 'signup' в case 'login' выше,
        // раскомментируйте ссылку на странице входа и гейт в api/auth.php.
        case 'signup':
            header('Location: ' . BASE_URL . '/login');
            exit;
            break;

        case 'password':
            if (mokko_is_authenticated()) {
                header('Location: ' . BASE_URL . '/panel');
                exit;
            }
            mokko_render('password');
            break;

        case 'crm':
            if (mokko_is_authenticated() && isset($_SESSION['place'])) {
                mokko_render('crm');
            } else {
                header('Location: ' . BASE_URL . '/enter');
                exit;
            }
            break;

        case 'enter':
            if (!mokko_is_authenticated()) {
                header('Location: ' . BASE_URL . '/login');
                exit;
            }
            if (isset($_GET['place'])) {
                $place = (int)$_GET['place'];
                require_once __DIR__ . '/config/db.php';
                // Кофейня должна принадлежать текущему пользователю: иначе
                // ?place=1 переключал интерфейс на чужую точку.
                $chk = getDB()->prepare('SELECT id FROM places WHERE id = ? AND user_id = ? LIMIT 1');
                $chk->execute(array($place, (int)$_SESSION['uid']));
                if ($chk->fetchColumn()) {
                    $_SESSION['place'] = $place;
                }
                header('Location: ' . BASE_URL . (!empty($_SESSION['place']) ? '/crm' : '/enter'));
                exit;
            }
            mokko_render('select_place');
            break;

        default:
            require __DIR__ . '/lib/main.php';
    }
} else {
    require __DIR__ . '/lib/main.php';
}

// ── Действия ────────────────────────────────────────────────────────────────
if (isset($_GET['do'])) {
    switch ((string)$_GET['do']) {
        case 'exit':
            $_SESSION = array();
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    isset($params['path']) ? $params['path'] : '/',
                    isset($params['domain']) ? $params['domain'] : '',
                    !empty($params['secure']),
                    !empty($params['httponly'])
                );
            }
            session_destroy();
            header('Location: ' . BASE_URL . '/login');
            exit;
    }
}

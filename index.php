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

        case 'login':
        case 'signup':
            if (mokko_is_authenticated()) {
                header('Location: ' . BASE_URL . '/panel');
                exit;
            }
            mokko_render($view);
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

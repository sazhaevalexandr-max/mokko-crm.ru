<?php
/**
 * API раздела «Обновления»: загрузка пакета, установка, откат, модули.
 *
 * Доступ только администратору (role=admin) с CSRF-токеном.
 * Загрузка идёт multipart-запросом, остальные действия — JSON.
 */

require_once dirname(__DIR__) . '/config/security.php';
mokko_harden_errors();
mokko_session_start();
mokko_send_security_headers();

require_once dirname(__DIR__) . '/config/db.php';
if (is_file(dirname(__DIR__) . '/config/version.php')) {
    require_once dirname(__DIR__) . '/config/version.php';
}
require_once dirname(__DIR__) . '/lib/modules.php';
require_once dirname(__DIR__) . '/lib/updater.php';

@set_time_limit(300);

function mokko_upd_api_json($payload, $status = 200)
{
    $payload = is_array($payload) ? $payload : array('ok' => false, 'message' => 'Ошибка.');
    if (!isset($payload['ok'])) {
        $payload['ok'] = $status < 400;
    }
    mokko_json($payload, $status);
}

function mokko_upd_api_error($message, $status = 400)
{
    mokko_upd_api_json(array('ok' => false, 'message' => (string)$message), $status);
}

$uid = mokko_require_auth();

try {
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT id, role, password_hash FROM users WHERE id = ? LIMIT 1');
    $stmt->execute(array($uid));
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    mokko_log('updates: db error: ' . $e->getMessage());
    mokko_upd_api_error('Нет связи с базой данных.', 500);
}

if (!$user || $user['role'] !== 'admin' || !mokko_is_admin_session()) {
    mokko_upd_api_error('Доступ только для администратора.', 403);
}

// Сессия привязана к паролю: после смены пароля старые cookie не могут
// загрузить пакет (иначе кража cookie = выполнение кода).
$pv = isset($_SESSION['pv']) ? (string)$_SESSION['pv'] : '';
if ($pv === '' || !hash_equals(mokko_password_fingerprint($user['password_hash']), $pv)) {
    $_SESSION = array();
    mokko_upd_api_error('Сессия устарела. Войдите заново.', 401);
}

if (is_file(MOKKO_ROOT . '/config/updates.disabled')) {
    mokko_upd_api_error('Обновления отключены администратором (config/updates.disabled).', 403);
}

mokko_require_post();
mokko_require_same_origin();

$action = isset($_GET['action']) ? (string)$_GET['action'] : '';

/**
 * Публичное описание пакета для интерфейса (без полного списка файлов).
 */
function mokko_upd_public_summary($meta)
{
    $m = $meta['manifest'];
    $files = array();
    foreach (array_slice($meta['files'], 0, 40) as $file) {
        $files[] = array('path' => $file['path'], 'size' => $file['size']);
    }

    return array(
        'token'       => $meta['token'],
        'original'    => $meta['original'],
        'size'        => $meta['size'],
        'total'       => $meta['total'],
        'warnings'    => $meta['warnings'],
        'signature'   => $meta['signature'],
        'manifest'    => array(
            'id'          => $m['id'],
            'title'       => $m['title'],
            'version'     => $m['version'],
            'type'        => $m['type'],
            'module'      => $m['module'],
            'requires'    => $m['requires'],
            'description' => $m['description'],
            'has_sql'     => $m['sql'] !== '',
            'delete'      => count($m['delete']),
        ),
        'files'       => $files,
        'files_total' => count($meta['files']),
    );
}

function mokko_upd_state_payload()
{
    $state = mokko_upd_ui_state();
    $state['ok'] = true;
    return $state;
}

try {
    switch ($action) {
        case 'inspect':
            $info = mokko_rate_limit_info('updates_upload', 12, 3600);
            if (!$info['allowed']) {
                header('Retry-After: ' . max(1, (int)$info['retry']));
                mokko_upd_api_error('Слишком много загрузок. Попробуйте позже.', 429);
            }

            if (!mokko_csrf_valid(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
                mokko_upd_api_error('Сессия устарела. Обновите страницу и попробуйте снова.', 403);
            }

            if (empty($_FILES['package'])) {
                $length = isset($_SERVER['CONTENT_LENGTH']) ? (int)$_SERVER['CONTENT_LENGTH'] : 0;
                if ($length > 0 && empty($_POST) && empty($_FILES)) {
                    mokko_upd_api_error(
                        'Файл больше лимита PHP (' . round(mokko_upd_max_upload() / 1048576, 1) . ' МБ). '
                        . 'Увеличьте upload_max_filesize и post_max_size.',
                        413
                    );
                }
                mokko_upd_api_error('Файл не выбран.', 400);
            }

            $file = $_FILES['package'];
            if (!empty($file['error'])) {
                $messages = array(
                    UPLOAD_ERR_INI_SIZE   => 'Файл больше upload_max_filesize.',
                    UPLOAD_ERR_FORM_SIZE  => 'Файл больше допустимого размера.',
                    UPLOAD_ERR_PARTIAL    => 'Файл загрузился не полностью.',
                    UPLOAD_ERR_NO_FILE    => 'Файл не выбран.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Нет временного каталога для загрузки.',
                    UPLOAD_ERR_CANT_WRITE => 'Не удалось записать файл на диск.',
                    UPLOAD_ERR_EXTENSION  => 'Загрузка остановлена расширением PHP.',
                );
                mokko_upd_api_error(isset($messages[$file['error']]) ? $messages[$file['error']] : 'Ошибка загрузки файла.', 400);
            }

            if (!is_uploaded_file($file['tmp_name'])) {
                mokko_upd_api_error('Некорректная загрузка файла.', 400);
            }

            $max = mokko_upd_max_upload();
            if ((int)$file['size'] > $max) {
                mokko_upd_api_error('Архив больше ' . round($max / 1048576, 1) . ' МБ.', 413);
            }

            try {
                $meta = mokko_upd_save_upload($file['tmp_name'], $file['name']);
            } catch (MokkoUpdateException $e) {
                mokko_upd_api_error($e->getMessage(), 422);
            }

            mokko_log('updates: package inspected ' . $meta['manifest']['id'] . ' by uid=' . $uid);
            mokko_upd_api_json(array('ok' => true, 'package' => mokko_upd_public_summary($meta)));
            break;

        case 'apply':
            mokko_require_json();
            mokko_require_csrf();
            mokko_rate_limit_guard('updates_apply', 8, 3600);

            $in = mokko_json_input();
            $token = isset($in['token']) ? (string)$in['token'] : '';

            $opts = array(
                'maintenance'     => !empty($in['maintenance']),
                'db_backup'       => !empty($in['db_backup']),
                'allow_reinstall' => !empty($in['allow_reinstall']),
                'force_db'        => !empty($in['force_db']),
            );

            try {
                $result = mokko_upd_apply($token, $opts);
            } catch (MokkoUpdateException $e) {
                mokko_log('updates: apply failed: ' . $e->getMessage());
                mokko_upd_api_error($e->getMessage(), 409);
            }

            mokko_log('updates: applied ' . $result['title'] . ' ' . $result['version'] . ' by uid=' . $uid);
            mokko_upd_api_json(array('ok' => true, 'result' => $result, 'state' => mokko_upd_state_payload()));
            break;

        case 'rollback':
            mokko_require_json();
            mokko_require_csrf();
            mokko_rate_limit_guard('updates_rollback', 8, 3600);

            $in = mokko_json_input();
            $token = isset($in['token']) ? (string)$in['token'] : '';

            try {
                $result = mokko_upd_rollback($token, !empty($in['restore_db']), !empty($in['force']));
            } catch (MokkoUpdateException $e) {
                mokko_upd_api_error($e->getMessage(), 409);
            }

            mokko_log('updates: rollback ' . $token . ' by uid=' . $uid);
            mokko_upd_api_json(array('ok' => true, 'result' => $result, 'state' => mokko_upd_state_payload()));
            break;

        case 'module_state':
            mokko_require_json();
            mokko_require_csrf();

            $in = mokko_json_input();
            $slug = isset($in['slug']) ? (string)$in['slug'] : '';
            $enabled = !empty($in['enabled']);

            try {
                mokko_module_set_enabled($slug, $enabled);
            } catch (Throwable $e) {
                mokko_upd_api_error($e->getMessage(), 400);
            }

            mokko_log('updates: module ' . $slug . ' ' . ($enabled ? 'enabled' : 'disabled') . ' by uid=' . $uid);
            mokko_upd_api_json(array('ok' => true, 'state' => mokko_upd_state_payload()));
            break;

        case 'module_remove':
            mokko_require_json();
            mokko_require_csrf();

            $in = mokko_json_input();
            $slug = isset($in['slug']) ? (string)$in['slug'] : '';

            try {
                $moved = mokko_module_remove($slug);
            } catch (Throwable $e) {
                mokko_upd_api_error($e->getMessage(), 400);
            }

            mokko_log('updates: module ' . $slug . ' removed to ' . $moved . ' by uid=' . $uid);
            mokko_upd_api_json(array('ok' => true, 'message' => 'Модуль перемещён в ' . $moved, 'state' => mokko_upd_state_payload()));
            break;

        case 'maintenance_end':
            mokko_require_json();
            mokko_require_csrf();
            mokko_maintenance_end();
            mokko_upd_api_json(array('ok' => true, 'state' => mokko_upd_state_payload()));
            break;

        case 'state':
            mokko_require_json();
            mokko_require_csrf();
            mokko_upd_api_json(mokko_upd_state_payload());
            break;

        case 'secret_create':
            mokko_require_json();
            mokko_require_csrf();

            try {
                $secret = null;
                if (mokko_upd_secret() === null) {
                    $secret = mokko_upd_secret_create();
                }
            } catch (MokkoUpdateException $e) {
                mokko_upd_api_error($e->getMessage(), 500);
            }

            mokko_upd_api_json(array(
                'ok'      => true,
                'secret'  => $secret,
                'message' => $secret !== null
                    ? 'Ключ создан. Сохраните его: он показывается один раз.'
                    : 'Ключ уже задан в config/update.secret.',
                'state'   => mokko_upd_state_payload(),
            ));
            break;

        case 'secret_download':
            // Ключ подписи — секрет. Отдаём его только администратору,
            // POST-запросом и после проверки CSRF (для скачивания токен
            // приходит в скрытом поле формы, а не заголовком).
            $csrf = isset($_POST['csrf']) ? (string)$_POST['csrf'] : null;
            if (!mokko_csrf_valid($csrf)) {
                mokko_upd_api_error('Неверный CSRF-токен. Обновите страницу.', 403);
            }
            $secret = mokko_upd_secret();
            if ($secret === null) {
                mokko_upd_api_error('Ключ подписи не найден.', 404);
            }
            mokko_log('updates: secret downloaded by uid=' . $uid);
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="update.secret"');
            header('Content-Length: ' . strlen($secret . "\n"));
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            echo $secret . "\n";
            exit;

        default:
            mokko_upd_api_error('Неизвестное действие.', 404);
    }
} catch (Throwable $e) {
    mokko_log('updates: unhandled: ' . $e->getMessage());
    if (!headers_sent()) {
        mokko_upd_api_error('Внутренняя ошибка: ' . $e->getMessage(), 500);
    }
}

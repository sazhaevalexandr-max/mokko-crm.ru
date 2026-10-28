<?php
/**
 * MOKKO CRM — общий модуль безопасности.
 *
 * Подключается первым во всех точках входа (index.php, api/*.php, subscriber.php).
 * Содержит: hardening сессии, security-заголовки, JSON-ответы, проверку
 * same-origin запросов, rate limiting и хелперы экранирования.
 *
 * Требования: PHP 7.3+, PDO MySQL.
 */

if (defined('MOKKO_SECURITY')) {
    return;
}
define('MOKKO_SECURITY', 1);

/** Время жизни сессии: 30 минут бездействия и 12 часов всего. */
define('MOKKO_SESSION_IDLE', 1800);
define('MOKKO_SESSION_MAX', 43200);

/**
 * Скрываем подробные ошибки от пользователя, пишем их в лог.
 */
function mokko_harden_errors()
{
    error_reporting(E_ALL);
    @ini_set('display_errors', '0');
    @ini_set('display_startup_errors', '0');
    @ini_set('log_errors', '1');
    @ini_set('html_errors', '0');
}

/**
 * Запись в лог без раскрытия деталей клиенту.
 */
function mokko_log($message)
{
    @error_log('[mokko] ' . str_replace(array("\r", "\n"), ' ', (string)$message));
}

/**
 * Работает ли запрос по HTTPS.
 */
function mokko_is_https()
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])
        && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
        return true;
    }
    return (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
}

/**
 * Безопасное значение Host (защита от Host header injection).
 */
function mokko_host()
{
    $host = isset($_SERVER['HTTP_HOST']) ? (string)$_SERVER['HTTP_HOST'] : '';
    if (!preg_match('/^[A-Za-z0-9]([A-Za-z0-9.\-]*[A-Za-z0-9])?(:\d{1,5})?$/', $host)) {
        $host = 'localhost';
    }
    return $host;
}

/**
 * Запуск сессии с безопасными атрибутами cookie.
 */
function mokko_session_start()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    if (headers_sent()) {
        return;
    }

    $https = mokko_is_https();

    @ini_set('session.use_strict_mode', '1');
    @ini_set('session.use_only_cookies', '1');
    @ini_set('session.use_trans_sid', '0');
    @ini_set('session.cookie_httponly', '1');
    @ini_set('session.sid_length', '48');
    @ini_set('session.sid_bits_per_character', '6');

    session_name('MOKKOSESSID');

    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params(array(
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ));
    } else {
        session_set_cookie_params(0, '/; samesite=Lax', '', $https, true);
    }

    session_start();

    // Абсолютный и idle-таймауты сессии + отметка активности.
    mokko_session_guard();
}

/**
 * Завершает сессию по абсолютному (12 ч) или idle-таймауту (30 мин).
 * Вызывается после session_start() на каждом запросе, чтобы «вечная»
 * украденная cookie не жила месяцами.
 */
function mokko_session_guard()
{
    if (empty($_SESSION['uid']) || empty($_SESSION['key'])) {
        return;
    }

    $now    = time();
    $issued = isset($_SESSION['issued']) ? (int)$_SESSION['issued'] : 0;
    $seen   = isset($_SESSION['last_seen']) ? (int)$_SESSION['last_seen'] : $issued;

    $expired = ($issued > 0 && ($now - $issued) > MOKKO_SESSION_MAX)
        || ($seen > 0 && ($now - $seen) > MOKKO_SESSION_IDLE);

    if ($expired) {
        $_SESSION = array();
        if (!headers_sent()) {
            session_regenerate_id(true);
        }
        return;
    }

    $_SESSION['last_seen'] = $now;
}

/**
 * Базовые security-заголовки.
 */
function mokko_send_security_headers()
{
    if (headers_sent()) {
        return;
    }

    @header_remove('X-Powered-By');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header("Content-Security-Policy: frame-ancestors 'self'; base-uri 'self'; form-action 'self'");
}

/**
 * JSON-ответ и завершение выполнения.
 */
function mokko_json($payload, $status = 200)
{
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Короткий ответ с ошибкой.
 */
function mokko_fail($message, $status = 400)
{
    mokko_json(array('error' => (string)$message), $status);
}

function mokko_request_method()
{
    return strtoupper(isset($_SERVER['REQUEST_METHOD']) ? (string)$_SERVER['REQUEST_METHOD'] : 'GET');
}

/**
 * Требуем POST (для действий, изменяющих состояние).
 */
function mokko_require_post()
{
    if (mokko_request_method() !== 'POST') {
        header('Allow: POST');
        mokko_fail('method not allowed', 405);
    }
}

/**
 * Проверка, что запрос пришёл с того же origin (защита от CSRF).
 * Современные браузеры присылают Sec-Fetch-Site / Origin / Referer.
 */
function mokko_is_same_origin()
{
    $host = isset($_SERVER['HTTP_HOST']) ? (string)$_SERVER['HTTP_HOST'] : '';

    $site = isset($_SERVER['HTTP_SEC_FETCH_SITE']) ? (string)$_SERVER['HTTP_SEC_FETCH_SITE'] : '';
    if ($site !== '' && $site !== 'same-origin' && $site !== 'none') {
        return false;
    }

    $origin = isset($_SERVER['HTTP_ORIGIN']) ? (string)$_SERVER['HTTP_ORIGIN'] : '';
    if ($origin !== '') {
        $parts = parse_url($origin);
        if (!is_array($parts) || empty($parts['host'])) {
            return false;
        }
        $originHost = $parts['host'] . (empty($parts['port']) ? '' : ':' . $parts['port']);
        if (strcasecmp($originHost, $host) !== 0) {
            return false;
        }
        return true;
    }

    $referer = isset($_SERVER['HTTP_REFERER']) ? (string)$_SERVER['HTTP_REFERER'] : '';
    if ($referer !== '') {
        $parts = parse_url($referer);
        if (!is_array($parts) || empty($parts['host'])) {
            return false;
        }
        $refererHost = $parts['host'] . (empty($parts['port']) ? '' : ':' . $parts['port']);
        if (strcasecmp($refererHost, $host) !== 0) {
            return false;
        }
    }

    return true;
}

function mokko_require_same_origin()
{
    if (!mokko_is_same_origin()) {
        mokko_fail('forbidden', 403);
    }
}

/**
 * Требуем Content-Type: application/json (нельзя отправить обычной HTML-формой).
 */
function mokko_require_json()
{
    $type = isset($_SERVER['CONTENT_TYPE']) ? (string)$_SERVER['CONTENT_TYPE'] : '';
    if (stripos($type, 'application/json') !== 0) {
        mokko_fail('unsupported media type', 415);
    }
}

/**
 * Разбор JSON-тела запроса с ограничением размера.
 */
function mokko_json_input()
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return array();
    }
    if (strlen($raw) > 1048576) { // 1 MiB
        mokko_fail('payload too large', 413);
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : array();
}

function mokko_is_authenticated()
{
    return !empty($_SESSION['key']) && !empty($_SESSION['uid']);
}

/**
 * Установлена ли система: база доступна и в ней есть хотя бы один пользователь.
 * Используется фронт-контроллером, чтобы до установки не показывать
 * полупустой интерфейс с ошибками БД, а сразу вести в setup/install.php.
 */
function mokko_is_installed()
{
    static $installed = null;
    if ($installed !== null) {
        return $installed;
    }

    $installed = false;
    try {
        require_once __DIR__ . '/db.php';
        $pdo = getDB();
        $installed = ((int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn()) > 0;
    } catch (Exception $e) {
        $installed = false;
    } catch (Throwable $e) {
        $installed = false;
    }

    return $installed;
}

/**
 * Требуем авторизованную сессию, возвращаем id пользователя.
 */
function mokko_require_auth()
{
    if (!mokko_is_authenticated()) {
        mokko_fail('invalid token', 401);
    }
    return (int)$_SESSION['uid'];
}

/**
 * Каталог для файловых счётчиков. Если временный каталог недоступен,
 * используется storage/rate-limit внутри проекта: защита не должна
 * молча отключаться из-за прав на /tmp.
 */
function mokko_rate_limit_dir()
{
    static $dir = null;
    if ($dir !== null) {
        return $dir;
    }

    $candidates = array(
        rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'mokko-rate-limit',
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'rate-limit',
    );

    foreach ($candidates as $candidate) {
        if (!is_dir($candidate)) {
            @mkdir($candidate, 0700, true);
        }
        if (is_dir($candidate) && is_writable($candidate)) {
            $dir = $candidate;
            return $dir;
        }
    }

    mokko_log('rate-limit: no writable directory');
    $dir = false;
    return $dir;
}

/**
 * Учёт попытки. Возвращает array(allowed, retry, count).
 * Если счётчик недоступен, запрос отклоняется (fail-closed), а не
 * пропускается молча.
 */
function mokko_rate_limit_info($bucket, $max, $window)
{
    $bucket = preg_replace('/[^a-z0-9_\-]/i', '', (string)$bucket);
    if ($bucket === '') {
        return array('allowed' => true, 'retry' => 0, 'count' => 0);
    }

    $dir = mokko_rate_limit_dir();
    if ($dir === false) {
        return array('allowed' => false, 'retry' => 60, 'count' => 0);
    }

    $ip   = isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    $file = $dir . DIRECTORY_SEPARATOR . $bucket . '-' . hash('sha256', $bucket . '|' . $ip) . '.json';
    $fp   = @fopen($file, 'c+');
    if (!$fp) {
        mokko_log('rate-limit: cannot open bucket ' . $bucket);
        return array('allowed' => false, 'retry' => 60, 'count' => 0);
    }

    $allowed = true;
    $retry   = 0;
    $count   = 0;

    if (@flock($fp, LOCK_EX)) {
        $data = json_decode((string)stream_get_contents($fp), true);
        $now  = time();

        if (!is_array($data) || empty($data['start']) || ($now - (int)$data['start']) > $window) {
            $data = array('start' => $now, 'count' => 0);
        }

        $data['count'] = (int)$data['count'] + 1;
        $count   = $data['count'];
        $allowed = $count <= $max;
        $retry   = max(0, ((int)$data['start'] + $window) - $now);

        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($data));
        fflush($fp);
        @flock($fp, LOCK_UN);
    }
    fclose($fp);

    // Изредка чистим старые бакеты, иначе каталог растёт бесконечно.
    if (mt_rand(1, 200) === 1) {
        foreach ((array)glob($dir . DIRECTORY_SEPARATOR . '*.json') as $old) {
            if (@filemtime($old) < time() - 7200) {
                @unlink($old);
            }
        }
    }

    return array('allowed' => $allowed, 'retry' => $retry, 'count' => $count);
}

/**
 * Совместимость: true, если запрос разрешён.
 */
function mokko_rate_limit($bucket, $max, $window)
{
    $info = mokko_rate_limit_info($bucket, $max, $window);
    return $info['allowed'];
}

/**
 * Проверка лимита с немедленным ответом 429 и заголовком Retry-After.
 */
function mokko_rate_limit_guard($bucket, $max, $window)
{
    $info = mokko_rate_limit_info($bucket, $max, $window);
    if (!$info['allowed']) {
        $retry = max(1, (int)$info['retry']);
        if (!headers_sent()) {
            header('Retry-After: ' . $retry);
        }
        mokko_json(array(
            'error'       => 'limit',
            'retry_after' => $retry,
        ), 429);
    }
    return $info;
}

/**
 * Сброс счётчика (например, после успешного входа).
 */
function mokko_rate_limit_clear($bucket)
{
    $bucket = preg_replace('/[^a-z0-9_\-]/i', '', (string)$bucket);
    if ($bucket === '') {
        return;
    }
    $dir = mokko_rate_limit_dir();
    if ($dir === false) {
        return;
    }
    $ip   = isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    $file = $dir . DIRECTORY_SEPARATOR . $bucket . '-' . hash('sha256', $bucket . '|' . $ip) . '.json';
    if (is_file($file)) {
        @unlink($file);
    }
}

/**
 * HTML-экранирование.
 */
function mokko_h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Экранирование значения для вставки в JS (в <script>).
 */
function mokko_js($value)
{
    $encoded = json_encode(
        $value,
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
    );
    return $encoded === false ? 'null' : $encoded;
}

/**
 * Нормализация строки из запроса: обрезка, удаление управляющих символов,
 * ограничение длины. Не «магичит» с SQL — для SQL везде prepared statements.
 */
function mokko_str($value, $maxLength = 255)
{
    $value = trim((string)$value);
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
    if ($value === null) {
        return '';
    }
    $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    if ($length > $maxLength) {
        $value = function_exists('mb_substr')
            ? mb_substr($value, 0, $maxLength, 'UTF-8')
            : substr($value, 0, $maxLength);
    }
    return $value;
}

/**
 * Проверка пароля: не короче 8 СИМВОЛОВ (а не байт) и не длиннее 72 байт
 * (ограничение bcrypt). Кириллица в UTF-8 занимает 2 байта на символ,
 * поэтому strlen() пропускал пароли из 4–7 символов.
 */
function mokko_password_ok($password)
{
    $password = (string)$password;
    $bytes = strlen($password);
    if ($bytes < 8 || $bytes > 72) {
        return false;
    }
    $chars = function_exists('mb_strlen') ? mb_strlen($password, 'UTF-8') : $bytes;
    return $chars >= 8;
}

/**
 * Отпечаток хеша пароля для привязки сессии к текущему паролю.
 * Смена пароля меняет отпечаток — старые сессии становятся недействительными.
 */
function mokko_password_fingerprint($hash)
{
    return substr(hash('sha256', 'mokko-pv|' . (string)$hash), 0, 32);
}

/**
 * Административная сессия: владелец аккаунта (sid = uid), а не бариста.
 * Роль в БД проверяется отдельно на страницах, где это важно.
 */
function mokko_is_admin_session()
{
    return mokko_is_authenticated()
        && isset($_SESSION['sid'])
        && $_SESSION['sid'] === 'uid';
}

/**
 * CSRF-токен для форм и загрузки файлов.
 *
 * JSON-API дополнительно проверяет Origin/Referer, но загрузка ZIP идёт
 * обычным multipart-запросом, поэтому для неё нужен отдельный токен.
 */
function mokko_csrf_token()
{
    if (empty($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function mokko_csrf_valid($token = null)
{
    if ($token === null) {
        $token = isset($_SERVER['HTTP_X_CSRF_TOKEN']) ? $_SERVER['HTTP_X_CSRF_TOKEN'] : '';
    }
    return is_string($token)
        && $token !== ''
        && !empty($_SESSION['csrf'])
        && is_string($_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $token);
}

function mokko_require_csrf()
{
    if (!mokko_csrf_valid()) {
        mokko_fail('invalid csrf token', 403);
    }
}

/**
 * Режим обслуживания. Пока существует config/maintenance.flag, посетители
 * получают 503, а администратор может завершить обновление или откатиться.
 */
function mokko_maintenance_file()
{
    return dirname(__DIR__) . '/config/maintenance.flag';
}

function mokko_in_maintenance()
{
    return is_file(mokko_maintenance_file());
}

function mokko_maintenance_begin($reason = '')
{
    $data = array(
        'since'  => date('c'),
        'reason' => (string)$reason,
        'pid'    => function_exists('getmypid') ? getmypid() : 0,
    );
    $ok = @file_put_contents(
        mokko_maintenance_file(),
        json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        LOCK_EX
    );
    return $ok !== false;
}

function mokko_maintenance_end()
{
    $file = mokko_maintenance_file();
    return !is_file($file) || @unlink($file);
}

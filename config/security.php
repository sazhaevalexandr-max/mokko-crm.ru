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
 * Простое ограничение частоты запросов (per-IP, файловый бакет).
 * Возвращает true, если запрос разрешён.
 */
function mokko_rate_limit($bucket, $max, $window)
{
    $bucket = preg_replace('/[^a-z0-9_\-]/i', '', (string)$bucket);
    if ($bucket === '') {
        return true;
    }

    $ip  = isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    $dir = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'mokko-rate-limit';

    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    if (!is_dir($dir)) {
        return true; // не ломаем вход, если нет доступа к каталогу
    }

    $file = $dir . DIRECTORY_SEPARATOR . $bucket . '-' . hash('sha256', $bucket . '|' . $ip) . '.json';
    $fp   = @fopen($file, 'c+');
    if (!$fp) {
        return true;
    }

    $allowed = true;
    if (@flock($fp, LOCK_EX)) {
        $raw  = stream_get_contents($fp);
        $data = json_decode((string)$raw, true);
        $now  = time();

        if (!is_array($data) || empty($data['start']) || ($now - (int)$data['start']) > $window) {
            $data = array('start' => $now, 'count' => 0);
        }

        $data['count'] = (int)$data['count'] + 1;
        $allowed = $data['count'] <= $max;

        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($data));
        fflush($fp);
        @flock($fp, LOCK_UN);
    }
    fclose($fp);

    return $allowed;
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
    $ip  = isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    $dir = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'mokko-rate-limit';
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
 * Проверка минимальной длины пароля (в байтах, bcrypt limit = 72).
 */
function mokko_password_ok($password)
{
    $length = strlen((string)$password);
    return $length >= 8 && $length <= 72;
}

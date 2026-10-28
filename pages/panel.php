<?php
/**
 * Легаси-копия панели. Штатный маршрут — /panel (index.php).
 *
 * Исправлено: удалён «вход» по POST-параметру key (обходил авторизацию).
 */

define('MOKKO', true);

require_once dirname(__DIR__) . '/config/security.php';
mokko_harden_errors();
mokko_session_start();
mokko_send_security_headers();

$base = rtrim(str_replace('\\', '/', dirname(dirname(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/'))), '/');
define('BASE_PATH', $base);
define('BASE_URL', (mokko_is_https() ? 'https' : 'http') . '://' . mokko_host() . $base);
unset($base);

if (!mokko_is_authenticated() || !isset($_SESSION['sid']) || $_SESSION['sid'] !== 'uid') {
    header('Location: ' . BASE_URL . '/login');
    exit;
}

if (isset($_GET['do']) && (string)$_GET['do'] === 'exit') {
    $_SESSION = array();
    session_destroy();
    header('Location: ' . BASE_URL . '/login');
    exit;
}

require dirname(__DIR__) . '/lib/panel.php';

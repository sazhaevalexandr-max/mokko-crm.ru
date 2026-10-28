<?php
/**
 * Служебное API выбора кофейни.
 *
 * Исправлено: раньше uid брался из GET-параметра и авторизация не
 * проверялась вообще — любой мог прочитать чужие кофейни (IDOR).
 * Теперь uid берётся только из серверной сессии.
 */

require_once dirname(__DIR__) . '/config/security.php';
mokko_harden_errors();
mokko_session_start();
mokko_send_security_headers();

require_once dirname(__DIR__) . '/config/db.php';

if (!mokko_is_authenticated()) {
    mokko_json(array('error' => 'invalid token'));
}

$uid    = (int)$_SESSION['uid'];
$action = isset($_GET['action']) ? (string)$_GET['action'] : '';

switch ($action) {
    case 'ini':
        handleIni($uid);
        break;
    case 'load':
        handleLoad($uid);
        break;
    default:
        mokko_json(array('error' => 'unknown action'));
}

function handleIni($uid)
{
    try {
        $stmt = getDB()->prepare('SELECT status FROM users WHERE id = ? LIMIT 1');
        $stmt->execute(array($uid));
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        mokko_log('places:ini db error: ' . $e->getMessage());
        mokko_json(array('error' => 'server'));
    }

    if (!$user) {
        mokko_json(array('error' => 'not found'));
    }

    mokko_json(array('settings' => array(
        'currency' => 'RUB',
        'status'   => $user['status'],
    )));
}

function handleLoad($uid)
{
    try {
        $stmt = getDB()->prepare('SELECT id, name FROM places WHERE user_id = ? ORDER BY id');
        $stmt->execute(array($uid));
        mokko_json($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Exception $e) {
        mokko_log('places:load db error: ' . $e->getMessage());
        mokko_json(array());
    }
}

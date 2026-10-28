<?php
/**
 * Авторизация / регистрация (JSON API).
 *
 * Исправлено:
 *  - раньше сессия не перегенерировалась (session fixation);
 *  - сообщения БД уходили клиенту (раскрытие структуры БД);
 *  - не было ограничения попыток входа (brute force);
 *  - разные ответы для «нет пользователя» и «неверный пароль» (enumeration);
 *  - не проверялась длина/качество пароля при регистрации.
 */

require_once dirname(__DIR__) . '/config/security.php';
mokko_harden_errors();
mokko_session_start();
mokko_send_security_headers();

require_once dirname(__DIR__) . '/config/db.php';

mokko_require_post();
mokko_require_same_origin();
mokko_require_json();

// «Пустой» bcrypt-хеш: password_verify() тратит столько же времени,
// сколько для реального пользователя (защита от user enumeration по времени).
define('MOKKO_DUMMY_HASH', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

$action = isset($_GET['action']) ? (string)$_GET['action'] : '';

switch ($action) {
    case 'login':
        handleLogin();
        break;
    case 'register':
        handleRegister();
        break;
    default:
        mokko_json(array('error' => 'unknown action'));
}

/**
 * Вход.
 */
function handleLogin()
{
    $data = mokko_json_input();

    $login    = isset($data['login']) && is_string($data['login']) ? trim($data['login']) : '';
    $password = isset($data['password']) && is_string($data['password']) ? $data['password'] : '';

    if ($login === '' || $password === '' || strlen($login) > 254 || strlen($password) > 4096) {
        mokko_json(array('error' => 'invalid'));
    }

    if (!mokko_rate_limit('login', 10, 300)) {
        mokko_json(array('error' => 'limit'));
    }

    try {
        $pdo  = getDB();
        $stmt = $pdo->prepare('SELECT id, password_hash, role FROM users WHERE email = ? LIMIT 1');
        $stmt->execute(array($login));
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        mokko_log('login: db error: ' . $e->getMessage());
        mokko_json(array('error' => 'server'));
    }

    // Нет владельца с таким логином — ищем среди сотрудников (бариста).
    // Бариста работает в данных владельца: uid в сессии — id владельца,
    // а staff_id отличает его от владельца и закрывает доступ к панели.
    $staff = null;
    if (!$user) {
        try {
            $st = $pdo->prepare('SELECT s.id, s.uid, s.name, s.lastname, s.password_hash FROM staff s JOIN users u ON u.id = s.uid WHERE s.email = ? ORDER BY s.id DESC LIMIT 1');
            $st->execute(array($login));
            $staff = $st->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Exception $e) {
            mokko_log('login: staff lookup failed: ' . $e->getMessage());
        }
    }

    if ($staff) {
        $hash = (string)$staff['password_hash'];
    } else {
        $hash = $user ? (string)$user['password_hash'] : MOKKO_DUMMY_HASH;
    }
    $ok = $hash !== '' && password_verify($password, $hash);

    if ($staff && $ok) {
        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            try {
                $pdo->prepare('UPDATE staff SET password_hash = ? WHERE id = ?')
                    ->execute(array(password_hash($password, PASSWORD_DEFAULT), (int)$staff['id']));
            } catch (Exception $e) {
                mokko_log('login: staff rehash failed: ' . $e->getMessage());
            }
        }
        mokko_rate_limit_clear('login');
        session_regenerate_id(true);
        $_SESSION = array();
        $_SESSION['key']        = bin2hex(random_bytes(16));
        $_SESSION['uid']        = (int)$staff['uid'];
        $_SESSION['sid']        = 'staff';
        $_SESSION['staff_id']   = (int)$staff['id'];
        $_SESSION['issued']     = time();
        mokko_json(array('success' => true, 'sid' => 'staff'));
    }

    if (!$ok || !$user) {
        mokko_log('login: failed attempt for ' . substr($login, 0, 100));
        mokko_json(array('error' => 'invalid'));
    }

    // Прозрачное обновление хеша при смене алгоритма/стоимости.
    if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
        try {
            $upd = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $upd->execute(array(password_hash($password, PASSWORD_DEFAULT), (int)$user['id']));
        } catch (Exception $e) {
            mokko_log('login: rehash failed: ' . $e->getMessage());
        }
    }

    mokko_rate_limit_clear('login');

    // Защита от session fixation.
    session_regenerate_id(true);
    $_SESSION = array();
    $_SESSION['key']    = bin2hex(random_bytes(16));
    $_SESSION['uid']    = (int)$user['id'];
    $_SESSION['sid']    = ($user['role'] === 'admin') ? 'uid' : 'staff';
    $_SESSION['issued'] = time();

    mokko_json(array('success' => true, 'sid' => $_SESSION['sid']));
}

/**
 * Регистрация нового владельца аккаунта.
 */
function handleRegister()
{
    $data = mokko_json_input();

    $login    = isset($data['login']) && is_string($data['login']) ? trim($data['login']) : '';
    $password = isset($data['password']) && is_string($data['password']) ? $data['password'] : '';
    $name     = isset($data['name']) && is_string($data['name']) ? mokko_str($data['name'], 100) : '';

    if ($login === '' || $password === '' || $name === '') {
        mokko_json(array('error' => 'invalid data'));
    }
    if (!filter_var($login, FILTER_VALIDATE_EMAIL) || strlen($login) > 254) {
        mokko_json(array('error' => 'invalid email'));
    }
    if (!mokko_password_ok($password)) {
        mokko_json(array('error' => 'password must be 8-72 characters'));
    }

    if (!mokko_rate_limit('register', 5, 3600)) {
        mokko_json(array('error' => 'limit'));
    }

    try {
        $pdo = getDB();

        $chk = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $chk->execute(array($login));
        if ($chk->fetch()) {
            // Не подтверждаем существование адреса — отвечаем нейтрально.
            mokko_json(array('success' => true));
        }

        $stmt = $pdo->prepare(
            'INSERT INTO users (email, password_hash, name, role) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute(array(
            $login,
            password_hash($password, PASSWORD_DEFAULT),
            $name,
            'admin',
        ));
    } catch (Exception $e) {
        mokko_log('register: db error: ' . $e->getMessage());
        mokko_json(array('error' => 'server'));
    }

    mokko_json(array('success' => true));
}

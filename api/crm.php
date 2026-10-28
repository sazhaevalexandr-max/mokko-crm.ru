<?php
/**
 * Основное JSON API CRM.
 *
 * Исправлено по сравнению с прежней версией:
 *  - авторизация: сессия + проверка пользователя в БД на каждом запросе;
 *  - разграничение прав: бариста больше не может вызывать админские модули
 *    (places/staff/category/item/lager/stat/user);
 *  - CSRF: только POST + application/json + проверка Origin/Referer;
 *  - сообщения об ошибках БД больше не уходят клиенту;
 *  - проверка длины паролей и входных данных;
 *  - сумма чека пересчитывается на сервере по ценам меню (нельзя подделать total);
 *  - place/shift проверяются на принадлежность текущему пользователю.
 */

require_once dirname(__DIR__) . '/config/security.php';
mokko_harden_errors();
mokko_session_start();
mokko_send_security_headers();

require_once dirname(__DIR__) . '/config/db.php';

$uid = mokko_require_auth();

mokko_require_post();
mokko_require_same_origin();
mokko_require_json();

$module = isset($_GET['module']) ? (string)$_GET['module'] : '';
$action = isset($_GET['action']) ? (string)$_GET['action'] : '';
$p      = mokko_json_input();

try {
    $pdo = getDB();
} catch (Exception $e) {
    mokko_log('crm: db connect error: ' . $e->getMessage());
    mokko_json(array('error' => 'server'));
}

// Пользователь и его роль берутся из БД, а не из сессии/запроса.
$stmt = $pdo->prepare('SELECT id, role, status, password_hash FROM users WHERE id = ? LIMIT 1');
$stmt->execute(array($uid));
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$currentUser) {
    $_SESSION = array();
    mokko_json(array('error' => 'invalid token'));
}

$isAdmin = ($currentUser['role'] === 'admin');

// Сессия бариста: работает в данных владельца ($uid), но с правами кассира.
// Карточку сотрудника проверяем на каждом запросе — удалённый сотрудник
// сразу теряет доступ.
$staffId  = isset($_SESSION['staff_id']) ? (int)$_SESSION['staff_id'] : 0;
$staffRow = null;
if ($staffId > 0) {
    $st = $pdo->prepare('SELECT id, name, lastname, email, password_hash FROM staff WHERE id = ? AND uid = ? LIMIT 1');
    $st->execute(array($staffId, $uid));
    $staffRow = $st->fetch(PDO::FETCH_ASSOC);
    if (!$staffRow) {
        $_SESSION = array();
        mokko_json(array('error' => 'invalid token'));
    }
    $isAdmin = false;
}

// Сессия привязана к текущему паролю: после смены пароля все остальные
// сессии (включая украденные cookie) получают 401 на следующем запросе.
$sessionHash = $staffRow ? (string)$staffRow['password_hash'] : (string)$currentUser['password_hash'];
if (empty($_SESSION['pv']) || !hash_equals(mokko_password_fingerprint($sessionHash), (string)$_SESSION['pv'])) {
    $_SESSION = array();
    mokko_json(array('error' => 'invalid token'), 401);
}

/** Смены владельца (staff_id IS NULL) или конкретного бариста. */
function mokko_shift_owner_sql($staffId)
{
    $staffId = (int)$staffId;
    return $staffId > 0 ? 'staff_id = ' . $staffId : 'staff_id IS NULL';
}

/**
 * Колонка shifts.staff_id — кто открыл смену (NULL — владелец).
 * Добавляется автоматически при первом обращении, без ручной миграции.
 */
function mokko_shifts_ready($pdo)
{
    static $ready = false;
    if ($ready) { return; }
    try {
        $pdo->query('SELECT staff_id FROM shifts LIMIT 0');
    } catch (Exception $e) {
        try {
            $pdo->exec('ALTER TABLE shifts ADD COLUMN staff_id INT NULL');
        } catch (Exception $e2) {
            mokko_log('shifts: add staff_id failed: ' . $e2->getMessage());
        }
    }
    $ready = true;
}

// ── Разграничение прав ──────────────────────────────────────────────────────
// Для бариста доступны только эти модули/действия.
$staffPermissions = array(
    'ini'        => '*',
    'guests'     => '*',
    'startshift' => '*',
    'shift'      => '*',
    'buy'        => '*',
    'places'     => array('get'),
    'staff'      => array('newpassword'),
    'category'   => array('load'),
    'items'      => array('load'),
);

if (!$isAdmin) {
    if (!isset($staffPermissions[$module])) {
        mokko_json(array('error' => 'access denied'), 403);
    }
    $allowed = $staffPermissions[$module];
    if ($allowed !== '*' && !in_array($action, $allowed, true)) {
        mokko_json(array('error' => 'access denied'), 403);
    }
}

// ── Маршрутизация ───────────────────────────────────────────────────────────
switch ($module) {
    case 'ini':        handleIni();         break;
    case 'places':     handlePlaces();      break;
    case 'staff':      handleStaff();       break;
    case 'category':   handleCategory();    break;
    case 'items':      handleItems();       break;
    case 'item':       handleItem();        break;
    case 'lager':      handleLager();       break;
    case 'guests':     handleGuests();      break;
    case 'startshift': handleStartShift();  break;
    case 'shift':      handleShift();       break;
    case 'buy':        handleBuy();         break;
    case 'user':       handleUser();        break;
    case 'stat':       handleStat();        break;
    default:
        mokko_json(array('error' => 'unknown module'));
}

// ─────────────────────────────────────────────────────────────────────────────

/**
 * Проверяет, что кофейня принадлежит текущему пользователю.
 */
function mokko_place_owned($pdo, $uid, $place)
{
    if ($place <= 0) {
        return false;
    }
    $s = $pdo->prepare('SELECT id FROM places WHERE id = ? AND user_id = ? LIMIT 1');
    $s->execute(array($place, $uid));
    return (bool)$s->fetch(PDO::FETCH_ASSOC);
}

/**
 * Проверяет дату вида YYYY-MM-DD.
 */
function mokko_date($value, $fallback)
{
    $value = (string)$value;
    $d = DateTime::createFromFormat('Y-m-d', $value);
    if ($d && $d->format('Y-m-d') === $value) {
        return $value;
    }
    return $fallback;
}

// ─── ini ────────────────────────────────────────────────────────────────────
function handleIni()
{
    global $pdo, $uid;

    $u = $pdo->prepare('SELECT name, lastname, email, status, wallet, phone FROM users WHERE id = ? LIMIT 1');
    $u->execute(array($uid));
    $user = $u->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        mokko_json(array('error' => 'user not found'));
    }
    global $staffRow;
    if ($staffRow) {
        // Бариста видит своё имя, а не имя владельца
        $user['name'] = $staffRow['name'];
        $user['lastname'] = $staffRow['lastname'];
        $user['email'] = $staffRow['email'];
        $user['phone'] = '';
        $user['wallet'] = 0;
    }
    mokko_json(array('settings' => array(
        'name'     => $user['name'],
        'lastname' => $user['lastname'],
        'login'    => $user['email'],
        'currency' => 'RUB',
        'status'   => $user['status'],
        'wallet'   => $user['wallet'],
        'phone'    => $user['phone'],
    )));
}

/**
 * Реквизиты: ИНН 10 или 12 цифр, КПП 9 цифр, телефон 10–15 цифр (с необязательным «+»).
 * Пустое значение допустимо — поля необязательные.
 */
function mokko_requisites_error($inn, $kpp, $phone)
{
    if ($inn !== '' && !preg_match('/^(\d{10}|\d{12})$/', $inn)) {
        return 'invalid inn';
    }
    if ($kpp !== '' && !preg_match('/^\d{9}$/', $kpp)) {
        return 'invalid kpp';
    }
    if ($phone !== '' && !preg_match('/^\+?\d{10,15}$/', $phone)) {
        return 'invalid phone';
    }
    return '';
}

/** Оставляем в телефоне только цифры и ведущий «+». */
function mokko_phone_clean($phone)
{
    $phone = trim((string)$phone);
    $plus = (strpos($phone, '+') === 0) ? '+' : '';
    return $plus . preg_replace('/\D+/', '', $phone);
}

/**
 * Проверка e-mail сотрудника: формат, длина и глобальная уникальность
 * (логин ищется и среди владельцев, и среди сотрудников).
 */
function mokko_staff_email_error($pdo, $email, $excludeId = 0)
{
    if ($email === '' || strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'invalid email';
    }
    $u = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $u->execute(array($email));
    if ($u->fetch(PDO::FETCH_ASSOC)) {
        return 'email already used';
    }
    $s = $pdo->prepare('SELECT id FROM staff WHERE email = ? AND id <> ? LIMIT 1');
    $s->execute(array($email, (int)$excludeId));
    if ($s->fetch(PDO::FETCH_ASSOC)) {
        return 'email already used';
    }
    return '';
}

// ─── places ─────────────────────────────────────────────────────────────────
function handlePlaces()
{
    global $pdo, $uid, $action, $p;

    switch ($action) {
        case 'load':
            $s = $pdo->prepare('SELECT id, name, address, inn, kpp, ltd, phone FROM places WHERE user_id = ? ORDER BY id');
            $s->execute(array($uid));
            mokko_json($s->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'get':
            $id = (int)(isset($p['place']) ? $p['place'] : 0);
            $s = $pdo->prepare('SELECT id, name, address, inn, kpp, ltd, phone FROM places WHERE id = ? AND user_id = ? LIMIT 1');
            $s->execute(array($id, $uid));
            $row = $s->fetch(PDO::FETCH_ASSOC);
            mokko_json($row ? $row : array('error' => 'not found'));
            break;

        case 'new':
            $name    = mokko_str(isset($p['name']) ? $p['name'] : '', 255);
            $address = mokko_str(isset($p['address']) ? $p['address'] : '', 255);
            $inn     = mokko_str(isset($p['inn']) ? $p['inn'] : '', 20);
            $kpp     = mokko_str(isset($p['kpp']) ? $p['kpp'] : '', 20);
            $ltd     = mokko_str(isset($p['ltd']) ? $p['ltd'] : '', 255);
            $phone   = mokko_phone_clean(mokko_str(isset($p['phone']) ? $p['phone'] : '', 20));
            $inn     = preg_replace('/\s+/', '', $inn);
            $kpp     = preg_replace('/\s+/', '', $kpp);

            if ($name === '' || $address === '') {
                mokko_json(array('error' => 'name and address required'));
            }
            $err = mokko_requisites_error($inn, $kpp, $phone);
            if ($err !== '') {
                mokko_json(array('error' => $err));
            }

            $s = $pdo->prepare('INSERT INTO places (user_id, name, address, inn, kpp, ltd, phone) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $s->execute(array($uid, $name, $address, $inn, $kpp, $ltd, $phone));
            $id = (int)$pdo->lastInsertId();

            mokko_json(array(
                'id' => $id, 'name' => $name, 'address' => $address,
                'inn' => $inn, 'kpp' => $kpp, 'ltd' => $ltd, 'phone' => $phone,
            ));
            break;

        case 'edit':
            $id      = (int)(isset($p['place']) ? $p['place'] : 0);
            $name    = mokko_str(isset($p['name']) ? $p['name'] : '', 255);
            $address = mokko_str(isset($p['address']) ? $p['address'] : '', 255);
            $inn     = mokko_str(isset($p['inn']) ? $p['inn'] : '', 20);
            $kpp     = mokko_str(isset($p['kpp']) ? $p['kpp'] : '', 20);
            $ltd     = mokko_str(isset($p['ltd']) ? $p['ltd'] : '', 255);
            $phone   = mokko_phone_clean(mokko_str(isset($p['phone']) ? $p['phone'] : '', 20));
            $inn     = preg_replace('/\s+/', '', $inn);
            $kpp     = preg_replace('/\s+/', '', $kpp);

            if (!mokko_place_owned($pdo, $uid, $id)) {
                mokko_json(array('error' => 'not found'));
            }
            if ($name === '' || $address === '') {
                mokko_json(array('error' => 'name and address required'));
            }
            $err = mokko_requisites_error($inn, $kpp, $phone);
            if ($err !== '') {
                mokko_json(array('error' => $err));
            }

            $s = $pdo->prepare('UPDATE places SET name = ?, address = ?, inn = ?, kpp = ?, ltd = ?, phone = ? WHERE id = ? AND user_id = ?');
            $s->execute(array($name, $address, $inn, $kpp, $ltd, $phone, $id, $uid));

            mokko_json(array(
                'Id' => $id, 'id' => $id, 'name' => $name, 'address' => $address,
                'inn' => $inn, 'kpp' => $kpp, 'ltd' => $ltd, 'phone' => $phone,
            ));
            break;

        default:
            mokko_json(array('error' => 'unknown action'));
    }
}

// ─── staff ──────────────────────────────────────────────────────────────────
function handleStaff()
{
    global $pdo, $uid, $action, $p;

    switch ($action) {
        case 'load':
            $s = $pdo->prepare('SELECT id, name, lastname, email AS login, phone, rate FROM staff WHERE uid = ? ORDER BY id');
            $s->execute(array($uid));
            mokko_json($s->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'new':
            $password = isset($p['pass']) ? (string)$p['pass'] : '';
            if (!mokko_password_ok($password)) {
                mokko_json(array('error' => 'password must be 8-72 characters'));
            }
            $name     = mokko_str(isset($p['name']) ? $p['name'] : '', 100);
            $lastname = mokko_str(isset($p['lastname']) ? $p['lastname'] : '', 100);
            $login    = mokko_str(isset($p['login']) ? $p['login'] : '', 254);
            $phone    = mokko_str(isset($p['phone']) ? $p['phone'] : '', 20);
            $rate     = (float)(isset($p['rate']) ? $p['rate'] : 0);

            $emailErr = mokko_staff_email_error($pdo, $login);
            if ($emailErr !== '') {
                mokko_json(array('error' => $emailErr));
            }

            $s = $pdo->prepare('INSERT INTO staff (uid, name, lastname, email, phone, rate, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $s->execute(array($uid, $name, $lastname, $login, $phone, $rate, password_hash($password, PASSWORD_DEFAULT)));
            $id = (int)$pdo->lastInsertId();

            mokko_json(array(
                'Id' => $id, 'name' => $name, 'lastname' => $lastname,
                'login' => $login, 'phone' => $phone, 'rate' => $rate,
            ));
            break;

        case 'edit':
            $id       = (int)(isset($p['id']) ? $p['id'] : 0);
            $name     = mokko_str(isset($p['name']) ? $p['name'] : '', 100);
            $lastname = mokko_str(isset($p['lastname']) ? $p['lastname'] : '', 100);
            $login    = mokko_str(isset($p['login']) ? $p['login'] : '', 254);
            $phone    = mokko_str(isset($p['phone']) ? $p['phone'] : '', 20);
            $rate     = (float)(isset($p['rate']) ? $p['rate'] : 0);

            $emailErr = mokko_staff_email_error($pdo, $login, $id);
            if ($emailErr !== '') {
                mokko_json(array('error' => $emailErr));
            }

            $s = $pdo->prepare('UPDATE staff SET name = ?, lastname = ?, email = ?, phone = ?, rate = ? WHERE id = ? AND uid = ?');
            $s->execute(array($name, $lastname, $login, $phone, $rate, $id, $uid));

            mokko_json(array(
                'Id' => $id, 'name' => $name, 'lastname' => $lastname,
                'login' => $login, 'phone' => $phone, 'rate' => $rate,
            ));
            break;

        case 'del':
            $id = (int)(isset($p['id']) ? $p['id'] : 0);
            $pdo->prepare('DELETE FROM staff WHERE id = ? AND uid = ?')->execute(array($id, $uid));
            mokko_json(array('Id' => $id));
            break;

        case 'newpass':
            $id       = (int)(isset($p['id']) ? $p['id'] : 0);
            $password = isset($p['password']) ? (string)$p['password'] : '';
            if (!mokko_password_ok($password)) {
                mokko_json(array('error' => 'password must be 8-72 characters'));
            }
            $pdo->prepare('UPDATE staff SET password_hash = ? WHERE id = ? AND uid = ?')
                ->execute(array(password_hash($password, PASSWORD_DEFAULT), $id, $uid));
            mokko_json(array('success' => true));
            break;

        case 'newpassword':
            // Смена собственного пароля. Ограничиваем подбор старого пароля:
            // без лимита украденная сессия превращалась в оракул.
            mokko_rate_limit_guard('pwchg_' . (int)$uid, 5, 900);

            $old = isset($p['old_password']) ? (string)$p['old_password'] : '';
            $new = isset($p['password']) ? (string)$p['password'] : '';

            global $staffId;
            if ($staffId > 0) {
                $u = $pdo->prepare('SELECT password_hash FROM staff WHERE id = ? AND uid = ? LIMIT 1');
                $u->execute(array($staffId, $uid));
                $row = $u->fetch(PDO::FETCH_ASSOC);
                if (!$row || !password_verify($old, (string)$row['password_hash'])) {
                    mokko_json(array('error' => 'wrong password'));
                }
                if (!mokko_password_ok($new)) {
                    mokko_json(array('error' => 'password must be 8-72 characters'));
                }
                $newHash = password_hash($new, PASSWORD_DEFAULT);
                $pdo->prepare('UPDATE staff SET password_hash = ? WHERE id = ? AND uid = ?')
                    ->execute(array($newHash, $staffId, $uid));
                $_SESSION['pv'] = mokko_password_fingerprint($newHash);
                mokko_rate_limit_clear('pwchg_' . (int)$uid);
                mokko_json(array('success' => true));
            }

            $u = $pdo->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
            $u->execute(array($uid));
            $row = $u->fetch(PDO::FETCH_ASSOC);

            if (!$row || !password_verify($old, (string)$row['password_hash'])) {
                mokko_json(array('error' => 'wrong password'));
            }
            if (!mokko_password_ok($new)) {
                mokko_json(array('error' => 'password must be 8-72 characters'));
            }

            $newHash = password_hash($new, PASSWORD_DEFAULT);
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute(array($newHash, $uid));
            $_SESSION['pv'] = mokko_password_fingerprint($newHash);
            mokko_rate_limit_clear('pwchg_' . (int)$uid);
            mokko_json(array('success' => true));
            break;

        default:
            mokko_json(array('error' => 'unknown action'));
    }
}

// ─── category ────────────────────────────────────────────────────────────────
function handleCategory()
{
    global $pdo, $uid, $action, $p;

    switch ($action) {
        case 'load':
            $s = $pdo->prepare('SELECT id, name, status FROM categories WHERE uid = ? ORDER BY id');
            $s->execute(array($uid));
            mokko_json($s->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'new':
            $name   = mokko_str(isset($p['name']) ? $p['name'] : 'Название', 255);
            $status = (isset($p['status']) && $p['status'] === 'off') ? 'off' : 'on';

            $name = ($name !== '' ? $name : 'Название');
            $s = $pdo->prepare('INSERT INTO categories (uid, name, status) VALUES (?, ?, ?)');
            $s->execute(array($uid, $name, $status));
            $id = (int)$pdo->lastInsertId();

            mokko_json(array('Id' => $id, 'name' => $name, 'status' => $status));
            break;

        case 'edit':
            $id     = (int)(isset($p['category']) ? $p['category'] : 0);
            $name   = mokko_str(isset($p['name']) ? $p['name'] : '', 255);
            $status = (isset($p['status']) && $p['status'] === 'off') ? 'off' : 'on';

            $pdo->prepare('UPDATE categories SET name = ?, status = ? WHERE id = ? AND uid = ?')
                ->execute(array($name, $status, $id, $uid));
            mokko_json(array('name' => $name, 'status' => $status));
            break;

        case 'del':
            $id = (int)(isset($p['category']) ? $p['category'] : 0);
            $pdo->prepare('DELETE FROM categories WHERE id = ? AND uid = ?')->execute(array($id, $uid));
            $pdo->prepare('DELETE FROM items WHERE category_id = ? AND uid = ?')->execute(array($id, $uid));
            mokko_json(array('success' => true));
            break;

        case 'status':
            $id     = (int)(isset($p['category']) ? $p['category'] : 0);
            $status = (isset($p['status']) && $p['status'] === 'off') ? 'off' : 'on';
            $pdo->prepare('UPDATE categories SET status = ? WHERE id = ? AND uid = ?')
                ->execute(array($status, $id, $uid));
            mokko_json(array('success' => true));
            break;

        default:
            mokko_json(array('error' => 'unknown action'));
    }
}

// ─── items (bulk load) ──────────────────────────────────────────────────────
function handleItems()
{
    global $pdo, $uid, $action, $p;

    if ($action !== 'load') {
        mokko_json(array('error' => 'unknown action'));
    }

    $cat = isset($p['category']) ? $p['category'] : 'all';

    if ($cat === 'all') {
        $s = $pdo->prepare('SELECT i.id, i.name, i.price, i.mass, i.em, i.category_id AS type FROM items i WHERE i.uid = ? ORDER BY i.id');
        $s->execute(array($uid));
    } else {
        $s = $pdo->prepare('SELECT i.id, i.name, i.price, i.mass, i.em, i.category_id AS type FROM items i WHERE i.uid = ? AND i.category_id = ? ORDER BY i.id');
        $s->execute(array($uid, (int)$cat));
    }

    mokko_json($s->fetchAll(PDO::FETCH_ASSOC));
}

// ─── item (single CRUD) ─────────────────────────────────────────────────────
function handleItem()
{
    global $pdo, $uid, $action, $p;

    switch ($action) {
        case 'new':
            $cat = (int)(isset($p['category']) ? $p['category'] : 0);
            $s = $pdo->prepare('INSERT INTO items (uid, category_id, name, price, mass, em, ingredients) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $s->execute(array($uid, $cat, 'Название', 0, 0, 1, '[]'));
            $id = (int)$pdo->lastInsertId();
            mokko_json(array('Id' => $id, 'mass' => 0, 'em' => 1));
            break;

        case 'edit':
            $id    = (int)(isset($p['id']) ? $p['id'] : 0);
            $name  = mokko_str(isset($p['name']) ? $p['name'] : '', 255);
            $price = max(0, (float)(isset($p['price']) ? $p['price'] : 0));
            $mass  = max(0, (float)(isset($p['mass']) ? $p['mass'] : 0));
            $em    = (int)(isset($p['em']) ? $p['em'] : 1);
            $ing   = isset($p['ingredients']) ? $p['ingredients'] : array();
            if (!is_array($ing)) {
                $ing = array();
            }

            // rowCount() для UPDATE не отличает «нет строки» от «значения не изменились»,
            // поэтому принадлежность проверяем отдельным SELECT.
            $own = $pdo->prepare('SELECT id FROM items WHERE id = ? AND uid = ? LIMIT 1');
            $own->execute(array($id, $uid));
            if (!$own->fetch(PDO::FETCH_ASSOC)) {
                mokko_json(array('error' => 'not found'));
            }

            $s = $pdo->prepare('UPDATE items SET category_id = ?, name = ?, price = ?, mass = ?, em = ?, ingredients = ? WHERE id = ? AND uid = ?');
            $s->execute(array(
                (int)(isset($p['category']) ? $p['category'] : 0),
                $name, $price, $mass, $em,
                json_encode($ing, JSON_UNESCAPED_UNICODE),
                $id, $uid,
            ));
            mokko_json(array('Id' => $id, 'name' => $name, 'price' => $price, 'mass' => $mass, 'em' => $em));
            break;

        case 'del':
            $id = (int)(isset($p['id']) ? $p['id'] : 0);
            $pdo->prepare('DELETE FROM items WHERE id = ? AND uid = ?')->execute(array($id, $uid));
            mokko_json(array('success' => true));
            break;

        case 'get':
            $id = (int)(isset($p['id']) ? $p['id'] : 0);
            $s = $pdo->prepare('SELECT id, name, price, mass, em, ingredients FROM items WHERE id = ? AND uid = ? LIMIT 1');
            $s->execute(array($id, $uid));
            $row = $s->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                mokko_json(array('error' => 'not found'));
            }
            mokko_json($row);
            break;

        default:
            mokko_json(array('error' => 'unknown action'));
    }
}

// ─── lager ──────────────────────────────────────────────────────────────────
function handleLager()
{
    global $pdo, $uid, $action, $p;

    switch ($action) {
        case 'load':
            $s = $pdo->prepare('SELECT id, name, em, active, mass FROM lager WHERE uid = ? ORDER BY id');
            $s->execute(array($uid));
            mokko_json($s->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'new':
            $places = $pdo->prepare('SELECT id FROM places WHERE user_id = ?');
            $places->execute(array($uid));
            $mass_arr = array();
            foreach ($places->fetchAll(PDO::FETCH_ASSOC) as $pl) {
                $mass_arr[] = array('place' => (int)$pl['id'], 'mass' => 0);
            }
            if (empty($mass_arr)) {
                $mass_arr[] = array('place' => 0, 'mass' => 0);
            }
            $mass_json = json_encode($mass_arr);

            $s = $pdo->prepare('INSERT INTO lager (uid, name, em, active, mass) VALUES (?, ?, ?, ?, ?)');
            $s->execute(array($uid, 'Название', 1, 1, $mass_json));
            $id = (int)$pdo->lastInsertId();

            mokko_json(array('Id' => $id, 'mass' => $mass_json));
            break;

        case 'edit':
            $id   = (int)(isset($p['id']) ? $p['id'] : 0);
            $name = mokko_str(isset($p['name']) ? $p['name'] : '', 255);
            $pdo->prepare('UPDATE lager SET name = ?, em = ? WHERE id = ? AND uid = ?')
                ->execute(array($name, (int)(isset($p['em']) ? $p['em'] : 1), $id, $uid));
            mokko_json(array('success' => true));
            break;

        case 'add':
        case 'substract':
            $id    = (int)(isset($p['id']) ? $p['id'] : 0);
            $place = (int)(isset($p['place']) ? $p['place'] : 0);
            $diff  = (float)(isset($p['mass']) ? $p['mass'] : 0);

            // Склад ведётся по конкретной кофейне: нельзя писать остатки
            // в place, который не принадлежит текущему пользователю.
            if (!mokko_place_owned($pdo, $uid, $place)) {
                mokko_json(array('error' => 'invalid place'));
            }

            $row = $pdo->prepare('SELECT mass FROM lager WHERE id = ? AND uid = ? LIMIT 1');
            $row->execute(array($id, $uid));
            $r = $row->fetch(PDO::FETCH_ASSOC);
            if (!$r) {
                mokko_json(array('error' => 'not found'));
            }

            $mass_arr = json_decode((string)$r['mass'], true);
            if (!is_array($mass_arr)) {
                $mass_arr = array();
            }

            $found = false;
            foreach ($mass_arr as &$m) {
                if ((int)$m['place'] === $place) {
                    $m['mass'] = ($action === 'add') ? ($m['mass'] + $diff) : ($m['mass'] - $diff);
                    $found = true;
                    break;
                }
            }
            unset($m);
            if (!$found) {
                $mass_arr[] = array('place' => $place, 'mass' => ($action === 'add') ? $diff : -$diff);
            }

            $mass_json = json_encode($mass_arr);
            $pdo->prepare('UPDATE lager SET mass = ? WHERE id = ? AND uid = ?')->execute(array($mass_json, $id, $uid));
            mokko_json(array('mass' => $mass_json));
            break;

        case 'remove':
            $id = (int)(isset($p['id']) ? $p['id'] : 0);
            $pdo->prepare('UPDATE lager SET active = 0 WHERE id = ? AND uid = ?')->execute(array($id, $uid));
            mokko_json(array('success' => true));
            break;

        default:
            mokko_json(array('error' => 'unknown action'));
    }
}

// ─── guests ─────────────────────────────────────────────────────────────────
function handleGuests()
{
    global $pdo, $uid, $action, $p;

    switch ($action) {
        case 'load':
            $place = (int)(isset($p['place']) ? $p['place'] : 0);
            $s = $pdo->prepare('SELECT id, name, phone, items, price FROM guests WHERE uid = ? AND place_id = ? AND status = "open" ORDER BY id DESC');
            $s->execute(array($uid, $place));
            mokko_json($s->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'new':
            $place = (int)(isset($p['place']) ? $p['place'] : 0);
            $name  = mokko_str(isset($p['name']) ? $p['name'] : '', 255);

            if (!mokko_place_owned($pdo, $uid, $place)) {
                mokko_json(array('error' => 'invalid place'));
            }

            $s = $pdo->prepare('INSERT INTO guests (uid, place_id, name, items, price, status) VALUES (?, ?, ?, ?, ?, ?)');
            $s->execute(array($uid, $place, $name, '[]', 0, 'open'));
            $id = (int)$pdo->lastInsertId();

            mokko_json(array('Id' => $id, 'name' => $name, 'phone' => '', 'items' => '[]', 'price' => 0));
            break;

        case 'edit':
            $id    = (int)(isset($p['id']) ? $p['id'] : 0);
            $items = isset($p['items']) ? $p['items'] : '[]';
            $price = max(0, (float)(isset($p['price']) ? $p['price'] : 0));

            if (is_array($items)) {
                $items = json_encode($items, JSON_UNESCAPED_UNICODE);
            } elseif (!is_string($items)) {
                $items = '[]';
            }
            if (strlen($items) > 65535) {
                mokko_json(array('error' => 'too large'));
            }

            $pdo->prepare('UPDATE guests SET items = ?, price = ? WHERE id = ? AND uid = ?')
                ->execute(array($items, $price, $id, $uid));
            mokko_json(array('success' => true));
            break;

        case 'update':
            $id   = (int)(isset($p['id']) ? $p['id'] : 0);
            $name = mokko_str(isset($p['name']) ? $p['name'] : '', 255);
            $pdo->prepare('UPDATE guests SET name = ? WHERE id = ? AND uid = ?')->execute(array($name, $id, $uid));
            mokko_json(array('success' => true));
            break;

        case 'get':
            $id = (int)(isset($p['id']) ? $p['id'] : 0);
            $s = $pdo->prepare('SELECT id, name, phone, items, price FROM guests WHERE id = ? AND uid = ? LIMIT 1');
            $s->execute(array($id, $uid));
            $row = $s->fetch(PDO::FETCH_ASSOC);
            mokko_json($row ? $row : array('error' => 'not found'));
            break;

        default:
            mokko_json(array('error' => 'unknown action'));
    }
}

// ─── startshift ─────────────────────────────────────────────────────────────
function handleStartShift()
{
    global $pdo, $uid, $p;

    $place = (int)(isset($p['place']) ? $p['place'] : 0);
    if (!mokko_place_owned($pdo, $uid, $place)) {
        mokko_json(array('error' => 'invalid place'));
    }

    // Продолжаем уже открытую смену (перезагрузка страницы, второе устройство),
    // а не плодим новые: раньше каждая загрузка кассы открывала ещё одну смену.
    // Смены старше 20 часов считаем забытыми и не продолжаем.
    // Продолжать можно только смену, открытую ПОСЛЕ последней закрытой: старые
    // «висящие» смены (раньше каждая перезагрузка кассы создавала новую) иначе
    // всплывали после закрытия текущей, и новая смена начиналась не с нуля.
    // У каждого — владельца и каждого бариста — своя смена.
    global $staffId;
    mokko_shifts_ready($pdo);
    // Условие на сотрудника — целым числом прямо в SQL ($staffId приведён к int):
    // так сравнение одинаково работает в MySQL и SQLite.
    $who = mokko_shift_owner_sql($staffId);
    $s = $pdo->prepare('SELECT MAX(id) FROM shifts WHERE uid = ? AND place_id = ? AND ' . $who . ' AND closed_at IS NOT NULL');
    $s->execute(array($uid, $place));
    $lastClosed = (int)$s->fetchColumn();

    $s = $pdo->prepare('SELECT id, created_at, NOW() AS now_ts FROM shifts WHERE uid = ? AND place_id = ? AND ' . $who . ' AND closed_at IS NULL AND id > ? ORDER BY id DESC LIMIT 1');
    $s->execute(array($uid, $place, $lastClosed));
    $open = $s->fetch(PDO::FETCH_ASSOC);
    if ($open) {
        $elapsed = strtotime((string)$open['now_ts']) - strtotime((string)$open['created_at']);
        if ($elapsed >= 0 && $elapsed < 20 * 3600) {
            mokko_json(array('id' => (int)$open['id'], 'elapsed' => (int)$elapsed, 'resumed' => true));
        }
    }

    $s = $pdo->prepare('INSERT INTO shifts (uid, place_id, staff_id) VALUES (?, ?, ?)');
    $s->execute(array($uid, $place, $staffId > 0 ? $staffId : null));
    mokko_json(array('id' => (int)$pdo->lastInsertId(), 'elapsed' => 0, 'resumed' => false));
}

// ─── shift (close) ──────────────────────────────────────────────────────────
function handleShift()
{
    global $pdo, $uid, $p;

    $shift_id = (int)(isset($p['shift']) ? $p['shift'] : 0);
    $place    = (int)(isset($p['place']) ? $p['place'] : 0);
    // Касса не знает номер смены (startshift не успел ответить) — закрываем
    // последнюю открытую смену этой кофейни, а не «смену 0», которой нет.
    global $staffId;
    mokko_shifts_ready($pdo);
    if ($shift_id <= 0 && $place > 0) {
        $s = $pdo->prepare('SELECT id FROM shifts WHERE uid = ? AND place_id = ? AND ' . mokko_shift_owner_sql($staffId) . ' AND closed_at IS NULL ORDER BY id DESC LIMIT 1');
        $s->execute(array($uid, $place));
        $shift_id = (int)$s->fetchColumn();
    }
    $hours    = min(24, max(0, (int)(isset($p['hours']) ? $p['hours'] : 0)));
    $min      = min(59, max(0, (int)(isset($p['min']) ? $p['min'] : 0)));
    $sec      = min(59, max(0, (int)(isset($p['sec']) ? $p['sec'] : 0)));

    $pdo->prepare('UPDATE shifts SET hours = ?, min = ?, sec = ?, closed_at = NOW() WHERE id = ? AND uid = ? AND ' . mokko_shift_owner_sql($staffId))
        ->execute(array($hours, $min, $sec, $shift_id, $uid));

    $s = $pdo->prepare('SELECT pay_method, SUM(total) AS price FROM checks WHERE shift_id = ? AND uid = ? GROUP BY pay_method');
    $s->execute(array($shift_id, $uid));
    $rows = $s->fetchAll(PDO::FETCH_ASSOC);

    $result = array();
    foreach ($rows as $r) {
        $result[$r['pay_method']] = $r;
    }
    if (empty($result)) {
        $result = array(
            1 => array('pay_method' => 1, 'price' => 0),
            2 => array('pay_method' => 2, 'price' => 0),
        );
    }
    mokko_json($result);
}

// ─── buy ────────────────────────────────────────────────────────────────────
function handleBuy()
{
    global $pdo, $uid, $action, $p;

    if ($action !== 'check') {
        mokko_json(array('error' => 'unknown action'));
    }

    $place    = (int)(isset($p['place']) ? $p['place'] : 0);
    $shift_id = (int)(isset($p['shift']) ? $p['shift'] : 0);
    $payed    = isset($p['payed']) && is_array($p['payed']) ? $p['payed'] : array();

    if (!mokko_place_owned($pdo, $uid, $place)) {
        mokko_json(array('error' => 'invalid place'));
    }

    $pay_method = 1;
    if (!empty($payed) && isset($payed[0][3])) {
        $pay_method = (int)$payed[0][3];
    }
    if ($pay_method !== 1 && $pay_method !== 2) {
        $pay_method = 1;
    }

    // Сумма пересчитывается по ценам из БД — клиентское значение total
    // больше не принимается на веру.
    $itemIds = array();
    foreach ($payed as $it) {
        if (is_array($it) && isset($it[0])) {
            $iid = (int)$it[0];
            if ($iid > 0) {
                $itemIds[$iid] = true;
            }
        }
    }

    $prices = array();
    if (!empty($itemIds)) {
        $ids = implode(',', array_map('intval', array_keys($itemIds)));
        $ps  = $pdo->prepare("SELECT id, price FROM items WHERE uid = ? AND id IN ($ids)");
        $ps->execute(array($uid));
        foreach ($ps->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $prices[(int)$row['id']] = (float)$row['price'];
        }
    }

    $calc = 0.0;
    foreach ($payed as $it) {
        if (!is_array($it) || !isset($it[0])) {
            continue;
        }
        $iid = (int)$it[0];
        $qty = isset($it[1]) ? (int)$it[1] : 0;
        if ($qty <= 0) {
            continue;
        }
        if (isset($prices[$iid])) {
            $calc += $prices[$iid] * $qty;
        } else {
            // Позиция удалена из меню — используем переданную сумму строки.
            $calc += isset($it[4]) ? (float)$it[4] : 0;
        }
    }
    $total = round($calc, 2);

    $s = $pdo->prepare('INSERT INTO checks (uid, place_id, shift_id, items, total, pay_method) VALUES (?, ?, ?, ?, ?, ?)');
    $s->execute(array(
        $uid, $place, $shift_id,
        json_encode($payed, JSON_UNESCAPED_UNICODE),
        $total, $pay_method,
    ));
    $check_id = (int)$pdo->lastInsertId();

    if (!empty($payed[0][2])) {
        $guest_id = (int)$payed[0][2];
        $pdo->prepare('UPDATE guests SET status = "paid" WHERE id = ? AND uid = ?')->execute(array($guest_id, $uid));
    }

    mokko_json(array('Id' => $check_id));
}

// ─── user ───────────────────────────────────────────────────────────────────
function handleUser()
{
    global $pdo, $uid, $action, $p;

    switch ($action) {
        case 'unlock':
            // Проверка пароля — тоже оракул: 10 попыток на пользователя за 5 минут.
            mokko_rate_limit_guard('pwchk_' . (int)$uid, 10, 300);
            $pass = isset($p['password']) ? (string)$p['password'] : '';
            $u = $pdo->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
            $u->execute(array($uid));
            $row = $u->fetch(PDO::FETCH_ASSOC);

            mokko_json(array('unlock' => ($row && password_verify($pass, (string)$row['password_hash'])) ? 'true' : 'false'));
            break;

        case 'newpassword':
            mokko_rate_limit_guard('pwchg_' . (int)$uid, 5, 900);
            $old = isset($p['old_password']) ? (string)$p['old_password'] : '';
            $new = isset($p['password']) ? (string)$p['password'] : '';

            $u = $pdo->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
            $u->execute(array($uid));
            $row = $u->fetch(PDO::FETCH_ASSOC);

            if (!$row || !password_verify($old, (string)$row['password_hash'])) {
                mokko_json(array('error' => 'wrong password'));
            }
            if (!mokko_password_ok($new)) {
                mokko_json(array('error' => 'password must be 8-72 characters'));
            }

            $newHash = password_hash($new, PASSWORD_DEFAULT);
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute(array($newHash, $uid));
            $_SESSION['pv'] = mokko_password_fingerprint($newHash);
            mokko_rate_limit_clear('pwchg_' . (int)$uid);
            mokko_json(array('success' => true));
            break;

        case 'newphone':
            $phone = mokko_phone_clean(mokko_str(isset($p['phone']) ? $p['phone'] : '', 24));
            if ($phone !== '' && !preg_match('/^\+?\d{10,15}$/', $phone)) {
                mokko_json(array('error' => 'invalid phone'));
            }
            $pdo->prepare('UPDATE users SET phone = ? WHERE id = ?')->execute(array($phone === '' ? null : $phone, $uid));
            mokko_json(array('success' => true, 'phone' => $phone));
            break;

        case 'newname':
            $name = mokko_str(isset($p['newname']) ? $p['newname'] : '', 100);
            $pdo->prepare('UPDATE users SET name = ? WHERE id = ?')->execute(array($name, $uid));
            mokko_json(array('success' => true));
            break;

        default:
            mokko_json(array('error' => 'unknown action'));
    }
}

// ─── stat ───────────────────────────────────────────────────────────────────
function handleStat()
{
    global $pdo, $uid, $action, $p;

    $place      = (int)(isset($p['place']) ? $p['place'] : 0);
    $start_date = mokko_date(isset($p['start_date']) ? $p['start_date'] : '', date('Y-m-d'));
    $stop_date  = mokko_date(isset($p['stop_date']) ? $p['stop_date'] : '', date('Y-m-d'));

    switch ($action) {
        case 'check':
            $s = $pdo->prepare("SELECT DATE(created_at) AS day, SUM(total) AS proceeds, AVG(total) AS a_b FROM checks WHERE uid = ? AND place_id = ? AND DATE(created_at) BETWEEN ? AND ? GROUP BY day ORDER BY day");
            $s->execute(array($uid, $place, $start_date, $stop_date));

            $rows = array();
            foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $rows[$r['day']] = array('proceeds' => (float)$r['proceeds'], 'a_b' => (float)$r['a_b']);
            }
            mokko_json($rows);
            break;

        case 'sales':
            $s = $pdo->prepare("SELECT c.items FROM checks c WHERE c.uid = ? AND c.place_id = ? AND DATE(c.created_at) BETWEEN ? AND ?");
            $s->execute(array($uid, $place, $start_date, $stop_date));

            $totals = array();
            foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $check) {
                $items = json_decode((string)$check['items'], true);
                if (!is_array($items)) {
                    continue;
                }
                foreach ($items as $it) {
                    if (!is_array($it) || !isset($it[0])) {
                        continue;
                    }
                    $item_id = (int)$it[0];
                    $qty     = isset($it[1]) ? (int)$it[1] : 1;
                    $price   = isset($it[4]) ? (float)$it[4] : 0;

                    if (!isset($totals[$item_id])) {
                        $totals[$item_id] = array('name' => 'Item #' . $item_id, 'q' => 0, 'total' => 0);
                    }
                    $totals[$item_id]['q']     += $qty;
                    $totals[$item_id]['total'] += $price;
                }
            }

            if (!empty($totals)) {
                $ids = implode(',', array_map('intval', array_keys($totals)));
                $ns  = $pdo->prepare("SELECT id, name FROM items WHERE uid = ? AND id IN ($ids)");
                $ns->execute(array($uid));
                foreach ($ns->fetchAll(PDO::FETCH_ASSOC) as $it) {
                    if (isset($totals[(int)$it['id']])) {
                        $totals[(int)$it['id']]['name'] = $it['name'];
                    }
                }
            }
            mokko_json(array_values($totals));
            break;

        case 'staff':
            // Одна строка на человека: часы всех закрытых смен за период складываются.
            // Пустые смены (меньше минуты — раньше их создавала каждая перезагрузка кассы)
            // в число смен не идут.
            mokko_shifts_ready($pdo);
            $s = $pdo->prepare("SELECT sh.uid, COALESCE(sh.staff_id, 0) AS staff_id,
                    SUM(sh.hours * 3600 + sh.min * 60 + sh.sec) AS secs,
                    SUM(CASE WHEN sh.hours * 3600 + sh.min * 60 + sh.sec >= 60 THEN 1 ELSE 0 END) AS shifts
                FROM shifts sh
                WHERE sh.uid = ? AND sh.place_id = ? AND DATE(sh.created_at) BETWEEN ? AND ? AND sh.closed_at IS NOT NULL
                GROUP BY sh.uid, COALESCE(sh.staff_id, 0)");
            $s->execute(array($uid, $place, $start_date, $stop_date));

            $result = array();
            foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $secs = (int)$row['secs'];
                if ($secs < 60) { continue; }
                $h = $secs / 3600;

                if ((int)$row['staff_id'] > 0) {
                    // Смены бариста: имя и ставка — из его карточки
                    $u = $pdo->prepare('SELECT name, lastname, email, rate FROM staff WHERE id = ? AND uid = ? LIMIT 1');
                    $u->execute(array($row['staff_id'], $uid));
                } else {
                    $u = $pdo->prepare('SELECT name, lastname, email, NULL AS rate FROM users WHERE id = ? LIMIT 1');
                    $u->execute(array($row['uid']));
                }
                $urow = $u->fetch(PDO::FETCH_ASSOC);
                if (!$urow && (int)$row['staff_id'] > 0) {
                    $urow = array('name' => 'Удалённый сотрудник', 'lastname' => '', 'email' => '', 'rate' => 0);
                }

                // Ставка — из карточки сотрудника с тем же логином, если она есть
                $rate = ($urow && $urow['rate'] !== null) ? (float)$urow['rate'] : 0.0;
                if ($urow && $urow['rate'] === null && $urow['email'] !== '') {
                    $r = $pdo->prepare('SELECT rate FROM staff WHERE uid = ? AND email = ? LIMIT 1');
                    $r->execute(array($uid, $urow['email']));
                    $rate = (float)$r->fetchColumn();
                }
                $name = $urow ? trim((string)$urow['name']) : '';
                $last = $urow ? trim((string)$urow['lastname']) : '';
                // Имя в профиле часто уже записано полностью — фамилию не дублируем
                if ($last !== '' && mb_stripos($name, $last, 0, 'UTF-8') === false) { $name = trim($name . ' ' . $last); }

                $result[] = array(
                    'name'   => $name !== '' ? $name : 'Без имени',
                    'h'      => round($h, 2),
                    'secs'   => $secs,
                    'shifts' => (int)$row['shifts'],
                    'rate'   => round($h * $rate, 2),
                );
            }
            mokko_json($result);
            break;

        default:
            mokko_json(array('error' => 'unknown action'));
    }
}

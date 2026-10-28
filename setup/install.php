<?php
/**
 * Установщик MOKKO CRM.
 *
 * Открывается автоматически, пока система не установлена (см. index.php).
 * Доступы к MySQL вводятся вручную: на стороннем сервере они отличаются
 * от локальных. После установки сохраняются в config/db.local.php,
 * откуда их читает config/db.php.
 *
 * Защита: страница блокируется, как только появляется config/db.local.php;
 * дополнительно проверяется, что в целевой базе нет пользователей.
 * Опционально можно задать MOKKO_SETUP_KEY и открывать установщик как
 * /setup/install.php?key=...
 */

require_once dirname(__DIR__) . '/config/security.php';
mokko_harden_errors();
mokko_session_start();
mokko_send_security_headers();

$setupKey = getenv('MOKKO_SETUP_KEY');
if ($setupKey !== false && $setupKey !== '') {
    $given = isset($_GET['key']) ? (string)$_GET['key'] : '';
    if (!hash_equals((string)$setupKey, $given)) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Forbidden: укажите ?key=... из MOKKO_SETUP_KEY';
        exit;
    }
}

$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string)$_SERVER['REQUEST_METHOD']) : 'GET';
if ($method !== 'GET' && $method !== 'POST') {
    http_response_code(405);
    header('Allow: GET, POST');
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Method Not Allowed';
    exit;
}

$scriptDir = rtrim(str_replace('\\', '/', dirname(dirname(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/'))), '/');
$loginUrl  = $scriptDir . '/login';
$localConfig = dirname(__DIR__) . '/config/db.local.php';

// ── Оформление в духе установщиков 2000-х ───────────────────────────────────

function setup_css()
{
    return <<<'CSS'
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; }
body {
    background: #d4d0c8;
    color: #000;
    font: 12px Tahoma, Verdana, Arial, sans-serif;
    padding: 18px 12px 28px;
}
a { color: #003399; }
a:visited { color: #551a8b; }

.page { width: 820px; max-width: 100%; margin: 0 auto; }

.page-head { display: flex; align-items: center; padding: 4px 0 10px; }
.page-head h1 { margin: 0; font: bold 19px Tahoma, Verdana, Arial, sans-serif; letter-spacing: -.2px; }
.page-head h1 span { color: #0a246a; }
.page-head .sub { margin-top: 2px; font: 11px Tahoma, sans-serif; color: #555; }
hr.rule { border: 0; border-top: 1px solid #808080; border-bottom: 1px solid #fff; margin: 0 0 12px; }

.content { display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-start; }
.content.single { display: block; }

.side {
    width: 208px;
    flex: 0 0 208px;
    background: #fff;
    border: 1px solid #808080;
    padding: 12px 12px 10px;
}
.disk { text-align: center; margin: 2px 0 6px; }
.disk svg { display: block; margin: 0 auto; }
.side-cap { text-align: center; font: 10px Tahoma, sans-serif; color: #555; line-height: 1.5; }
.side-cap b { color: #000; font-size: 11px; }

.steps { list-style: none; margin: 12px 0 0; padding: 8px 0 0; border-top: 1px solid #c8c4bc; }
.steps li { display: flex; align-items: flex-start; gap: 6px; padding: 3px 0; font: 11px Tahoma, sans-serif; color: #444; }
.steps li b {
    flex: 0 0 15px;
    width: 15px; height: 15px;
    display: inline-flex; align-items: center; justify-content: center;
    background: #d4d0c8;
    border: 1px solid;
    border-color: #fff #404040 #404040 #fff;
    font: 9px Tahoma, sans-serif;
    color: #333;
}
.steps li.on { font-weight: bold; color: #000; }
.steps li.on b { background: #0a246a; color: #fff; border-color: #6f8fc0 #000 #000 #6f8fc0; }
.seg { display: flex; gap: 2px; margin-top: 10px; }
.seg i { flex: 1; height: 8px; background: #fff; border: 1px solid #808080; }
.seg i.on { background: #0a246a; }

.main { flex: 1 1 auto; min-width: 0; }

.group { border: 1px solid #808080; margin: 0 0 12px; padding: 10px 12px 12px; }
.group-title {
    margin: -10px -12px 10px;
    padding: 3px 8px;
    background: #d4d0c8;
    border-bottom: 1px solid #808080;
    font: bold 11px Tahoma, sans-serif;
}
label { display: block; margin: 8px 0 3px; font: 11px Tahoma, sans-serif; }
label.first { margin-top: 0; }
input[type=text], input[type=password] {
    width: 100%;
    padding: 3px 4px;
    background: #fff;
    border: 2px inset #d4d0c8;
    font: 12px Tahoma, sans-serif;
    color: #000;
}
input:focus { outline: 1px dotted #000; outline-offset: -3px; }
.row { display: flex; gap: 10px; }
.row > div { flex: 1; min-width: 0; }
.row .narrow { flex: 0 0 110px; }
.hint { margin-top: 3px; font: 10px Tahoma, sans-serif; color: #555; line-height: 1.4; }
code { font: 10px "Courier New", monospace; background: #f0ede6; border: 1px solid #d8d3c8; padding: 0 3px; }

.status {
    background: #fff;
    border: 2px inset #d4d0c8;
    padding: 7px 9px;
    margin: 0 0 12px;
    font: 11px Tahoma, sans-serif;
    max-height: 170px;
    overflow: auto;
}
.status .line { padding: 2px 0; line-height: 1.45; }
.status .line.ok { color: #006000; }
.status .line.err { color: #a00000; }

.actions { display: flex; justify-content: flex-end; gap: 8px; padding-top: 2px; }
button, .btn {
    display: inline-block;
    min-width: 96px;
    padding: 4px 14px;
    background: #d4d0c8;
    border: 2px solid;
    border-color: #fff #404040 #404040 #fff;
    font: 11px Tahoma, sans-serif;
    color: #000;
    text-align: center;
    text-decoration: none;
    cursor: pointer;
}
button:active, .btn:active { border-color: #404040 #fff #fff #404040; }
button.primary { font-weight: bold; }
button:focus, .btn:focus { outline: 1px dotted #000; outline-offset: -4px; }

.foot {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    margin-top: 16px;
    padding-top: 6px;
    border-top: 1px solid #b8b3a9;
    font: 10px Tahoma, sans-serif;
    color: #666;
}

.msgbox { display: flex; gap: 14px; padding: 14px 16px; background: #fff; border: 1px solid #808080; }
.msgbox .icon { flex: 0 0 64px; }
.msgbox h2 { margin: 0 0 8px; font: bold 12px Tahoma, sans-serif; }
.msgbox p { margin: 0 0 8px; font: 11px Tahoma, sans-serif; line-height: 1.55; }
.msgbox ul { margin: 0 0 10px; padding-left: 18px; font: 11px Tahoma, sans-serif; line-height: 1.5; }
.msgbox .warn { color: #7a4a00; }
.msgbox .err { color: #a00000; }

.gallery-block { flex: 1 0 100%; margin-top: 18px; padding-top: 12px; border-top: 1px solid #b8b3a9; }
.gallery-title { margin-bottom: 8px; font: bold 11px Tahoma, sans-serif; }
.gallery { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
.shot { display: block; color: #003399; text-decoration: none; }
.shot img { display: block; width: 100%; height: auto; border: 1px solid #808080; background: #111; }
.shot span { display: block; margin-top: 3px; font: 10px Tahoma, sans-serif; color: #333; }
.shot:hover img { border-color: #0a246a; }
.lightbox { position: fixed; inset: 0; z-index: 20; display: flex; align-items: center; justify-content: center; padding: 28px; background: rgba(0, 0, 0, .78); }
.lightbox[hidden] { display: none; }
.lightbox img { max-width: 96vw; max-height: 90vh; border: 2px solid #fff; background: #000; }
.lb-close { position: absolute; top: 12px; right: 16px; width: 30px; height: 28px; min-width: 0; padding: 0; font: bold 15px Tahoma, sans-serif; }

.led { animation: blink 1.1s steps(2, start) infinite; }
@keyframes blink { 50% { opacity: .2; } }

@media (max-width: 660px) {
    .content { flex-direction: column; }
    .side { width: 100%; flex: 1 1 auto; display: flex; gap: 14px; align-items: center; }
    .side .disk { margin: 0; }
    .side-cap { text-align: left; }
    .steps { flex: 1 1 auto; border-top: 0; padding-top: 0; margin-top: 0; }
    .seg { display: none; }
    .page-head h1 { font-size: 17px; }
    .gallery { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 520px) {
    .row { flex-direction: column; gap: 0; }
    .row .narrow { flex: 1 1 auto; }
    .foot span:last-child { display: none; }
    .gallery { grid-template-columns: repeat(2, 1fr); }
}
CSS;
}

/**
 * Рисует дискету. $size — сторона в пикселях.
 */
function setup_floppy_svg($size)
{
    $svg = <<<'SVG'
<svg viewBox="0 0 140 140" width="%SIZE%" height="%SIZE%" role="img" aria-label="Установочная дискета MOKKO CRM">
  <defs>
    <linearGradient id="fd-body" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#3c3c3c"/>
      <stop offset="1" stop-color="#141414"/>
    </linearGradient>
    <linearGradient id="fd-metal" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#f0f0f0"/>
      <stop offset=".55" stop-color="#b9b9b9"/>
      <stop offset="1" stop-color="#8d8d8d"/>
    </linearGradient>
  </defs>
  <rect x="6" y="6" width="128" height="128" rx="5" fill="url(#fd-body)" stroke="#000" stroke-width="2"/>
  <rect x="40" y="6" width="60" height="38" fill="url(#fd-metal)" stroke="#5f5f5f"/>
  <rect x="57" y="6" width="26" height="30" fill="#242424"/>
  <rect x="61" y="11" width="18" height="9" fill="#6f6f6f"/>
  <rect x="22" y="56" width="96" height="66" fill="#f5f2e6" stroke="#8b8678"/>
  <text x="30" y="75" font-family="Tahoma, Arial, sans-serif" font-size="9" fill="#2b2b2b">MOKKO CRM</text>
  <text x="30" y="87" font-family="Tahoma, Arial, sans-serif" font-size="7" fill="#7a7466">setup disk</text>
  <line x1="30" y1="96" x2="110" y2="96" stroke="#cdc7b6"/>
  <line x1="30" y1="104" x2="110" y2="104" stroke="#cdc7b6"/>
  <line x1="30" y1="112" x2="92" y2="112" stroke="#cdc7b6"/>
  <rect x="104" y="116" width="12" height="10" fill="#1e1e1e" stroke="#000"/>
  <circle class="led" cx="122" cy="30" r="3.2" fill="#39d353" stroke="#0b3d16"/>
</svg>
SVG;

    return str_replace('%SIZE%', (int)$size, $svg);
}

/**
 * Открывает «окно» установщика.
 */
function setup_header($title, $single = false)
{
    echo '<!DOCTYPE html><html lang="ru"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<meta name="robots" content="noindex">'
        . '<title>' . mokko_h($title) . '</title>'
        . '<style>' . setup_css() . '</style></head><body>'
        . '<div class="page">'
        . '<div class="page-head">'
        . '<div><h1>MOKKO <span>CRM</span></h1>'
        . '<div class="sub">Установка системы · версия 1.0</div></div>'
        . '</div>'
        . '<hr class="rule">'
        . '<div class="content' . ($single ? ' single' : '') . '">';
}

function setup_footer($status)
{
    echo '</div>'
        . '<div class="foot"><span>' . mokko_h($status) . '</span><span>MOKKO CRM · установка</span></div>'
        . '</div></body></html>';
}

// ── Блокировка повторной установки ──────────────────────────────────────────

if (is_file($localConfig)) {
    $dbOk = mokko_is_installed();
    header('Content-Type: text/html; charset=utf-8');
    setup_header('MOKKO CRM — установка', true);
    echo '<div class="msgbox"><div class="icon">' . setup_floppy_svg(58) . '</div><div>'
        . '<h2>Установка уже выполнена</h2>';
    if ($dbOk) {
        echo '<p>Система установлена, база доступна. Повторная установка через браузер заблокирована.</p>';
    } else {
        echo '<p class="err">Система уже настроена, но база недоступна. Проверьте доступы в <code>config/db.local.php</code>.</p>';
    }
    echo '<p><a class="btn" href="' . mokko_h($loginUrl) . '">Перейти к входу</a></p>'
        . '<p class="warn">Переустановка: удалите <code>config/db.local.php</code> и базу данных, затем обновите страницу.</p>'
        . '</div></div>';
    setup_footer('Готово');
    exit;
}

if (empty($_SESSION['setup_token'])) {
    $_SESSION['setup_token'] = bin2hex(random_bytes(16));
}
$token = $_SESSION['setup_token'];

$values = array(
    'db_host'     => 'localhost',
    'db_port'     => '3306',
    'db_name'     => 'mokko',
    'db_user'     => 'root',
    'admin_email' => 'admin@mokko.local',
    'admin_name'  => 'Администратор',
);

$errors = array();
$report = array();
$done   = false;
$dbPass = '';

/**
 * Подключение к MySQL. $withDb = false — только сервер (для CREATE DATABASE).
 */
function setup_connect($host, $port, $name, $user, $pass, $withDb)
{
    $dsn = 'mysql:host=' . $host;
    if ($port !== '') {
        $dsn .= ';port=' . (int)$port;
    }
    if ($withDb) {
        $dsn .= ';dbname=' . $name;
    }
    $dsn .= ';charset=utf8mb4';

    return new PDO($dsn, $user, $pass, array(
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ));
}

/**
 * Применяет setup/schema.sql и возвращает список таблиц.
 */
function setup_apply_schema($pdo)
{
    $schemaFile = __DIR__ . '/schema.sql';
    if (!is_file($schemaFile)) {
        throw new RuntimeException('Не найден файл схемы setup/schema.sql.');
    }
    $schema = file_get_contents($schemaFile);
    if ($schema === false) {
        throw new RuntimeException('Не удалось прочитать setup/schema.sql.');
    }

    $schema = preg_replace('/^\s*--.*$/m', '', $schema);
    $statements = preg_split('/;\s*(?:\r\n|\r|\n)/', $schema);
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if ($statement === '' || stripos($statement, 'CREATE TABLE') !== 0) {
            continue;
        }
        $pdo->exec($statement);
    }

    return $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
}

if ($method === 'POST') {
    foreach (array('db_host', 'db_port', 'db_name', 'db_user', 'admin_email', 'admin_name') as $k) {
        if (isset($_POST[$k]) && is_string($_POST[$k])) {
            $values[$k] = trim($_POST[$k]);
        }
    }
    $dbPass     = isset($_POST['db_pass']) ? (string)$_POST['db_pass'] : '';
    $adminPass  = isset($_POST['admin_pass']) ? (string)$_POST['admin_pass'] : '';
    $adminPass2 = isset($_POST['admin_pass2']) ? (string)$_POST['admin_pass2'] : '';
    $action     = isset($_POST['do']) ? (string)$_POST['do'] : 'check';

    if (!mokko_is_same_origin()) {
        $errors[] = 'Запрос пришёл с другого сайта — обновите страницу установщика.';
    }
    if (empty($_POST['token']) || !hash_equals($token, (string)$_POST['token'])) {
        $errors[] = 'Сессия устарела — обновите страницу и заполните форму заново.';
    }

    if ($values['db_host'] === '' || !preg_match('/^[A-Za-z0-9._:\-\[\]]+$/', $values['db_host'])) {
        $errors[] = 'Сервер MySQL — некорректное значение.';
    }
    if ($values['db_port'] !== '' && (!ctype_digit($values['db_port']) || (int)$values['db_port'] < 1 || (int)$values['db_port'] > 65535)) {
        $errors[] = 'Порт MySQL — число от 1 до 65535 или пусто.';
    }
    if (!preg_match('/^[A-Za-z0-9_\-]+$/', $values['db_name'])) {
        $errors[] = 'Имя базы данных — только латиница, цифры, «_» и «-».';
    }
    if ($values['db_user'] === '' || strlen($values['db_user']) > 64) {
        $errors[] = 'Пользователь MySQL обязателен (до 64 символов).';
    }
    if (strlen($dbPass) > 256) {
        $errors[] = 'Пароль MySQL слишком длинный.';
    }

    if ($action === 'install') {
        if (!filter_var($values['admin_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'E-mail администратора указан неверно.';
        }
        if ($adminPass !== $adminPass2) {
            $errors[] = 'Пароли администратора не совпадают.';
        }
        if (!mokko_password_ok($adminPass)) {
            $errors[] = 'Пароль администратора — от 8 символов (и не длиннее 72 байт).';
        }
        if (!is_writable(dirname(__DIR__) . '/config')) {
            $errors[] = 'Каталог config/ недоступен для записи — сохранить доступы к БД не получится.';
        }
    }

    if (empty($errors)) {
        try {
            $server = setup_connect($values['db_host'], $values['db_port'], $values['db_name'], $values['db_user'], $dbPass, false);
            $version = (string)$server->query('SELECT VERSION()')->fetchColumn();
            $report[] = 'Подключение к MySQL успешно, версия ' . $version;

            $stmt = $server->prepare('SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?');
            $stmt->execute(array($values['db_name']));
            $dbExists = (bool)$stmt->fetchColumn();

            if ($dbExists) {
                $db = setup_connect($values['db_host'], $values['db_port'], $values['db_name'], $values['db_user'], $dbPass, true);
                $tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
                $report[] = 'База «' . $values['db_name'] . '» найдена, таблиц: ' . count($tables);
                if (in_array('users', $tables, true)) {
                    $users = (int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn();
                    $report[] = 'Пользователей в базе: ' . $users;
                }
            } else {
                if ($action === 'check') {
                    $report[] = 'Базы «' . $values['db_name'] . '» пока нет — она будет создана при установке.';
                }
            }

            if ($action === 'check') {
                $report[] = 'Проверка пройдена. Теперь можно установить систему.';
            } else {
                if (!$dbExists) {
                    $server->exec('CREATE DATABASE IF NOT EXISTS `' . $values['db_name'] . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
                    $report[] = 'База «' . $values['db_name'] . '» создана.';
                    $db = setup_connect($values['db_host'], $values['db_port'], $values['db_name'], $values['db_user'], $dbPass, true);
                }

                $tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
                if (in_array('users', $tables, true) && (int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) {
                    throw new RuntimeException('В этой базе уже есть пользователи — установка отменена, чтобы не затереть данные.');
                }

                $tables = setup_apply_schema($db);
                foreach (array('users', 'places', 'staff', 'categories', 'items', 'lager', 'guests', 'checks', 'shifts', 'subscribers') as $table) {
                    if (!in_array($table, $tables, true)) {
                        throw new RuntimeException('Не создалась таблица ' . $table . '.');
                    }
                }
                $report[] = 'Схема применена, таблиц: ' . count($tables);

                $hash = password_hash($adminPass, PASSWORD_DEFAULT);
                $ins  = $db->prepare("INSERT INTO users (email, password_hash, name, role, status) VALUES (?, ?, ?, 'admin', 'active')");
                $ins->execute(array($values['admin_email'], $hash, mokko_str($values['admin_name'], 100)));
                $adminId = (int)$db->lastInsertId();

                $db->prepare('INSERT INTO places (user_id, name, address) VALUES (?, ?, ?)')
                    ->execute(array($adminId, 'Главная кофейня', ''));
                $report[] = 'Администратор ' . $values['admin_email'] . ' создан.';

                $config = "<?php\n"
                    . "// Создано установщиком " . date('Y-m-d H:i:s') . ". Файл содержит пароль БД — не коммитить.\n"
                    . "return array(\n"
                    . "    'host' => " . var_export($values['db_host'], true) . ",\n"
                    . "    'port' => " . var_export($values['db_port'], true) . ",\n"
                    . "    'name' => " . var_export($values['db_name'], true) . ",\n"
                    . "    'user' => " . var_export($values['db_user'], true) . ",\n"
                    . "    'pass' => " . var_export($dbPass, true) . ",\n"
                    . ");\n";

                if (@file_put_contents($localConfig, $config) === false) {
                    throw new RuntimeException('Не удалось записать config/db.local.php. Создайте файл вручную по образцу.');
                }
                $report[] = 'Доступы к БД сохранены в config/db.local.php.';
                $done = true;
            }
        } catch (Exception $e) {
            mokko_log('install: ' . $e->getMessage());
            $errors[] = 'Ошибка: ' . $e->getMessage();
        }
    }
}

// ── Вывод ───────────────────────────────────────────────────────────────────

if ($done) {
    setup_header('MOKKO CRM — установка завершена', true);
    echo '<div class="msgbox"><div class="icon">' . setup_floppy_svg(58) . '</div><div>'
        . '<h2>Установка завершена</h2>'
        . '<p>Система готова к работе. Войдите под созданным администратором:</p>'
        . '<ul>';
    foreach ($report as $line) {
        echo '<li>' . mokko_h($line) . '</li>';
    }
    echo '</ul>'
        . '<p><a class="btn" href="' . mokko_h($loginUrl) . '">Перейти к входу</a></p>'
        . '<p class="warn"><b>Удалите папку setup/</b> — после установки она не нужна.</p>'
        . '</div></div>';
    setup_footer('Установка завершена');
    exit;
}

setup_header('MOKKO CRM — установка');
?>

<div class="side">
    <div class="disk"><?= setup_floppy_svg(118) ?></div>
    <div class="side-cap">Установочный диск<br><b>MOKKO CRM</b><br>версия 1.0</div>

    <ol class="steps">
        <li class="on"><b>1</b><span>Сервер и база</span></li>
        <li><b>2</b><span>Проверка связи</span></li>
        <li><b>3</b><span>Администратор</span></li>
        <li><b>4</b><span>Готово</span></li>
    </ol>
    <div class="seg"><i class="on"></i><i></i><i></i><i></i></div>
</div>

<div class="main">
    <?php if ($errors): ?>
        <div class="status">
            <?php foreach ($errors as $e): ?>
                <div class="line err">✗ <?= mokko_h($e) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($report): ?>
        <div class="status">
            <?php foreach ($report as $line): ?>
                <div class="line ok">✓ <?= mokko_h($line) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <input type="hidden" name="token" value="<?= mokko_h($token) ?>">

        <div class="group">
            <div class="group-title">1. Сервер и база данных</div>

            <div class="row">
                <div>
                    <label class="first" for="db_host">Сервер MySQL</label>
                    <input id="db_host" type="text" name="db_host" value="<?= mokko_h($values['db_host']) ?>" required>
                    <div class="hint">Обычно <code>localhost</code> или адрес из панели хостинга</div>
                </div>
                <div class="narrow">
                    <label class="first" for="db_port">Порт</label>
                    <input id="db_port" type="text" name="db_port" value="<?= mokko_h($values['db_port']) ?>">
                </div>
            </div>

            <label for="db_name">Имя базы</label>
            <input id="db_name" type="text" name="db_name" value="<?= mokko_h($values['db_name']) ?>" required>
            <div class="hint">Если базы нет, установщик попробует создать её (нужно право CREATE).</div>

            <div class="row">
                <div>
                    <label for="db_user">Пользователь</label>
                    <input id="db_user" type="text" name="db_user" value="<?= mokko_h($values['db_user']) ?>" required>
                </div>
                <div>
                    <label for="db_pass">Пароль</label>
                    <input id="db_pass" type="password" name="db_pass" value="" autocomplete="off">
                </div>
            </div>
        </div>

        <div class="group">
            <div class="group-title">2. Администратор</div>

            <div class="row">
                <div>
                    <label class="first" for="admin_email">E-mail (он же логин)</label>
                    <input id="admin_email" type="text" name="admin_email" value="<?= mokko_h($values['admin_email']) ?>" required>
                </div>
                <div>
                    <label class="first" for="admin_name">Имя</label>
                    <input id="admin_name" type="text" name="admin_name" value="<?= mokko_h($values['admin_name']) ?>" maxlength="100">
                </div>
            </div>

            <div class="row">
                <div>
                    <label for="admin_pass">Пароль</label>
                    <input id="admin_pass" type="password" name="admin_pass" value="" autocomplete="new-password" required>
                    <div class="hint">От 8 символов</div>
                </div>
                <div>
                    <label for="admin_pass2">Повтор пароля</label>
                    <input id="admin_pass2" type="password" name="admin_pass2" value="" autocomplete="new-password" required>
                </div>
            </div>
        </div>

        <div class="actions">
            <button type="submit" name="do" value="check">Проверить подключение</button>
            <button type="submit" name="do" value="install" class="primary">Установить</button>
        </div>
    </form>
</div>

<div class="gallery-block">
        <div class="gallery-title">Как выглядит система</div>
        <div class="gallery">
            <a class="shot" href="../images/screens/01-login.png" data-full="../images/screens/01-login.png">
                <img src="../images/screens/01-login-thumb.png" alt="Экран входа" loading="lazy">
                <span>Вход</span>
            </a>
            <a class="shot" href="../images/screens/06-cashier.png" data-full="../images/screens/06-cashier.png">
                <img src="../images/screens/06-cashier-thumb.png" alt="Касса: заказ" loading="lazy">
                <span>Касса</span>
            </a>
            <a class="shot" href="../images/screens/08-bill.png" data-full="../images/screens/08-bill.png">
                <img src="../images/screens/08-bill-thumb.png" alt="Открытый счёт гостя" loading="lazy">
                <span>Открытый счёт</span>
            </a>
            <a class="shot" href="../images/screens/07-guests.png" data-full="../images/screens/07-guests.png">
                <img src="../images/screens/07-guests-thumb.png" alt="Открытые счета" loading="lazy">
                <span>Гости</span>
            </a>
            <a class="shot" href="../images/screens/03-menu.png" data-full="../images/screens/03-menu.png">
                <img src="../images/screens/03-menu-thumb.png" alt="Меню и цены" loading="lazy">
                <span>Меню</span>
            </a>
            <a class="shot" href="../images/screens/04-lager.png" data-full="../images/screens/04-lager.png">
                <img src="../images/screens/04-lager-thumb.png" alt="Склад" loading="lazy">
                <span>Склад</span>
            </a>
            <a class="shot" href="../images/screens/05-staff.png" data-full="../images/screens/05-staff.png">
                <img src="../images/screens/05-staff-thumb.png" alt="Сотрудники" loading="lazy">
                <span>Сотрудники</span>
            </a>
            <a class="shot" href="../images/screens/02-places.png" data-full="../images/screens/02-places.png">
                <img src="../images/screens/02-places-thumb.png" alt="Кофейни" loading="lazy">
                <span>Кофейни</span>
            </a>
        </div>
        <div class="hint">Нажмите на превью, чтобы открыть скриншот целиком.</div>
    </div>

<div class="lightbox" id="lightbox" hidden>
    <img src="" alt="">
    <button type="button" class="lb-close" aria-label="Закрыть">×</button>
</div>

<script>
(function () {
    var lb = document.getElementById('lightbox');
    var full = lb.querySelector('img');
    var links = document.querySelectorAll('.shot');
    for (var i = 0; i < links.length; i++) {
        links[i].addEventListener('click', function (e) {
            e.preventDefault();
            var label = this.querySelector('span');
            full.src = this.getAttribute('data-full');
            full.alt = label ? label.textContent : '';
            lb.hidden = false;
            document.documentElement.style.overflow = 'hidden';
        });
    }
    function close() {
        lb.hidden = true;
        full.src = '';
        document.documentElement.style.overflow = '';
    }
    lb.addEventListener('click', function (e) {
        if (e.target === lb || e.target.className === 'lb-close') { close(); }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !lb.hidden) { close(); }
    });
})();
</script>

<?php
setup_footer('Заполните поля и нажмите «Проверить подключение»');

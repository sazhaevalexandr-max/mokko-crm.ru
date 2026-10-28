<?php
/**
 * Установщик MOKKO CRM (коробочная версия).
 *
 * Логика:
 *  - доступ закрыт одноразовым ключом setup/install.key;
 *  - доступы к MySQL вводятся в форме и сохраняются в config/db_local.php;
 *  - схема базы — setup/schema.sql (10 таблиц);
 *  - после установки пишутся config/license.php и config/installed.lock.
 *
 * Оформление — минималистичная веб-страница в духе CMS начала 2000-х:
 * серый фон, Tahoma, вдавленные поля, выпуклые кнопки и дискета.
 */

require_once dirname(__DIR__) . '/config/security.php';
mokko_harden_errors();
mokko_send_security_headers();
mokko_session_start();

$SETUP_DIR    = __DIR__;
$CONFIG_DIR   = dirname(__DIR__) . '/config';
$LOCK_FILE    = $CONFIG_DIR . '/installed.lock';
$DBCONF_FILE  = $CONFIG_DIR . '/db_local.php';
$LICENSE_FILE = $CONFIG_DIR . '/license.php';
$KEY_FILE     = $SETUP_DIR . '/install.key';

$isPost = (isset($_SERVER['REQUEST_METHOD']) && strtoupper((string)$_SERVER['REQUEST_METHOD']) === 'POST');

/* ── Оформление ─────────────────────────────────────────────────────────── */

function setup_css()
{
    return <<<'CSS'
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; }
body {
    background: #d4d0c8;
    color: #000;
    font: 12px Tahoma, Verdana, Arial, sans-serif;
    padding: 20px 14px 34px;
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

.main { flex: 1 1 0%; min-width: 0; }

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
    max-height: 190px;
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

.msgbox { display: flex; gap: 14px; padding: 14px 16px; background: #fff; border: 1px solid #808080; }
.msgbox .icon { flex: 0 0 64px; }
.msgbox h2 { margin: 0 0 8px; font: bold 12px Tahoma, sans-serif; }
.msgbox h3 { margin: 10px 0 6px; font: bold 11px Tahoma, sans-serif; }
.msgbox p { margin: 0 0 8px; font: 11px Tahoma, sans-serif; line-height: 1.55; }
.msgbox ul { margin: 0 0 10px; padding-left: 18px; font: 11px Tahoma, sans-serif; line-height: 1.5; }
.msgbox .warn { color: #7a4a00; }
.msgbox .err { color: #a00000; }
.msgbox pre {
    margin: 6px 0 10px;
    padding: 8px 10px;
    background: #f4f1e8;
    border: 1px solid #c8c3b6;
    font: 11px "Courier New", monospace;
    line-height: 1.55;
    overflow: auto;
}

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

function setup_header($single = false)
{
    echo '<!DOCTYPE html><html lang="ru"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<meta name="robots" content="noindex">'
        . '<title>MOKKO CRM — установка</title>'
        . '<style>' . setup_css() . '</style></head><body>'
        . '<div class="page">'
        . '<div class="page-head"><div><h1>MOKKO <span>CRM</span></h1>'
        . '<div class="sub">Установка системы · версия 1.0</div></div></div>'
        . '<hr class="rule">'
        . '<div class="content' . ($single ? ' single' : '') . '">';
}

function setup_footer($status)
{
    echo '</div>'
        . '<div class="foot"><span>' . mokko_h($status) . '</span><span>MOKKO CRM · установка</span></div>'
        . '</div></body></html>';
}

function setup_message($title, $bodyHtml)
{
    setup_header(true);
    echo '<div class="msgbox"><div class="icon">' . setup_floppy_svg(58) . '</div><div>'
        . '<h2>' . mokko_h($title) . '</h2>' . $bodyHtml . '</div></div>';
}

/**
 * Безопасная запись PHP-файла.
 */
function write_php_file($path, $php)
{
    $tmp = $path . '.tmp';
    if (@file_put_contents($tmp, $php, LOCK_EX) === false) { return false; }
    if (!@rename($tmp, $path)) { @unlink($tmp); return false; }
    @chmod($path, 0640);
    return true;
}

/**
 * Применяет setup/schema.sql, возвращает список таблиц.
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

/* ── 1. Уже установлено? ────────────────────────────────────────────────── */
if (is_file($LOCK_FILE)) {
    setup_message('Установка уже выполнена', '<p>MOKKO CRM уже установлена на этом сервере. Повторный запуск установщика заблокирован.</p>'
        . '<p><a class="btn" href="../login">Перейти к входу</a></p>'
        . '<p class="warn"><b>Удалите папку setup/ с сервера.</b></p>');
    setup_footer('Готово');
    exit;
}

/* ── 2. Ключ установки ──────────────────────────────────────────────────── */
$installKey = '';
if (is_file($KEY_FILE)) {
    $loaded = include $KEY_FILE;
    if (is_string($loaded)) { $installKey = trim($loaded); }
}

if ($installKey === '') {
    $newKey = bin2hex(random_bytes(16));
    $ok = write_php_file($KEY_FILE, "<?php\n// Ключ установки MOKKO CRM. Передайте его вместе с копией.\nreturn '" . $newKey . "';\n");
    if ($ok) {
        setup_message('Создан ключ установки', '<p>Откройте файл <code>setup/install.key</code> по FTP или в файловом менеджере хостинга, скопируйте ключ и обновите страницу — установщик попросит его ввести.</p>'
            . '<p class="hint">Ключ не показывается на экране, чтобы его не увидел случайный посетитель.</p>');
    } else {
        setup_message('Не удалось создать ключ', '<p class="err">Не удалось записать файл <code>setup/install.key</code>. Дайте права на запись папке setup/ (chmod 755) и обновите страницу.</p>');
    }
    setup_footer('Ожидание ключа');
    exit;
}

/* ── 3. Обработка формы ─────────────────────────────────────────────────── */
$errors = array();
$in = array(
    'db_host'     => 'localhost',
    'db_name'     => 'mokko',
    'db_user'     => 'root',
    'db_pass'     => '',
    'admin_email' => '',
    'admin_pass'  => '',
    'token'       => '',
);
$generatedPass = '';
$domain = preg_replace('/:\d+$/', '', mokko_host());
$adminEmail = '';
$adminPass = '';

if ($isPost) {
    foreach ($in as $k => $def) {
        $in[$k] = isset($_POST[$k]) ? trim((string)$_POST[$k]) : $def;
    }

    if (!hash_equals($installKey, $in['token'])) {
        $errors[] = 'Неверный ключ установки. Он указан в файле setup/install.key и в вашей лицензии.';
    }
    if (!preg_match('/^[A-Za-z0-9_]+$/', $in['db_name'])) {
        $errors[] = 'Имя базы данных: только латинские буквы, цифры и подчёркивание.';
    }
    if ($in['db_host'] === '') { $in['db_host'] = 'localhost'; }
    if ($in['db_user'] === '') { $errors[] = 'Укажите пользователя базы данных.'; }

    if ($in['admin_pass'] === '') {
        $generatedPass = bin2hex(random_bytes(6));
    } elseif (strlen($in['admin_pass']) < 8 || strlen($in['admin_pass']) > 72) {
        $errors[] = 'Пароль администратора — от 8 до 72 символов (или оставьте поле пустым, чтобы сгенерировать).';
    }
    $adminEmail = $in['admin_email'] !== '' ? $in['admin_email'] : 'admin@mokko.local';
    if (strlen($adminEmail) > 254) { $errors[] = 'Слишком длинный e-mail администратора.'; }

    $pdo = null;
    if (!$errors) {
        try {
            $pdo = new PDO(
                'mysql:host=' . $in['db_host'] . ';charset=utf8mb4',
                $in['db_user'],
                $in['db_pass'],
                array(
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                )
            );
        } catch (Exception $e) {
            mokko_log('install: connect failed: ' . $e->getMessage());
            $errors[] = 'Не удалось подключиться к MySQL с этими данными. Проверьте хост, пользователя и пароль.';
        }
    }

    if (!$errors && $pdo instanceof PDO) {
        try {
            $adminPass = $generatedPass !== '' ? $generatedPass : $in['admin_pass'];

            $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . $in['db_name'] . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $pdo->exec('USE `' . $in['db_name'] . '`');

            $tables = setup_apply_schema($pdo);
            foreach (array('users', 'places', 'staff', 'categories', 'items', 'lager', 'guests', 'checks', 'shifts', 'subscribers') as $table) {
                if (!in_array($table, $tables, true)) {
                    throw new RuntimeException('Не создалась таблица ' . $table . '.');
                }
            }

            $has = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
            if ($has === 0) {
                $hash = password_hash($adminPass, PASSWORD_DEFAULT);
                $ins = $pdo->prepare("INSERT INTO users (email, password_hash, name, role, status) VALUES (?, ?, ?, 'admin', 'active')");
                $ins->execute(array($adminEmail, $hash, 'Администратор'));
                $adminId = (int)$pdo->lastInsertId();
                $pdo->prepare('INSERT INTO places (user_id, name) VALUES (?, ?)')->execute(array($adminId, 'Моя кофейня'));
            }

            $dbPhp = "<?php\n// Создано установщиком MOKKO CRM. Доступы к базе данных.\nreturn array(\n"
                   . "    'host' => " . var_export($in['db_host'], true) . ",\n"
                   . "    'name' => " . var_export($in['db_name'], true) . ",\n"
                   . "    'user' => " . var_export($in['db_user'], true) . ",\n"
                   . "    'pass' => " . var_export($in['db_pass'], true) . ",\n"
                   . ");\n";
            if (!write_php_file($DBCONF_FILE, $dbPhp)) {
                throw new RuntimeException('Не удалось записать config/db_local.php. Дайте права на запись папке config/.');
            }
        } catch (Exception $e) {
            mokko_log('install: schema failed: ' . $e->getMessage());
            $errors[] = 'Ошибка при создании таблиц: ' . $e->getMessage();
        }
    }

    if (!$errors) {
        $issued = date('c');
        $signature = hash_hmac('sha256', $domain . '|' . $issued, $installKey);
        $licPhp = "<?php\n// Лицензия MOKKO CRM. Копия привязана к домену установки.\nreturn array(\n"
                . "    'domain'    => " . var_export($domain, true) . ",\n"
                . "    'issued_at' => " . var_export($issued, true) . ",\n"
                . "    'key'       => " . var_export(substr($installKey, 0, 8) . '…', true) . ",\n"
                . "    'signature' => " . var_export($signature, true) . ",\n"
                . ");\n";
        write_php_file($LICENSE_FILE, $licPhp);
        write_php_file($LOCK_FILE, "<?php\n// Установка выполнена " . date('c') . ". Удалите папку setup/.\nreturn true;\n");

        // Персональный секрет подписи обновлений: привязан к этой копии
        // (ключ установки + домен). Тем же секретом вы на своей стороне
        // подписываете апдейты именно для этого домена:
        //   php tools/build-update.php src out.zip --domain=<домен> --key=<ключ установки>
        // Пакет, собранный для другого домена, на этой копии не встанет.
        $updateSecret = hash_hmac('sha256', 'mokko-update|' . $domain, $installKey);
        @file_put_contents($CONFIG_DIR . '/update.secret', $updateSecret . "\n", LOCK_EX);

        $basePath = rtrim(str_replace('\\', '/', dirname(dirname(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/'))), '/');
        $loginUrl = (mokko_is_https() ? 'https' : 'http') . '://' . mokko_host() . $basePath . '/login';

        $body = '<p>Копия привязана к домену <b>' . mokko_h($domain) . '</b>.</p>'
            . '<h3>Данные для входа</h3><pre>'
            . 'Адрес:  ' . mokko_h($loginUrl) . "\n"
            . 'E-mail: ' . mokko_h($adminEmail) . "\n"
            . 'Пароль: ' . mokko_h($adminPass) . "\n"
            . '</pre>';
        if ($generatedPass !== '') {
            $body .= '<p class="err">Пароль сгенерирован автоматически — сохраните его и смените после входа.</p>';
        }
        $body .= '<p><a class="btn" href="../login">Перейти к входу</a></p>'
            . '<p class="warn"><b>Теперь удалите папку setup/ с сервера.</b></p>';

        setup_message('Установка завершена', $body);
        setup_footer('Установка завершена');
        exit;
    }
}

/* ── 4. Форма установки ─────────────────────────────────────────────────── */
setup_header();
?>

<div class="side">
    <div class="disk"><?= setup_floppy_svg(118) ?></div>
    <div class="side-cap">Установочный диск<br><b>MOKKO CRM</b><br>версия 1.0</div>

    <ol class="steps">
        <li class="on"><b>1</b><span>Сервер и база</span></li>
        <li><b>2</b><span>Администратор</span></li>
        <li><b>3</b><span>Ключ установки</span></li>
        <li><b>4</b><span>Готово</span></li>
    </ol>
    <div class="seg"><i class="on"></i><i></i><i></i><i></i></div>
</div>

<div class="main">
    <?php if ($errors): ?>
        <div class="status">
            <?php foreach ($errors as $msg): ?>
                <div class="line err">✗ <?= mokko_h($msg) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <div class="group">
            <div class="group-title">1. База данных MySQL</div>

            <div class="row">
                <div>
                    <label class="first" for="db_host">Хост</label>
                    <input id="db_host" type="text" name="db_host" value="<?= mokko_h($in['db_host']) ?>" autocomplete="off">
                    <div class="hint">Обычно <code>localhost</code>; у некоторых хостингов — отдельный адрес из панели.</div>
                </div>
                <div>
                    <label class="first" for="db_name">Имя базы</label>
                    <input id="db_name" type="text" name="db_name" value="<?= mokko_h($in['db_name']) ?>" autocomplete="off">
                    <div class="hint">Создастся, если её нет. Латиница, цифры и «_».</div>
                </div>
            </div>

            <div class="row">
                <div>
                    <label for="db_user">Пользователь</label>
                    <input id="db_user" type="text" name="db_user" value="<?= mokko_h($in['db_user']) ?>" autocomplete="off">
                </div>
                <div>
                    <label for="db_pass">Пароль базы</label>
                    <input id="db_pass" type="password" name="db_pass" value="" autocomplete="off">
                    <div class="hint">На локальном XAMPP обычно пустой.</div>
                </div>
            </div>
        </div>

        <div class="group">
            <div class="group-title">2. Администратор</div>

            <div class="row">
                <div>
                    <label class="first" for="admin_email">E-mail для входа</label>
                    <input id="admin_email" type="text" name="admin_email" value="<?= mokko_h($in['admin_email']) ?>" autocomplete="off">
                    <div class="hint">Пусто — <code>admin@mokko.local</code>.</div>
                </div>
                <div>
                    <label class="first" for="admin_pass">Пароль</label>
                    <input id="admin_pass" type="password" name="admin_pass" value="" autocomplete="new-password">
                    <div class="hint">От 8 символов. Пусто — сгенерируем и покажем один раз.</div>
                </div>
            </div>
        </div>

        <div class="group">
            <div class="group-title">3. Ключ установки</div>
            <label class="first" for="token">Ключ из лицензии</label>
            <input id="token" type="text" name="token" value="<?= mokko_h($in['token']) ?>" autocomplete="off">
            <div class="hint">Указан в файле <code>setup/install.key</code> и передаётся вместе с копией.</div>
        </div>

        <div class="actions">
            <button type="submit" class="primary">Установить</button>
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
setup_footer('Заполните данные и нажмите «Установить»');

<?php
/**
 * Раздел «Обновления и модули».
 *
 * Открывается по /updates, доступен только владельцу аккаунта (role=admin).
 * Вся логика установки — в lib/updater.php, API — в api/updates.php.
 *
 * Оформление повторяет установщик: серый фон, Tahoma, вдавленные поля,
 * выпуклые кнопки, дискета и минимум декора.
 */

if (!defined('MOKKO')) {
    die;
}

require_once dirname(__DIR__) . '/config/db.php';
if (is_file(dirname(__DIR__) . '/config/version.php')) {
    require_once dirname(__DIR__) . '/config/version.php';
}
require_once dirname(__DIR__) . '/lib/modules.php';
require_once dirname(__DIR__) . '/lib/updater.php';

if (!mokko_is_admin_session()) {
    header('Location: ' . BASE_URL . '/login');
    exit;
}

$uid = (int)$_SESSION['uid'];
try {
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT id, role, password_hash FROM users WHERE id = ? LIMIT 1');
    $stmt->execute(array($uid));
    $me = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $me = null;
}

if (!$me || $me['role'] !== 'admin') {
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><title>403</title><p style="font:16px sans-serif;padding:40px">Доступ только для администратора. <a href="' . mokko_h(BASE_URL) . '/panel">В панель</a></p>';
    exit;
}

$pv = isset($_SESSION['pv']) ? (string)$_SESSION['pv'] : '';
if ($pv === '' || !hash_equals(mokko_password_fingerprint($me['password_hash']), $pv)) {
    $_SESSION = array();
    session_destroy();
    header('Location: ' . BASE_URL . '/login');
    exit;
}

$storageError = '';
try {
    mokko_upd_ensure_storage();
} catch (Throwable $e) {
    $storageError = $e->getMessage();
}

$state = mokko_upd_ui_state();
$csrf = mokko_csrf_token();
$updatesDisabled = is_file(MOKKO_ROOT . '/config/updates.disabled');
$canUpload = $storageError === '' && !$updatesDisabled;
$version = $state['version'];
$maxUpload = (int)$state['limits']['max'];

$modulesCount = count($state['modules']);
$enabledCount = 0;
foreach ($state['modules'] as $module) {
    if (!empty($module['enabled'])) {
        $enabledCount++;
    }
}

/**
 * Дискета в стиле установщика.
 */
function upd_floppy_svg($size)
{
    $svg = <<<'SVG'
<svg viewBox="0 0 140 140" width="%SIZE%" height="%SIZE%" role="img" aria-label="Дискета обновлений MOKKO CRM">
  <defs>
    <linearGradient id="upd-fd-body" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#3c3c3c"/>
      <stop offset="1" stop-color="#141414"/>
    </linearGradient>
    <linearGradient id="upd-fd-metal" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#f0f0f0"/>
      <stop offset=".55" stop-color="#b9b9b9"/>
      <stop offset="1" stop-color="#8d8d8d"/>
    </linearGradient>
  </defs>
  <rect x="6" y="6" width="128" height="128" rx="5" fill="url(#upd-fd-body)" stroke="#000" stroke-width="2"/>
  <rect x="40" y="6" width="60" height="38" fill="url(#upd-fd-metal)" stroke="#5f5f5f"/>
  <rect x="57" y="6" width="26" height="30" fill="#242424"/>
  <rect x="61" y="11" width="18" height="9" fill="#6f6f6f"/>
  <rect x="22" y="56" width="96" height="66" fill="#f5f2e6" stroke="#8b8678"/>
  <text x="30" y="75" font-family="Tahoma, Arial, sans-serif" font-size="9" fill="#2b2b2b">MOKKO CRM</text>
  <text x="30" y="87" font-family="Tahoma, Arial, sans-serif" font-size="7" fill="#7a7466">update disk</text>
  <line x1="30" y1="96" x2="110" y2="96" stroke="#cdc7b6"/>
  <line x1="30" y1="104" x2="110" y2="104" stroke="#cdc7b6"/>
  <line x1="30" y1="112" x2="92" y2="112" stroke="#cdc7b6"/>
  <rect x="104" y="116" width="12" height="10" fill="#1e1e1e" stroke="#000"/>
  <circle class="upd-led" cx="122" cy="30" r="3.2" fill="#39d353" stroke="#0b3d16"/>
</svg>
SVG;

    return str_replace('%SIZE%', (int)$size, $svg);
}
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>MOKKO CRM — обновления</title>
<link rel="icon" type="image/svg+xml" href="<?= BASE_PATH ?>/images/brand/favicon.svg?v=2">
<link href="<?= BASE_PATH ?>/css/updates.css?v=2" rel="stylesheet">
</head>
<body class="upd-body">

<div class="upd-page">

    <div class="upd-masthead">
        <div class="upd-masthead-main">
            <h1>MOKKO <span>CRM</span></h1>
            <div class="upd-sub">Обновления системы · <b id="top-version">v<?= mokko_h($version) ?></b></div>
        </div>
        <a class="upd-top-back" href="<?= BASE_URL ?>/panel">← Панель управления</a>
    </div>
    <hr class="upd-rule">

    <?php if ($state['maintenance']): ?>
    <div class="upd-banner upd-banner--warn" role="status">
        <b>Включён режим обслуживания.</b> Посетители видят 503. Завершите обновление или отключите режим.
        <button type="button" class="upd-linkbtn" id="maintenance-off">Отключить</button>
    </div>
    <?php endif; ?>

    <?php if ($updatesDisabled): ?>
    <div class="upd-banner upd-banner--warn" role="status">
        <b>Обновления отключены.</b> На сервере есть <code>config/updates.disabled</code>.
        Удалите файл, чтобы снова разрешить установку пакетов.
    </div>
    <?php endif; ?>

    <?php if ($storageError !== ''): ?>
    <div class="upd-banner upd-banner--error" role="alert">
        <b>Каталог обновлений недоступен.</b> <?= mokko_h($storageError) ?>
    </div>
    <?php endif; ?>

    <div class="upd-layout">

        <div class="upd-side">
            <div class="upd-disk"><?= upd_floppy_svg(112) ?></div>
            <div class="upd-side-cap">
                <b>MOKKO CRM</b><br>update disk<br>1.0 · 2026
            </div>

            <table class="upd-facts">
                <tr><th>Версия ядра</th><td><b id="fact-version"><?= mokko_h($version) ?></b></td></tr>
                <tr><th>Модули</th><td><b id="fact-modules"><?= (int)$enabledCount ?> из <?= (int)$modulesCount ?></b></td></tr>
                <tr><th>Лимит пакета</th><td><?= mokko_h(mokko_upd_format_bytes($maxUpload)) ?></td></tr>
                <tr><th>Подпись</th><td id="fact-signature"><?= $state['secret'] ? 'ключ задан' : 'не настроена' ?></td></tr>
            </table>

            <div class="upd-side-note">
                Пакет может заменить PHP/JS/CSS-файлы, добавить модуль и выполнить SQL.
                Устанавливайте только собственные сборки.
            </div>
        </div>

        <div class="upd-main">

            <div class="group">
                <div class="upd-group-title">Установка пакета</div>

                <div id="drop" class="upd-drop<?= $canUpload ? '' : ' is-disabled' ?>" tabindex="<?= $canUpload ? '0' : '-1' ?>" role="button" aria-label="Выбрать ZIP-пакет">
                    <svg class="upd-drop-icon" viewBox="0 0 48 48" width="44" height="44" fill="none" stroke="#0a246a" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M10 5h19l9 9v29H10z"/>
                        <path d="M29 5v9h9"/>
                        <path d="M24 21v13M19 26l5-5 5 5"/>
                    </svg>
                    <div class="upd-drop-text">
                        <b>Перетащите ZIP-пакет сюда</b>
                        <span>или нажмите, чтобы выбрать файл</span>
                    </div>
                    <div class="upd-drop-hint">в корне архива нужен mokko-update.json</div>
                </div>

                <input type="file" id="package" accept=".zip,application/zip,application/x-zip-compressed" hidden>

                <div id="upload" class="upd-progress" hidden>
                    <div class="upd-progress-bar"><i id="upload-bar"></i></div>
                    <div class="upd-progress-meta">
                        <span id="upload-name">Загрузка…</span>
                        <span id="upload-pct">0%</span>
                    </div>
                </div>

                <div class="upd-hint">
                    ZIP до <?= mokko_h(mokko_upd_format_bytes($maxUpload)) ?>.
                    Перед установкой сервер проверит пути и хеши, сохранит копию файлов и сделает дамп БД.
                </div>
            </div>

            <div id="preview" class="group upd-preview" hidden aria-live="polite"></div>

            <div class="group">
                <div class="upd-group-title">Модули <span id="modules-count" class="upd-count"><?= (int)$modulesCount ?></span></div>
                <div id="modules-list" class="upd-list"></div>
                <div class="upd-hint">
                    Модуль — каталог <code>modules/&lt;код&gt;/</code> с <code>module.json</code>.
                    Выключенный модуль остаётся на диске, но его <code>bootstrap.php</code> не выполняется.
                </div>
            </div>

            <div class="group">
                <div class="upd-group-title">История операций</div>
                <div id="history-list" class="upd-list"></div>
            </div>

            <div class="group">
                <div class="upd-group-title">Подпись пакетов</div>
                <div class="upd-text">
                    Если задан ключ <code>config/update.secret</code>, пакеты собираются с подписью
                    <code>mokko-update.sig</code> (HMAC-SHA256), и сервер отвергает изменённые архивы.
                    Обязательную подпись включает файл <code>config/update.require_signature</code>.
                </div>
                <div class="upd-actions">
                    <?php if ($state['secret']): ?>
                        <span class="upd-badge upd-badge--ok">ключ задан</span>
                        <span class="upd-hint" style="margin:0">Сборка: <code>php tools/build-update.php src dist.zip</code></span>
                    <?php else: ?>
                        <span class="upd-badge">ключ не задан</span>
                        <button type="button" class="upd-btn" id="secret-create">Создать ключ</button>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <div class="upd-foot">
        <span>MOKKO CRM · офлайн-обновления</span>
        <span><a href="<?= BASE_URL ?>/panel">панель управления</a> · <a href="<?= BASE_URL ?>/updates">обновить страницу</a></span>
    </div>
</div>

<div id="toast" class="upd-toast" hidden role="status" aria-live="polite"></div>

<div id="modal" class="upd-modal" hidden>
    <div class="upd-modal-box" role="dialog" aria-modal="true" aria-labelledby="modal-title">
        <h3 id="modal-title"></h3>
        <div id="modal-text" class="upd-modal-text"></div>
        <label id="modal-check-wrap" class="upd-check" hidden>
            <input type="checkbox" id="modal-check">
            <span id="modal-check-label"></span>
        </label>
        <div class="upd-modal-actions">
            <button type="button" class="upd-btn" id="modal-cancel">Отмена</button>
            <button type="button" class="upd-btn upd-btn--danger" id="modal-ok">Подтвердить</button>
        </div>
    </div>
</div>

<script>
window.UPD = {
    base: <?= mokko_js(BASE_PATH) ?>,
    api: <?= mokko_js(BASE_PATH . '/api/updates.php') ?>,
    csrf: <?= mokko_js($csrf) ?>,
    state: <?= mokko_js($state) ?>,
    maxUpload: <?= (int)$maxUpload ?>,
    canUpload: <?= $canUpload ? 'true' : 'false' ?>
};
</script>
<script src="<?= BASE_PATH ?>/js/updates.js?v=2"></script>
</body>
</html>

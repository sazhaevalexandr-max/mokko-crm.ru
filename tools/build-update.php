<?php
/**
 * Сборщик офлайн-пакетов MOKKO CRM.
 *
 * Использование:
 *   php tools/build-update.php <каталог-исходников> <пакет.zip> [--secret=HEX] [--no-sign]
 *
 * Каталог-исходники повторяет структуру проекта:
 *
 *   src/
 *     mokko-update.json          ← манифест (id, title, version, type, ...)
 *     api/crm.php                 ← файлы, которые нужно заменить
 *     js/panel.js
 *     modules/loyalty/module.json ← для type=module
 *
 * Сборщик сам считает SHA-256 всех файлов и записывает их в manifest.files.
 * Если задан секрет (--secret или config/update.secret), добавляет
 * mokko-update.sig — HMAC-SHA256 от байтов манифеста.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);

function builder_fail($message, $code = 1)
{
    fwrite(STDERR, "Ошибка: " . $message . PHP_EOL);
    exit($code);
}

function builder_usage()
{
    fwrite(STDERR, "Использование: php tools/build-update.php <src> <out.zip> [--domain=DOMAIN --key=INSTALLKEY] [--secret=HEX] [--no-sign] [--force]\n");
    exit(2);
}

$args = array_slice($argv, 1);
$positional = array();
$options = array();

foreach ($args as $arg) {
    if (strpos($arg, '--') === 0) {
        $arg = substr($arg, 2);
        $eq = strpos($arg, '=');
        if ($eq === false) {
            $options[$arg] = true;
        } else {
            $options[substr($arg, 0, $eq)] = substr($arg, $eq + 1);
        }
    } else {
        $positional[] = $arg;
    }
}

if (count($positional) < 2) {
    builder_usage();
}

$source = rtrim(str_replace('\\', '/', $positional[0]), '/');
$output = str_replace('\\', '/', $positional[1]);

if (!is_dir($source)) {
    builder_fail('каталог не найден: ' . $source);
}

$manifestFile = $source . '/mokko-update.json';
if (!is_file($manifestFile)) {
    builder_fail('в каталоге нет mokko-update.json');
}

$rawManifest = file_get_contents($manifestFile);
$manifest = json_decode($rawManifest, true);
if (!is_array($manifest)) {
    builder_fail('mokko-update.json — некорректный JSON');
}

$id = isset($manifest['id']) ? (string)$manifest['id'] : '';
$version = isset($manifest['version']) ? (string)$manifest['version'] : '';
$type = isset($manifest['type']) ? (string)$manifest['type'] : '';
$slug = isset($manifest['module']) ? (string)$manifest['module'] : '';

if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._\-]{2,63}$/', $id)) {
    builder_fail('некорректный id (латиница, цифры, точка, дефис; 3–64 символа)');
}
if (!preg_match('/^\d{1,4}\.\d{1,4}\.\d{1,4}([.\-+][A-Za-z0-9.\-+]{1,20})?$/', $version)) {
    builder_fail('некорректная версия, нужен вид 1.2.3');
}
if ($type !== 'update' && $type !== 'module') {
    builder_fail('type должен быть update или module');
}
if ($type === 'module' && !preg_match('/^[a-z0-9][a-z0-9\-]{1,31}$/', $slug)) {
    builder_fail('для модуля нужен корректный module (a-z, 0-9, дефис)');
}

// Секрет подписи.
//   Доменная подпись:  --domain=client.ru --key=<ключ установки этой копии>
//     секрет выводится так же, как его пишет установщик:
//     HMAC-SHA256(data="mokko-update|"+домен, key=install.key).
//     Поле domain добавляется в манифест (под подпись) — такой пакет
//     встанет только на копию этого домена.
//   Иначе прежнее поведение: --secret=HEX либо локальный config/update.secret.
$secret = '';
if (!empty($options['domain']) && is_string($options['domain'])) {
    $targetDomain = preg_replace('/:\d+$/', '', trim($options['domain']));
    if ($targetDomain === '') {
        builder_fail('--domain пустой');
    }
    if (empty($options['key']) || !is_string($options['key'])) {
        builder_fail('для --domain нужен --key=<ключ установки этой копии> (содержимое setup/install.key)');
    }
    $installKey = trim($options['key']);
    if ($installKey === '') {
        builder_fail('--key пустой');
    }
    $secret = hash_hmac('sha256', 'mokko-update|' . $targetDomain, $installKey);
    $manifest['domain'] = $targetDomain; // попадёт в подписанный манифест
} else {
    if (!empty($options['secret']) && is_string($options['secret'])) {
        $secret = trim($options['secret']);
    } elseif (!isset($options['no-sign']) && is_file($root . '/config/update.secret')) {
        $secret = trim((string)file_get_contents($root . '/config/update.secret'));
    }
}
if ($secret !== '' && !preg_match('/^[a-f0-9]{64}$/i', $secret)) {
    builder_fail('секрет должен быть 64 hex-символа');
}

$excludeExact = array(
    'mokko-update.json',
    'mokko-update.sig',
    'config/db_local.php',
    'config/db.local.php',
    'config/installed.lock',
    'config/license.php',
    'config/version.php',
    'config/update.secret',
    'config/maintenance.flag',
);

$collected = array();
$totalBytes = 0;
$outputReal = str_replace('\\', '/', $output);

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($iterator as $file) {
    if (!$file->isFile() || $file->isLink()) {
        continue;
    }

    $abs = str_replace('\\', '/', $file->getPathname());
    $relative = ltrim(substr($abs, strlen($source)), '/');
    $relative = str_replace('\\', '/', $relative);

    if (in_array($relative, $excludeExact, true)) {
        continue;
    }
    if (strpos($relative, 'storage/') === 0 || strpos($relative, '.git/') === 0) {
        continue;
    }
    if (preg_match('#(^|/)\.#', $relative)) {
        continue;
    }
    if (preg_match('/\.(zip|bak|tmp|log)$/i', $relative)) {
        continue;
    }
    if (basename($relative) === 'Thumbs.db' || basename($relative) === '.DS_Store') {
        continue;
    }
    if ($abs === $outputReal) {
        continue;
    }

    if (strpos($relative, 'setup/') === 0) {
        builder_fail('setup/ нельзя включать в пакет: ' . $relative);
    }

    if ($type === 'module') {
        if (strpos($relative, 'modules/' . $slug . '/') !== 0) {
            builder_fail('файл модуля должен лежать в modules/' . $slug . '/: ' . $relative);
        }
    } else {
        if (strpos($relative, 'modules/') === 0) {
            builder_fail('модули собираются пакетом type=module, а не update: ' . $relative);
        }
    }

    $size = $file->getSize();
    $collected[$relative] = array(
        'abs'  => $abs,
        'size' => $size,
        'sha'  => hash_file('sha256', $abs),
    );
    $totalBytes += $size;
}

if (empty($collected)) {
    builder_fail('в каталоге нет файлов для пакета');
}

ksort($collected);

$filesMap = array();
foreach ($collected as $relative => $meta) {
    $filesMap[$relative] = $meta['sha'];
}

$manifest['files'] = $filesMap;
$manifest['id'] = $id;
$manifest['version'] = $version;
$manifest['type'] = $type;
if (!isset($manifest['title']) || $manifest['title'] === '') {
    $manifest['title'] = $id;
}

// SQL-миграция едет в архиве, но не устанавливается в веб-корень и не
// входит в files: сервер выполняет её из временного каталога.
if (!empty($manifest['sql'])) {
    $sqlRel = str_replace('\\', '/', (string)$manifest['sql']);
    if (!isset($collected[$sqlRel])) {
        builder_fail('SQL-файл из manifest.sql не найден: ' . $sqlRel);
    }
    unset($manifest['files'][$sqlRel]);
}

$manifestJson = json_encode(
    $manifest,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
if ($manifestJson === false) {
    builder_fail('не удалось сериализовать манифест');
}

if (is_file($output) && !isset($options['force'])) {
    builder_fail('файл ' . $output . ' уже существует (добавьте --force)');
}
if (is_dir(dirname($output)) === false) {
    if (!mkdir(dirname($output), 0755, true)) {
        builder_fail('не удалось создать каталог ' . dirname($output));
    }
}
if (is_file($output)) {
    unlink($output);
}

$zip = new ZipArchive();
if ($zip->open($output, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    builder_fail('не удалось создать ' . $output);
}

$zip->addFromString('mokko-update.json', $manifestJson);

foreach ($collected as $relative => $meta) {
    if (!$zip->addFile($meta['abs'], $relative)) {
        $zip->close();
        @unlink($output);
        builder_fail('не удалось добавить в архив: ' . $relative);
    }
}

if ($secret !== '') {
    $signature = hash_hmac('sha256', $manifestJson, $secret);
    $zip->addFromString('mokko-update.sig', $signature . "\n");
}

$zip->close();

$size = filesize($output);
$installCount = count($collected);
if (!empty($manifest['sql'])) {
    $installCount--;
}

echo "Пакет собран: " . $output . PHP_EOL;
echo "  id:       " . $id . PHP_EOL;
echo "  версия:   " . $version . PHP_EOL;
echo "  тип:      " . $type . ($type === 'module' ? ' (' . $slug . ')' : '') . PHP_EOL;
echo "  файлов:   " . $installCount . PHP_EOL;
echo "  размер:   " . round($size / 1024) . " КБ (распаковано " . round($totalBytes / 1024) . " КБ)" . PHP_EOL;
if (isset($manifest['domain']) && $manifest['domain'] !== '') {
    echo "  домен:    " . $manifest['domain'] . " (пакет встанет только на эту копию)" . PHP_EOL;
}
echo "  подпись:  " . ($secret !== '' ? 'HMAC-SHA256' : 'нет (сервер проверит только SHA-256)') . PHP_EOL;

<?php
/**
 * MOKKO CRM — движок офлайн-обновлений.
 *
 * Пакет — ZIP-архив с манифестом mokko-update.json в корне:
 *
 *   {
 *     "id":          "mokko-1.1.0",
 *     "title":       "MOKKO CRM 1.1.0",
 *     "version":     "1.1.0",
 *     "type":        "update",              // update | module
 *     "module":      "loyalty",             // только для type=module
 *     "requires":    "1.0.0",              // минимальная версия ядра
 *     "description": "Исправления и доработки",
 *     "sql":         "sql/upgrade.sql",     // необязательно
 *     "delete":      ["old/file.php"],      // необязательно
 *     "files":       {"api/crm.php": "<sha256>"} // необязательно, но желательно
 *   }
 *
 * Что делает установка:
 *   1. проверяет ZIP (пути, размеры, симлинки, расширения, контрольные суммы);
 *   2. делает резервную копию заменяемых файлов и дамп БД (mysqldump);
 *   3. распаковывает пакет в storage/updates/staging (вне веб-доступа);
 *   4. подменяет файлы, выполняет SQL, пишет config/version.php;
 *   5. ведёт журнал в storage/updates/history.json;
 *   6. при ошибке откатывает файлы и БД.
 *
 * Ограничения безопасности:
 *   - доступ только из административного раздела;
 *   - запрещены config/db_local.php, config/license.php, setup/, storage/,
 *     .htaccess и любые файлы с точкой в начале имени;
 *   - запрещены symlink-записи и расширения, которые может выполнить сервер
 *     (cgi, phar, exe, sh...), кроме обычных php/js/css/картинок;
 *   - при наличии config/update.secret проверяется HMAC-подпись
 *     mokko-update.sig (см. tools/build-update.php).
 */

if (defined('MOKKO_UPDATER')) {
    return;
}
define('MOKKO_UPDATER', 1);

if (!defined('MOKKO_ROOT')) {
    define('MOKKO_ROOT', dirname(__DIR__));
}
if (!defined('MOKKO_VERSION')) {
    define('MOKKO_VERSION', '1.0.0');
}

class MokkoUpdateException extends RuntimeException
{
}

/* ── Каталоги и лимиты ────────────────────────────────────────────────────── */

function mokko_upd_dir()
{
    return MOKKO_ROOT . '/storage/updates';
}

function mokko_upd_inbox_dir()
{
    return mokko_upd_dir() . '/inbox';
}

function mokko_upd_staging_dir()
{
    return mokko_upd_dir() . '/staging';
}

function mokko_upd_backups_dir()
{
    return mokko_upd_dir() . '/backups';
}

function mokko_upd_removed_dir()
{
    return mokko_upd_dir() . '/removed';
}

function mokko_upd_history_file()
{
    return mokko_upd_dir() . '/history.json';
}

function mokko_upd_lock_file()
{
    return mokko_upd_dir() . '/.lock';
}

function mokko_upd_limits()
{
    return array(
        'zip_bytes'   => 64 * 1024 * 1024,
        'files'       => 3000,
        'total_bytes' => 200 * 1024 * 1024,
        'file_bytes'  => 32 * 1024 * 1024,
        'ratio'       => 120,
    );
}

function mokko_upd_ini_bytes($value)
{
    $value = trim((string)$value);
    if ($value === '') {
        return 0;
    }
    $unit = strtolower(substr($value, -1));
    $num = (float)$value;
    if ($unit === 'g') {
        $num *= 1024 * 1024 * 1024;
    } elseif ($unit === 'm') {
        $num *= 1024 * 1024;
    } elseif ($unit === 'k') {
        $num *= 1024;
    }
    return (int)$num;
}

/**
 * Реальный максимум загрузки: min(наш лимит, upload_max_filesize, post_max_size).
 * post_max_size включает накладные расходы multipart, поэтому отнимаем 1 МиБ.
 */
function mokko_upd_max_upload()
{
    $limits = mokko_upd_limits();
    $max = $limits['zip_bytes'];

    foreach (array('upload_max_filesize', 'post_max_size') as $key) {
        $bytes = mokko_upd_ini_bytes(ini_get($key));
        if ($bytes > 0) {
            $max = min($max, $bytes);
        }
    }
    if ($max > 2 * 1024 * 1024) {
        $max -= 1024 * 1024;
    }

    return max(0, $max);
}

function mokko_upd_ensure_storage()
{
    static $done = false;
    if ($done) {
        return;
    }

    $dirs = array(
        mokko_upd_dir(),
        mokko_upd_inbox_dir(),
        mokko_upd_staging_dir(),
        mokko_upd_backups_dir(),
        mokko_upd_removed_dir(),
    );

    foreach ($dirs as $dir) {
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new MokkoUpdateException('Не удалось создать каталог ' . $dir . '. Проверьте права на папку storage/.');
        }
        if (!is_writable($dir)) {
            throw new MokkoUpdateException('Каталог ' . $dir . ' недоступен для записи. Выдайте права веб-серверу.');
        }
    }

    $deny = "# Служебный каталог MOKKO CRM — прямой доступ запрещён.\n"
        . "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n"
        . "<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n"
        . "<IfModule mod_php7.c>\n    php_flag engine off\n</IfModule>\n";

    foreach (array(MOKKO_ROOT . '/storage', mokko_upd_dir()) as $dir) {
        $ht = $dir . '/.htaccess';
        if (!is_file($ht)) {
            @file_put_contents($ht, $deny, LOCK_EX);
        }
    }

    // Индексные файлы не дают листинг даже там, где .htaccess игнорируется.
    foreach (array(MOKKO_ROOT . '/storage', mokko_upd_dir()) as $dir) {
        if (!is_file($dir . '/index.html')) {
            @file_put_contents($dir . '/index.html', '');
        }
    }

    $done = true;
}

function mokko_upd_writable_report()
{
    $report = array();
    foreach (array(
        'storage'          => MOKKO_ROOT . '/storage',
        'storage/updates'  => mokko_upd_dir(),
        'config'           => MOKKO_ROOT . '/config',
        'modules'          => MOKKO_ROOT . '/modules',
    ) as $label => $dir) {
        $report[$label] = array(
            'exists'   => is_dir($dir),
            'writable' => is_dir($dir) ? is_writable($dir) : is_writable(dirname($dir)),
        );
    }
    return $report;
}

/* ── Вспомогательные функции ──────────────────────────────────────────────── */

function mokko_upd_text($value, $max = 200)
{
    $value = trim(strip_tags((string)$value));
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $max, 'UTF-8');
    }
    return substr($value, 0, $max);
}

/**
 * Приведение текста из внешних программ (mysqldump/mysql в Windows) к UTF-8.
 * Иначе сообщение об ошибке в CP1251 ломает json_encode журнала.
 */
function mokko_upd_utf8($value)
{
    $value = (string)$value;
    if ($value === '' || preg_match('//u', $value)) {
        return $value;
    }

    if (function_exists('mb_convert_encoding')) {
        $converted = @mb_convert_encoding($value, 'UTF-8', 'Windows-1251');
        if (is_string($converted) && preg_match('//u', $converted)) {
            return $converted;
        }
    }
    if (function_exists('iconv')) {
        $converted = @iconv('Windows-1251', 'UTF-8//IGNORE', $value);
        if ($converted !== false && preg_match('//u', $converted)) {
            return $converted;
        }
    }

    return preg_replace('/[\x80-\xFF]/', '?', $value);
}

function mokko_upd_collect_bad_utf8($value, $path, &$found)
{
    if (is_string($value)) {
        if ($value !== '' && !preg_match('//u', $value)) {
            $found[] = $path . '[' . bin2hex(substr($value, 0, 48)) . ']';
        }
        return;
    }
    if (is_array($value)) {
        foreach ($value as $key => $item) {
            mokko_upd_collect_bad_utf8($item, $path . '.' . $key, $found);
        }
    }
}

function mokko_upd_write_json($file, $data)
{
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        $bad = array();
        mokko_upd_collect_bad_utf8($data, '$', $bad);
        throw new MokkoUpdateException(
            'Не удалось сериализовать данные: ' . json_last_error_msg()
            . (!empty($bad) ? ' (поля: ' . implode(', ', array_slice($bad, 0, 5)) . ')' : '')
        );
    }
    $tmp = $file . '.tmp' . bin2hex(random_bytes(4));
    if (@file_put_contents($tmp, $json, LOCK_EX) === false) {
        throw new MokkoUpdateException('Не удалось записать ' . $file . '.');
    }
    if (!@rename($tmp, $file)) {
        @unlink($tmp);
        throw new MokkoUpdateException('Не удалось сохранить ' . $file . '.');
    }
    @chmod($file, 0640);
}

function mokko_upd_read_json($file)
{
    if (!is_file($file)) {
        return null;
    }
    $raw = @file_get_contents($file);
    if ($raw === false) {
        return null;
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

function mokko_upd_remove_tree($dir)
{
    if (!is_dir($dir)) {
        return;
    }
    $items = @scandir($dir);
    if (!is_array($items)) {
        return;
    }
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . '/' . $item;
        if (is_link($path) || is_file($path)) {
            @unlink($path);
        } elseif (is_dir($path)) {
            mokko_upd_remove_tree($path);
        }
    }
    @rmdir($dir);
}

/**
 * Нормализация пути внутри проекта. Возвращает null для недопустимых путей.
 */
function mokko_upd_normalize_path($name)
{
    $name = str_replace('\\', '/', (string)$name);
    if ($name === '' || strlen($name) > 240) {
        return null;
    }
    if (!preg_match('//u', $name)) {
        return null; // имя файла не в UTF-8
    }
    if ($name[0] === '/') {
        return null; // абсолютный путь
    }
    if (preg_match('/^[A-Za-z]:/', $name) || strpos($name, "\0") !== false) {
        return null;
    }

    $parts = explode('/', $name);
    $out = array();
    foreach ($parts as $part) {
        if ($part === '' || $part === '.') {
            continue;
        }
        if ($part === '..') {
            return null;
        }
        // Скрытые файлы и каталоги (.git, .htaccess, .env) запрещены целиком.
        if (isset($part[0]) && $part[0] === '.') {
            return null;
        }
        // Короткие имена Windows (CONFIG~1) могут обойти проверку путей.
        if (strpos($part, '~') !== false) {
            return null;
        }
        $out[] = $part;
    }

    $path = implode('/', $out);
    if ($path === '' || preg_match('/[\x00-\x1F]/', $path)) {
        return null;
    }
    return $path;
}

function mokko_upd_allowed_extensions()
{
    return array(
        'php', 'js', 'css', 'scss', 'json', 'html', 'htm', 'svg',
        'png', 'jpg', 'jpeg', 'webp', 'gif', 'ico',
        'woff', 'woff2', 'ttf', 'otf', 'eot',
        'md', 'txt', 'map', 'xml', 'csv', 'webmanifest',
    );
}

function mokko_upd_forbidden_exact()
{
    return array(
        'config/db_local.php',
        'config/db.local.php',
        'config/installed.lock',
        'config/license.php',
        'config/version.php',
        'config/update.secret',
        'config/maintenance.flag',
        'config/updates.disabled',
        'mokko-update.json',
        'mokko-update.sig',
    );
}

/**
 * Можно ли пакету писать по этому пути.
 *
 * @param string $path нормализованный путь
 * @param string $type update|module
 * @param string $slug код модуля
 */
function mokko_upd_path_allowed($path, $type, $slug = '')
{
    if ($path === null || $path === '') {
        return false;
    }

    // Windows не различает регистр: CONFIG/DB_LOCAL.PHP — тот же файл.
    $pathLower = strtolower($path);
    foreach (mokko_upd_forbidden_exact() as $forbidden) {
        if ($pathLower === strtolower($forbidden)) {
            return false;
        }
    }

    $parts = explode('/', $path);
    $first = strtolower($parts[0]);

    if ($first === 'storage' || $first === 'setup') {
        return false;
    }

    if ($type === 'module') {
        if (!mokko_module_slug_ok($slug)) {
            return false;
        }
        if (strpos($pathLower, 'modules/' . $slug . '/') !== 0) {
            return false;
        }
    } elseif ($first === 'modules') {
        // Модули ставятся только пакетами type=module.
        return false;
    }

    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if ($ext === '' || !in_array($ext, mokko_upd_allowed_extensions(), true)) {
        return false;
    }

    return true;
}

if (!function_exists('mokko_module_slug_ok')) {
    function mokko_module_slug_ok($slug)
    {
        return is_string($slug) && preg_match('/^[a-z0-9][a-z0-9\-]{1,31}$/', $slug) === 1;
    }
}

/* ── Проверка ZIP ─────────────────────────────────────────────────────────── */

function mokko_upd_open_zip($path)
{
    if (!class_exists('ZipArchive')) {
        throw new MokkoUpdateException('На сервере не включено расширение PHP zip.');
    }
    $zip = new ZipArchive();
    $code = $zip->open($path, ZipArchive::CHECKCONS);
    if ($code !== true) {
        throw new MokkoUpdateException('Не удалось открыть ZIP-архив (код ' . (int)$code . ').');
    }
    return $zip;
}

function mokko_upd_zip_entry_is_link($zip, $index)
{
    $opsys = 0;
    $attr = 0;
    if (!$zip->getExternalAttributesIndex($index, $opsys, $attr)) {
        return false;
    }
    // Unix-режим лежит в старших 16 битах. 0xA000 — symlink.
    $mode = ($attr >> 16) & 0xFFFF;
    return ($mode & 0xF000) === 0xA000;
}

function mokko_upd_zip_hash($zip, $name)
{
    $fp = $zip->getStream($name);
    if (!$fp) {
        return null;
    }
    $ctx = hash_init('sha256');
    while (!feof($fp)) {
        $chunk = fread($fp, 1048576);
        if ($chunk === false) {
            break;
        }
        if ($chunk !== '') {
            hash_update($ctx, $chunk);
        }
    }
    fclose($fp);
    return hash_final($ctx);
}

function mokko_upd_secret()
{
    $file = MOKKO_ROOT . '/config/update.secret';
    if (!is_file($file)) {
        return null;
    }
    $secret = trim((string)@file_get_contents($file));
    return preg_match('/^[a-f0-9]{64}$/i', $secret) ? $secret : null;
}

/**
 * Домен, к которому привязана эта копия (из config/license.php).
 * Пустая строка — лицензии нет (например, в деве): доменную проверку пропускаем.
 */
function mokko_upd_license_domain()
{
    $file = MOKKO_ROOT . '/config/license.php';
    if (!is_file($file)) {
        return '';
    }
    $lic = @include $file;
    if (is_array($lic) && isset($lic['domain'])) {
        return trim((string)$lic['domain']);
    }
    return '';
}

function mokko_upd_secret_create()
{
    $file = MOKKO_ROOT . '/config/update.secret';
    if (is_file($file)) {
        return trim((string)@file_get_contents($file));
    }
    $secret = bin2hex(random_bytes(32));
    if (@file_put_contents($file, $secret . "\n", LOCK_EX) === false) {
        throw new MokkoUpdateException('Не удалось создать config/update.secret.');
    }
    @chmod($file, 0600);
    return $secret;
}

/**
 * Разбор и проверка манифеста.
 *
 * @param array $manifest сырые данные mokko-update.json
 * @param array $entries  путь => array(size, comp_size)
 * @return array нормализованный манифест
 */
function mokko_upd_validate_manifest($manifest, $entries)
{
    if (!is_array($manifest)) {
        throw new MokkoUpdateException('mokko-update.json повреждён.');
    }

    $id = isset($manifest['id']) ? (string)$manifest['id'] : '';
    if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._\-]{2,63}$/', $id)) {
        throw new MokkoUpdateException('Некорректный id пакета в манифесте.');
    }

    $version = isset($manifest['version']) ? (string)$manifest['version'] : '';
    if (!preg_match('/^\d{1,4}\.\d{1,4}\.\d{1,4}([.\-+][A-Za-z0-9.\-+]{1,20})?$/', $version)) {
        throw new MokkoUpdateException('Некорректная версия в манифесте (нужен вид 1.2.3).');
    }

    $type = isset($manifest['type']) ? (string)$manifest['type'] : '';
    if ($type !== 'update' && $type !== 'module') {
        throw new MokkoUpdateException('Неизвестный тип пакета: нужен update или module.');
    }

    $slug = '';
    if ($type === 'module') {
        $slug = isset($manifest['module']) ? (string)$manifest['module'] : '';
        if (!mokko_module_slug_ok($slug)) {
            throw new MokkoUpdateException('Некорректный код модуля (a-z, 0-9, дефис; 2–32 символа).');
        }
    }

    $requires = isset($manifest['requires']) ? (string)$manifest['requires'] : '';
    if ($requires !== '' && version_compare(MOKKO_VERSION, $requires, '<')) {
        throw new MokkoUpdateException(
            'Пакет требует MOKKO CRM ' . $requires . ' или новее. Сейчас установлена ' . MOKKO_VERSION . '.'
        );
    }

    $sql = '';
    if (isset($manifest['sql']) && $manifest['sql'] !== '') {
        $sql = mokko_upd_normalize_path($manifest['sql']);
        if ($sql === null || !isset($entries[$sql])) {
            throw new MokkoUpdateException('SQL-файл из манифеста не найден в архиве.');
        }
        if (strtolower(pathinfo($sql, PATHINFO_EXTENSION)) !== 'sql') {
            throw new MokkoUpdateException('SQL-файл должен иметь расширение .sql.');
        }
        if ($type === 'module' && strpos($sql, 'modules/' . $slug . '/') !== 0) {
            throw new MokkoUpdateException('SQL модуля должен лежать внутри modules/' . $slug . '/.');
        }
    }

    $delete = array();
    if (isset($manifest['delete'])) {
        if (!is_array($manifest['delete'])) {
            throw new MokkoUpdateException('Поле delete должно быть массивом путей.');
        }
        foreach ($manifest['delete'] as $path) {
            $path = mokko_upd_normalize_path($path);
            if ($path === null || !mokko_upd_path_allowed($path, $type, $slug)) {
                throw new MokkoUpdateException('Недопустимый путь в delete: ' . (string)$path);
            }
            $delete[] = $path;
        }
    }

    $filesMap = null;
    if (isset($manifest['files'])) {
        if (!is_array($manifest['files'])) {
            throw new MokkoUpdateException('Поле files должно быть объектом «путь: sha256».');
        }
        $filesMap = array();
        foreach ($manifest['files'] as $path => $hash) {
            $path = mokko_upd_normalize_path($path);
            if ($path === null || ($path !== $sql && !mokko_upd_path_allowed($path, $type, $slug))) {
                throw new MokkoUpdateException('Недопустимый путь в files: ' . (string)$path);
            }
            $hash = strtolower((string)$hash);
            if (!preg_match('/^[a-f0-9]{64}$/', $hash)) {
                throw new MokkoUpdateException('Некорректная контрольная сумма для ' . $path . '.');
            }
            $filesMap[$path] = $hash;
        }

        $compareEntries = $entries;
        if ($sql !== '' && isset($compareEntries[$sql])) {
            unset($compareEntries[$sql]);
        }
        $missing = array_diff(array_keys($compareEntries), array_keys($filesMap));
        $extra = array_diff(array_keys($filesMap), array_keys($compareEntries));
        if (!empty($missing) || !empty($extra)) {
            throw new MokkoUpdateException(
                'Список files не совпадает с содержимым архива'
                . (!empty($missing) ? ' (нет в files: ' . implode(', ', array_slice($missing, 0, 3)) . ')' : '')
                . (!empty($extra) ? ' (лишние в files: ' . implode(', ', array_slice($extra, 0, 3)) . ')' : '')
                . '.'
            );
        }
    }

    return array(
        'id'          => $id,
        'title'       => mokko_upd_text(isset($manifest['title']) ? $manifest['title'] : $id, 120),
        'version'     => $version,
        'type'        => $type,
        'module'      => $slug,
        'description' => mokko_upd_text(isset($manifest['description']) ? $manifest['description'] : '', 300),
        'requires'    => $requires,
        'sql'         => $sql,
        'delete'      => array_values(array_unique($delete)),
        'files'       => $filesMap,
    );
}

/**
 * Полная проверка архива без распаковки.
 *
 * @return array(manifest, files[], total_bytes, warnings[], signature)
 */
function mokko_upd_inspect_zip($zipPath)
{
    $limits = mokko_upd_limits();
    $size = @filesize($zipPath);
    if ($size === false || $size <= 0) {
        throw new MokkoUpdateException('Файл пакета пуст или недоступен.');
    }
    if ($size > $limits['zip_bytes']) {
        throw new MokkoUpdateException('Архив больше ' . round($limits['zip_bytes'] / 1048576) . ' МБ.');
    }

    $zip = mokko_upd_open_zip($zipPath);

    try {
        $rawManifest = $zip->getFromName('mokko-update.json');
        if ($rawManifest === false || $rawManifest === '') {
            throw new MokkoUpdateException('В корне архива нет mokko-update.json.');
        }
        if (strlen($rawManifest) > 262144) {
            throw new MokkoUpdateException('Манифест слишком большой.');
        }
        $manifestData = json_decode($rawManifest, true);
        if (!is_array($manifestData)) {
            throw new MokkoUpdateException('mokko-update.json — некорректный JSON.');
        }

        $type = isset($manifestData['type']) ? (string)$manifestData['type'] : '';
        $slug = isset($manifestData['module']) ? (string)$manifestData['module'] : '';
        $sqlPath = isset($manifestData['sql']) && $manifestData['sql'] !== ''
            ? mokko_upd_normalize_path($manifestData['sql'])
            : null;

        if ($zip->numFiles > $limits['files']) {
            throw new MokkoUpdateException('В архиве слишком много файлов (' . (int)$zip->numFiles . ').');
        }

        $entries = array();
        $total = 0;
        $seen = array();

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if (!is_array($stat) || !isset($stat['name'])) {
                throw new MokkoUpdateException('Не удалось прочитать содержимое архива.');
            }

            $name = str_replace('\\', '/', $stat['name']);
            if (substr($name, -1) === '/') {
                continue; // каталог
            }
            if ($name === 'mokko-update.json' || $name === 'mokko-update.sig') {
                continue;
            }
            if (mokko_upd_zip_entry_is_link($zip, $i)) {
                throw new MokkoUpdateException('Архив содержит символьную ссылку: ' . $name);
            }

            $path = mokko_upd_normalize_path($name);
            if ($path === null) {
                throw new MokkoUpdateException('Недопустимый путь в архиве: ' . $name);
            }
            if (isset($seen[$path])) {
                throw new MokkoUpdateException('Дублирующийся файл в архиве: ' . $path);
            }
            $isSql = ($sqlPath !== null && $path === $sqlPath);
            if (!$isSql && !mokko_upd_path_allowed($path, $type, $slug)) {
                throw new MokkoUpdateException('Файл запрещён к установке: ' . $path);
            }

            $entrySize = isset($stat['size']) ? (int)$stat['size'] : 0;
            $compSize = isset($stat['comp_size']) ? (int)$stat['comp_size'] : 0;
            if ($entrySize > $limits['file_bytes']) {
                throw new MokkoUpdateException('Файл слишком большой: ' . $path);
            }
            if ($compSize > 0 && $entrySize > 1048576 && ($entrySize / max(1, $compSize)) > $limits['ratio']) {
                throw new MokkoUpdateException('Подозрительно сильное сжатие файла: ' . $path);
            }

            $total += $entrySize;
            if ($total > $limits['total_bytes']) {
                throw new MokkoUpdateException('Распакованный размер архива превышает допустимый.');
            }

            $seen[$path] = true;
            $entries[$path] = array('size' => $entrySize, 'comp_size' => $compSize);
        }

        if (empty($entries)) {
            throw new MokkoUpdateException('В архиве нет файлов для установки.');
        }

        $manifest = mokko_upd_validate_manifest($manifestData, $entries);
        $warnings = array();

        // Подпись: HMAC-SHA256 от байтов манифеста, если на сервере есть ключ.
        $signature = 'none';
        $sigRaw = $zip->getFromName('mokko-update.sig');
        $secret = mokko_upd_secret();
        if ($sigRaw !== false && trim($sigRaw) !== '') {
            $sig = strtolower(trim($sigRaw));
            if ($secret === null) {
                $signature = 'unverified';
                $warnings[] = 'Пакет подписан, но на сервере нет config/update.secret — подпись не проверена.';
            } elseif (!preg_match('/^[a-f0-9]{64}$/', $sig) || !hash_equals(hash_hmac('sha256', $rawManifest, $secret), $sig)) {
                throw new MokkoUpdateException('Подпись пакета не совпадает. Установка отменена.');
            } else {
                $signature = 'valid';
            }
        } elseif (is_file(MOKKO_ROOT . '/config/update.require_signature')) {
            throw new MokkoUpdateException('Пакет не подписан, а на сервере включена обязательная подпись.');
        } elseif ($secret !== null) {
            $warnings[] = 'Пакет без подписи: целостность проверена только по SHA-256.';
        }

        // Доменная привязка: пакет, собранный для конкретного домена, не ставится
        // на чужую копию. Поле domain входит в подписанный манифест, поэтому
        // подменить его, не сломав подпись, нельзя. (Основную защиту даёт
        // персональный секрет копии — чужой пакет не пройдёт проверку подписи;
        // это дополнительно даёт понятную ошибку.)
        $pkgDomain = isset($manifestData['domain']) ? trim((string)$manifestData['domain']) : '';
        if ($pkgDomain !== '') {
            $localDomain = mokko_upd_license_domain();
            if ($localDomain !== '' && strcasecmp($pkgDomain, $localDomain) !== 0) {
                throw new MokkoUpdateException('Пакет собран для домена «' . $pkgDomain . '», а копия установлена на «' . $localDomain . '». Установка отменена.');
            }
        }

        $files = array();
        $sqlHash = '';
        foreach ($entries as $path => $meta) {
            $hash = mokko_upd_zip_hash($zip, $path);
            if ($hash === null) {
                throw new MokkoUpdateException('Не удалось прочитать файл из архива: ' . $path);
            }
            if ($manifest['files'] !== null && isset($manifest['files'][$path])) {
                if (!hash_equals($manifest['files'][$path], $hash)) {
                    throw new MokkoUpdateException('Контрольная сумма не совпадает: ' . $path . '. Архив повреждён или изменён.');
                }
            }
            if ($manifest['sql'] !== '' && $path === $manifest['sql']) {
                $sqlHash = $hash;
                continue;
            }
            $files[] = array(
                'path'   => $path,
                'size'   => $meta['size'],
                'sha256' => $hash,
            );
        }

        if ($manifest['files'] === null) {
            $warnings[] = 'В манифесте нет контрольных сумм files — целостность проверена по факту.';
        }

        return array(
            'manifest'  => $manifest,
            'files'     => $files,
            'sql_hash'  => $sqlHash,
            'total'     => $total,
            'warnings'  => $warnings,
            'signature' => $signature,
        );
    } finally {
        $zip->close();
    }
}

/* ── Приём загрузки ───────────────────────────────────────────────────────── */

function mokko_upd_purge_inbox($olderThan = 86400)
{
    $files = glob(mokko_upd_inbox_dir() . '/*');
    if (!is_array($files)) {
        return;
    }
    foreach ($files as $file) {
        if (is_file($file) && @filemtime($file) < time() - $olderThan) {
            @unlink($file);
        }
    }
}

function mokko_upd_save_upload($tmpName, $originalName)
{
    mokko_upd_ensure_storage();
    mokko_upd_purge_inbox();

    $originalName = basename((string)$originalName);
    if (strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) !== 'zip') {
        throw new MokkoUpdateException('Нужен файл с расширением .zip.');
    }

    $token = bin2hex(random_bytes(16));
    $zipPath = mokko_upd_inbox_dir() . '/' . $token . '.zip';

    $moved = @move_uploaded_file($tmpName, $zipPath);
    if (!$moved) {
        // Для CLI-тестов и нестандартных SAPI.
        $moved = @rename($tmpName, $zipPath);
    }
    if (!$moved) {
        throw new MokkoUpdateException('Не удалось сохранить загруженный файл.');
    }

    try {
        $inspection = mokko_upd_inspect_zip($zipPath);
    } catch (Throwable $e) {
        @unlink($zipPath);
        throw $e;
    }

    $meta = array(
        'token'       => $token,
        'original'    => mokko_upd_text($originalName, 120),
        'size'        => (int)filesize($zipPath),
        'uploaded_at' => date('c'),
        'manifest'    => $inspection['manifest'],
        'files'       => $inspection['files'],
        'total'       => $inspection['total'],
        'warnings'    => $inspection['warnings'],
        'signature'   => $inspection['signature'],
    );
    mokko_upd_write_json(mokko_upd_inbox_dir() . '/' . $token . '.json', $meta);

    return $meta;
}

function mokko_upd_meta($token)
{
    if (!preg_match('/^[a-f0-9]{32}$/', (string)$token)) {
        throw new MokkoUpdateException('Некорректный идентификатор пакета.');
    }
    $meta = mokko_upd_read_json(mokko_upd_inbox_dir() . '/' . $token . '.json');
    if ($meta === null) {
        throw new MokkoUpdateException('Пакет не найден. Загрузите его заново.');
    }
    if (!is_file(mokko_upd_inbox_dir() . '/' . $token . '.zip')) {
        throw new MokkoUpdateException('ZIP-файл пакета не найден. Загрузите его заново.');
    }
    return $meta;
}

/* ── БД: дамп, восстановление, SQL ────────────────────────────────────────── */

function mokko_upd_db_credentials()
{
    $local = array();
    $file = MOKKO_ROOT . '/config/db_local.php';
    if (is_file($file)) {
        $loaded = include $file;
        if (is_array($loaded)) {
            $local = $loaded;
        }
    }

    $pick = function ($key, $env, $default) use ($local) {
        if (isset($local[$key]) && $local[$key] !== '') {
            return (string)$local[$key];
        }
        $value = getenv($env);
        if ($value !== false && $value !== '') {
            return (string)$value;
        }
        return $default;
    };

    $pass = isset($local['pass']) ? (string)$local['pass'] : getenv('MOKKO_DB_PASS');
    if ($pass === false) {
        $pass = '';
    }

    return array(
        'host' => $pick('host', 'MOKKO_DB_HOST', 'localhost'),
        'name' => $pick('name', 'MOKKO_DB_NAME', 'mokko'),
        'user' => $pick('user', 'MOKKO_DB_USER', 'root'),
        'pass' => $pass,
    );
}

function mokko_upd_find_bin($names)
{
    $candidates = array();
    foreach ($names as $name) {
        $candidates[] = 'C:/xampp/mysql/bin/' . $name . '.exe';
        $candidates[] = '/usr/bin/' . $name;
        $candidates[] = '/usr/local/bin/' . $name;
        $candidates[] = '/opt/homebrew/bin/' . $name;
        $candidates[] = $name;
    }
    foreach ($candidates as $candidate) {
        if ($candidate === $name) {
            // Поиск в PATH.
            $path = getenv('PATH');
            if ($path === false) {
                continue;
            }
            foreach (explode(PATH_SEPARATOR, $path) as $dir) {
                foreach (array('', '.exe', '.bat', '.cmd') as $ext) {
                    $full = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $name . $ext;
                    if (is_file($full) && is_executable($full)) {
                        return $full;
                    }
                }
            }
            continue;
        }
        if (is_file($candidate)) {
            return $candidate;
        }
    }
    return null;
}

function mokko_upd_db_host_parts($host)
{
    $port = '';
    if (preg_match('/^\[(.+)\](?::(\d+))?$/', $host, $m)) {
        return array($m[1], isset($m[2]) ? $m[2] : '');
    }
    if (substr_count($host, ':') === 1) {
        list($h, $p) = explode(':', $host, 2);
        if (ctype_digit($p)) {
            return array($h, $p);
        }
    }
    return array($host, $port);
}

/**
 * Дамп базы перед обновлением. Возвращает путь к файлу или null.
 */
function mokko_upd_db_dump($targetFile)
{
    $bin = mokko_upd_find_bin(array('mysqldump', 'mariadb-dump'));
    if ($bin === null) {
        throw new MokkoUpdateException('Не найден mysqldump. Резервная копия БД не сделана.');
    }

    $db = mokko_upd_db_credentials();
    list($host, $port) = mokko_upd_db_host_parts($db['host']);

    $args = array(
        $bin,
        '--host=' . $host,
        '--user=' . $db['user'],
        '--single-transaction',
        '--quick',
        '--default-character-set=utf8mb4',
        '--add-drop-database',
        '--databases',
        $db['name'],
    );
    if ($port !== '') {
        array_splice($args, 2, 0, array('--port=' . $port));
    }

    // Windows: команда, начинающаяся с кавычек, ломается в cmd.exe.
    // Запускаем из каталога утилиты и передаём только её имя.
    $binName = basename($bin);
    $binDir = dirname($bin);
    if (!is_dir($binDir)) {
        $binDir = MOKKO_ROOT;
    }
    $cmd = (preg_match('/^[A-Za-z0-9._\-]+$/', $binName) ? $binName : escapeshellarg($binName))
        . ' ' . implode(' ', array_map('escapeshellarg', array_slice($args, 1)));

    $errFile = $targetFile . '.err';
    $descriptors = array(
        1 => array('file', $targetFile, 'w'),
        2 => array('file', $errFile, 'w'),
    );
    $env = getenv();
    if (!is_array($env)) {
        $env = array();
    }
    $env['MYSQL_PWD'] = $db['pass'];

    $proc = @proc_open($cmd, $descriptors, $pipes, $binDir, $env);
    if (!is_resource($proc)) {
        throw new MokkoUpdateException('Не удалось запустить mysqldump.');
    }
    $code = proc_close($proc);
    $err = is_file($errFile) ? trim((string)@file_get_contents($errFile)) : '';
    $err = mokko_upd_utf8($err);
    @unlink($errFile);

    if ($code !== 0 || !is_file($targetFile) || filesize($targetFile) === 0) {
        @unlink($targetFile);
        throw new MokkoUpdateException('mysqldump завершился с ошибкой: ' . ($err !== '' ? $err : 'код ' . $code));
    }

    return $targetFile;
}

function mokko_upd_db_restore($dumpFile)
{
    if (!is_file($dumpFile) || filesize($dumpFile) === 0) {
        throw new MokkoUpdateException('Дамп БД не найден.');
    }

    $bin = mokko_upd_find_bin(array('mysql', 'mariadb'));
    if ($bin === null) {
        throw new MokkoUpdateException('Не найден клиент mysql для восстановления БД.');
    }

    $db = mokko_upd_db_credentials();
    list($host, $port) = mokko_upd_db_host_parts($db['host']);

    $args = array(
        $bin,
        '--host=' . $host,
        '--user=' . $db['user'],
        '--default-character-set=utf8mb4',
    );
    if ($port !== '') {
        $args[] = '--port=' . $port;
    }

    $binName = basename($bin);
    $binDir = dirname($bin);
    if (!is_dir($binDir)) {
        $binDir = MOKKO_ROOT;
    }
    $cmd = (preg_match('/^[A-Za-z0-9._\-]+$/', $binName) ? $binName : escapeshellarg($binName))
        . ' ' . implode(' ', array_map('escapeshellarg', array_slice($args, 1)));

    $logFile = $dumpFile . '.restore.log';
    $descriptors = array(
        0 => array('file', $dumpFile, 'r'),
        1 => array('file', $logFile, 'w'),
        2 => array('file', $logFile, 'a'),
    );
    $env = getenv();
    if (!is_array($env)) {
        $env = array();
    }
    $env['MYSQL_PWD'] = $db['pass'];

    $proc = @proc_open($cmd, $descriptors, $pipes, $binDir, $env);
    if (!is_resource($proc)) {
        throw new MokkoUpdateException('Не удалось запустить mysql для восстановления.');
    }
    $code = proc_close($proc);
    $log = is_file($logFile) ? trim((string)@file_get_contents($logFile)) : '';
    $log = mokko_upd_utf8($log);
    @unlink($logFile);

    if ($code !== 0) {
        throw new MokkoUpdateException('Восстановление БД завершилось с ошибкой: ' . ($log !== '' ? $log : 'код ' . $code));
    }

    return true;
}

/**
 * Разбиение SQL на операторы. Поддерживаются строки, backticks и комментарии.
 * DELIMITER (хранимые процедуры) намеренно не поддерживается.
 */
function mokko_upd_split_sql($sql)
{
    $sql = (string)$sql;
    if (preg_match('/^\s*DELIMITER\b/im', $sql)) {
        throw new MokkoUpdateException('SQL-файл использует DELIMITER — такие миграции не поддерживаются.');
    }

    $out = array();
    $buf = '';
    $len = strlen($sql);
    $i = 0;
    $quote = null;

    while ($i < $len) {
        $ch = $sql[$i];

        if ($quote !== null) {
            $buf .= $ch;
            if ($ch === '\\' && $i + 1 < $len) {
                $buf .= $sql[$i + 1];
                $i += 2;
                continue;
            }
            if ($ch === $quote) {
                if ($i + 1 < $len && $sql[$i + 1] === $quote) {
                    $buf .= $quote;
                    $i += 2;
                    continue;
                }
                $quote = null;
            }
            $i++;
            continue;
        }

        if ($ch === "'" || $ch === '"' || $ch === '`') {
            $quote = $ch;
            $buf .= $ch;
            $i++;
            continue;
        }

        if ($ch === '-' && substr($sql, $i, 2) === '--' && ($i + 2 >= $len || ctype_space($sql[$i + 2]))) {
            $nl = strpos($sql, "\n", $i);
            $i = $nl === false ? $len : $nl + 1;
            continue;
        }
        if ($ch === '#') {
            $nl = strpos($sql, "\n", $i);
            $i = $nl === false ? $len : $nl + 1;
            continue;
        }
        if ($ch === '/' && substr($sql, $i, 2) === '/*') {
            $end = strpos($sql, '*/', $i + 2);
            $i = $end === false ? $len : $end + 2;
            continue;
        }
        if ($ch === ';') {
            $stmt = trim($buf);
            if ($stmt !== '') {
                $out[] = $stmt;
            }
            $buf = '';
            $i++;
            continue;
        }

        $buf .= $ch;
        $i++;
    }

    $stmt = trim($buf);
    if ($stmt !== '') {
        $out[] = $stmt;
    }

    return $out;
}

function mokko_upd_run_sql($file, &$done)
{
    $sql = @file_get_contents($file);
    if ($sql === false) {
        throw new MokkoUpdateException('Не удалось прочитать SQL-файл пакета.');
    }

    $statements = mokko_upd_split_sql($sql);
    if (empty($statements)) {
        throw new MokkoUpdateException('SQL-файл пуст.');
    }

    require_once MOKKO_ROOT . '/config/db.php';
    $pdo = getDB();

    $done = 0;
    foreach ($statements as $index => $statement) {
        try {
            $pdo->exec($statement);
        } catch (Exception $e) {
            $message = $e->getMessage();
            if (function_exists('mb_substr')) {
                $message = mb_substr($message, 0, 300, 'UTF-8');
            } else {
                $message = substr($message, 0, 300);
            }
            throw new MokkoUpdateException(
                'Ошибка SQL в операторе ' . ($index + 1) . ' из ' . count($statements) . ': ' . $message
            );
        }
        $done++;
    }

    return count($statements);
}

/* ── Установка ────────────────────────────────────────────────────────────── */

function mokko_upd_lock()
{
    $fp = @fopen(mokko_upd_lock_file(), 'c+');
    if (!$fp) {
        throw new MokkoUpdateException('Не удалось открыть lock-файл обновлений.');
    }
    if (!@flock($fp, LOCK_EX | LOCK_NB)) {
        fclose($fp);
        throw new MokkoUpdateException('Другое обновление уже выполняется. Подождите.');
    }
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode(array('time' => date('c'), 'pid' => getmypid())));
    fflush($fp);
    return $fp;
}

function mokko_upd_unlock($fp)
{
    if (is_resource($fp)) {
        @flock($fp, LOCK_UN);
        @fclose($fp);
    }
}

function mokko_upd_backup_file($abs, $backupDir, $path)
{
    $target = $backupDir . '/files/' . $path;
    $dir = dirname($target);
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new MokkoUpdateException('Не удалось создать каталог резервной копии.');
    }
    if (!@copy($abs, $target)) {
        throw new MokkoUpdateException('Не удалось скопировать ' . $path . ' в резервную копию.');
    }
    @chmod($target, 0640);
}

function mokko_upd_replace_file($source, $target)
{
    $dir = dirname($target);
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new MokkoUpdateException('Не удалось создать каталог ' . $dir . '.');
    }

    $tmp = $target . '.upd-' . bin2hex(random_bytes(4));
    if (!@copy($source, $tmp)) {
        throw new MokkoUpdateException('Не удалось записать ' . $target . '. Проверьте права.');
    }
    @chmod($tmp, 0644);

    if (!@rename($tmp, $target)) {
        // Windows не всегда переименовывает поверх существующего файла.
        @unlink($target);
        if (!@rename($tmp, $target)) {
            @unlink($tmp);
            throw new MokkoUpdateException('Не удалось заменить ' . $target . '.');
        }
    }
}

function mokko_upd_extract($zipPath, $files, $dest)
{
    $zip = mokko_upd_open_zip($zipPath);
    try {
        foreach ($files as $file) {
            $path = $file['path'];
            $target = $dest . '/' . $path;
            $dir = dirname($target);
            if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new MokkoUpdateException('Не удалось создать ' . $dir . '.');
            }

            $in = $zip->getStream($path);
            if (!$in) {
                throw new MokkoUpdateException('Не удалось прочитать ' . $path . ' из архива.');
            }
            $out = @fopen($target, 'wb');
            if (!$out) {
                fclose($in);
                throw new MokkoUpdateException('Не удалось записать ' . $target . '.');
            }

            $ctx = hash_init('sha256');
            while (!feof($in)) {
                $chunk = fread($in, 1048576);
                if ($chunk === false) {
                    break;
                }
                if ($chunk !== '') {
                    hash_update($ctx, $chunk);
                    fwrite($out, $chunk);
                }
            }
            fclose($in);
            fclose($out);
            @chmod($target, 0644);

            $actual = hash_file('sha256', $target);
            if (!hash_equals($file['sha256'], (string)$actual)) {
                throw new MokkoUpdateException('Контрольная сумма после распаковки не совпала: ' . $path . '.');
            }
        }
    } finally {
        $zip->close();
    }
}

function mokko_upd_save_state($backupDir, $state)
{
    mokko_upd_write_json($backupDir . '/state.json', $state);
}

function mokko_upd_write_version($version)
{
    $version = preg_replace('/[^0-9A-Za-z.\-+]/', '', (string)$version);
    if ($version === '') {
        return;
    }
    $php = "<?php\n"
        . "/**\n"
        . " * Версия установленной копии MOKKO CRM.\n"
        . " * Файл перезаписывается разделом «Обновления».\n"
        . " */\n\n"
        . "if (!defined('MOKKO_VERSION')) {\n"
        . "    define('MOKKO_VERSION', '" . addslashes($version) . "');\n"
        . "}\n\n"
        . "if (!defined('MOKKO_BUILD')) {\n"
        . "    define('MOKKO_BUILD', '" . date('Y-m-d') . "');\n"
        . "}\n";

    $target = MOKKO_ROOT . '/config/version.php';
    $tmp = $target . '.tmp' . bin2hex(random_bytes(4));
    if (@file_put_contents($tmp, $php, LOCK_EX) === false || !@rename($tmp, $target)) {
        @unlink($tmp);
        throw new MokkoUpdateException('Не удалось записать config/version.php.');
    }
    @chmod($target, 0644);
}

function mokko_upd_revert_files($backupDir, $state)
{
    $errors = array();
    $actions = isset($state['actions']) && is_array($state['actions']) ? $state['actions'] : array();

    for ($i = count($actions) - 1; $i >= 0; $i--) {
        $action = $actions[$i];
        if (empty($action['done'])) {
            continue;
        }

        $path = $action['path'];
        $abs = MOKKO_ROOT . '/' . $path;
        $backup = $backupDir . '/files/' . $path;

        if ($action['op'] === 'write') {
            if (!empty($action['existed'])) {
                if (!is_file($backup) || !@copy($backup, $abs)) {
                    $errors[] = $path;
                }
            } elseif (is_file($abs)) {
                @unlink($abs);
            }
        } elseif ($action['op'] === 'delete') {
            if (!empty($action['existed'])) {
                if (!is_file($backup) || !@copy($backup, $abs)) {
                    $errors[] = $path;
                }
            }
        }
    }

    if (!empty($errors)) {
        throw new MokkoUpdateException(
            'Не удалось вернуть файлы: ' . implode(', ', array_slice($errors, 0, 5))
        );
    }
}

function mokko_upd_auto_revert($backupDir, &$state, $originalMessage)
{
    $originalMessage = mokko_upd_utf8($originalMessage);
    $state['status'] = 'failed';
    $state['error'] = $originalMessage;
    $state['failed_at'] = date('c');
    mokko_upd_save_state($backupDir, $state);

    $messages = array($originalMessage);

    try {
        mokko_upd_revert_files($backupDir, $state);
        $messages[] = 'Файлы возвращены в исходное состояние.';
    } catch (Throwable $e) {
        $messages[] = $e->getMessage();
    }

    if (!empty($state['db_dump']) && !empty($state['sql_started'])) {
        try {
            mokko_upd_db_restore($backupDir . '/' . $state['db_dump']);
            $messages[] = 'База данных восстановлена из дампа.';
        } catch (Throwable $e) {
            $messages[] = 'БД не восстановлена: ' . $e->getMessage();
        }
    }

    mokko_upd_remove_tree(mokko_upd_staging_dir() . '/' . $state['token']);

    if (!empty($state['version_written']) && !empty($state['version_backup'])) {
        $bak = $backupDir . '/' . $state['version_backup'];
        if (is_file($bak)) {
            @copy($bak, MOKKO_ROOT . '/config/version.php');
        }
    }

    if (!empty($state['maintenance'])) {
        mokko_maintenance_end();
    }

    mokko_upd_history_add(array(
        'token'   => $state['token'],
        'time'    => date('c'),
        'type'    => $state['type'],
        'id'      => $state['id'],
        'title'   => $state['title'],
        'version' => $state['version'],
        'module'  => $state['module'],
        'files'   => count($state['actions']),
        'status'  => 'failed',
        'error'   => $originalMessage,
    ));

    throw new MokkoUpdateException(implode(' ', $messages));
}

/**
 * Установка пакета.
 *
 * @param string $token токен из mokko_upd_save_upload()
 * @param array  $opts  maintenance, db_backup, allow_reinstall, force_db
 * @return array
 */
function mokko_upd_apply($token, $opts = array())
{
    $opts = array_merge(array(
        'maintenance'     => true,
        'db_backup'       => true,
        'allow_reinstall' => false,
        'force_db'        => false,
    ), is_array($opts) ? $opts : array());

    mokko_upd_ensure_storage();
    $meta = mokko_upd_meta($token);
    $zipPath = mokko_upd_inbox_dir() . '/' . $token . '.zip';
    $started = microtime(true);
    $lock = mokko_upd_lock();

    $backupDir = mokko_upd_backups_dir() . '/' . $token;
    $state = null;

    try {
        $inspection = mokko_upd_inspect_zip($zipPath);
        $manifest = $inspection['manifest'];
        $files = $inspection['files'];

        // Проверка версии.
        if ($manifest['type'] === 'update') {
            $cmp = version_compare($manifest['version'], MOKKO_VERSION);
            if ($cmp < 0) {
                throw new MokkoUpdateException(
                    'Нельзя откатиться на старую версию ' . $manifest['version'] . ' (сейчас ' . MOKKO_VERSION . ').'
                );
            }
            if ($cmp === 0 && !$opts['allow_reinstall']) {
                throw new MokkoUpdateException(
                    'Версия ' . $manifest['version'] . ' уже установлена. Поставьте галочку «Переустановить».'
                );
            }
        } else {
            $installed = mokko_modules_scan();
            if (isset($installed[$manifest['module']])) {
                $current = $installed[$manifest['module']]['version'];
                $cmp = version_compare($manifest['version'], $current);
                if ($cmp < 0) {
                    throw new MokkoUpdateException('Установлена более новая версия модуля (' . $current . ').');
                }
                if ($cmp === 0 && !$opts['allow_reinstall']) {
                    throw new MokkoUpdateException(
                        'Модуль ' . $manifest['module'] . ' версии ' . $current . ' уже установлен.'
                    );
                }
            }
        }

        if (is_dir($backupDir)) {
            throw new MokkoUpdateException('Резервная копия для этого пакета уже существует. Загрузите пакет заново.');
        }
        if (!@mkdir($backupDir, 0755, true) && !is_dir($backupDir)) {
            throw new MokkoUpdateException('Не удалось создать каталог резервной копии.');
        }

        $state = array(
            'token'        => $token,
            'created_at'   => date('c'),
            'status'       => 'preparing',
            'type'         => $manifest['type'],
            'id'           => $manifest['id'],
            'title'        => $manifest['title'],
            'version'      => $manifest['version'],
            'module'       => $manifest['module'],
            'from_version' => MOKKO_VERSION,
            'sql'          => $manifest['sql'],
            'db_dump'      => null,
            'sql_started'  => 0,
            'maintenance'  => false,
            'actions'      => array(),
            'error'        => '',
        );

        // Резервная копия заменяемых и удаляемых файлов.
        $paths = array();
        foreach ($files as $file) {
            $paths[$file['path']] = 'write';
        }
        foreach ($manifest['delete'] as $path) {
            if (!isset($paths[$path])) {
                $paths[$path] = 'delete';
            }
        }

        foreach ($paths as $path => $op) {
            $abs = MOKKO_ROOT . '/' . $path;
            if (is_dir($abs)) {
                throw new MokkoUpdateException('Путь занят каталогом: ' . $path);
            }
            $exists = is_file($abs);
            if ($exists) {
                mokko_upd_backup_file($abs, $backupDir, $path);
            }
            $state['actions'][] = array(
                'op'          => $op,
                'path'        => $path,
                'existed'     => $exists,
                'before_sha'  => $exists ? hash_file('sha256', $abs) : '',
                'applied_sha' => '',
                'done'        => false,
            );
        }

        // Дамп БД — до любых изменений.
        if ($manifest['sql'] !== '' && $opts['db_backup']) {
            try {
                $dump = $backupDir . '/db.sql';
                mokko_upd_db_dump($dump);
                $state['db_dump'] = 'db.sql';
            } catch (Throwable $e) {
                if (!$opts['force_db']) {
                    throw new MokkoUpdateException(
                        $e->getMessage() . ' Установка остановлена, файлы не изменены. '
                        . 'Можно повторить с галочкой «Продолжить без дампа БД».'
                    );
                }
                $state['db_warning'] = $e->getMessage();
            }
        }

        // Распаковка во временный каталог.
        $stage = mokko_upd_staging_dir() . '/' . $token;
        mokko_upd_remove_tree($stage);
        if (!@mkdir($stage, 0755, true) && !is_dir($stage)) {
            throw new MokkoUpdateException('Не удалось создать каталог распаковки.');
        }
        $extract = $files;
        if ($manifest['sql'] !== '') {
            $extract[] = array('path' => $manifest['sql'], 'sha256' => $inspection['sql_hash']);
        }
        mokko_upd_extract($zipPath, $extract, $stage);

        $state['status'] = 'applying';
        mokko_upd_save_state($backupDir, $state);

        if ($opts['maintenance']) {
            $state['maintenance'] = mokko_maintenance_begin('update ' . $manifest['id'] . ' ' . $manifest['version']);
            mokko_upd_save_state($backupDir, $state);
        }

        // Подмена файлов.
        foreach ($state['actions'] as $index => $action) {
            $path = $action['path'];
            $abs = MOKKO_ROOT . '/' . $path;

            if ($action['op'] === 'write') {
                mokko_upd_replace_file($stage . '/' . $path, $abs);
                $state['actions'][$index]['done'] = true;
                $state['actions'][$index]['applied_sha'] = hash_file('sha256', $abs);
            } else {
                if (is_file($abs) && !@unlink($abs)) {
                    throw new MokkoUpdateException('Не удалось удалить ' . $path . '.');
                }
                $state['actions'][$index]['done'] = true;
            }
            mokko_upd_save_state($backupDir, $state);
        }

        // SQL.
        $sqlCount = 0;
        if ($manifest['sql'] !== '') {
            $state['sql_started'] = 1;
            mokko_upd_save_state($backupDir, $state);
            $done = 0;
            $sqlCount = mokko_upd_run_sql($stage . '/' . $manifest['sql'], $done);
        }

        if ($manifest['type'] === 'update') {
            // Сохраняем прежний version.php: при сбое после записи вернём его.
            $versionFile = MOKKO_ROOT . '/config/version.php';
            if (is_file($versionFile) && @copy($versionFile, $backupDir . '/version.php.bak')) {
                $state['version_backup'] = 'version.php.bak';
            }
            mokko_upd_write_version($manifest['version']);
            $state['version_written'] = true;
            mokko_upd_save_state($backupDir, $state);
        }

        $state['status'] = 'applied';
        $state['applied_at'] = date('c');
        $state['duration'] = round(microtime(true) - $started, 2);
        mokko_upd_save_state($backupDir, $state);

        if ($state['maintenance']) {
            mokko_maintenance_end();
        }

        // Чистим временные файлы, резервную копию оставляем.
        mokko_upd_remove_tree($stage);
        @unlink($zipPath);
        @unlink(mokko_upd_inbox_dir() . '/' . $token . '.json');

        mokko_upd_history_add(array(
            'token'   => $token,
            'time'    => date('c'),
            'type'    => $manifest['type'],
            'id'      => $manifest['id'],
            'title'   => $manifest['title'],
            'version' => $manifest['version'],
            'module'  => $manifest['module'],
            'files'   => count($state['actions']),
            'status'  => 'applied',
        ));

        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
        clearstatcache(true);
        if (function_exists('mokko_modules_reset_cache')) {
            mokko_modules_reset_cache();
        }

        return array(
            'token'      => $token,
            'version'    => $manifest['version'],
            'title'      => $manifest['title'],
            'type'       => $manifest['type'],
            'module'     => $manifest['module'],
            'files'      => count($state['actions']),
            'sql'        => $sqlCount,
            'duration'   => $state['duration'],
            'backup'     => 'storage/updates/backups/' . $token,
            'warning'    => isset($state['db_warning']) ? $state['db_warning'] : '',
        );
    } catch (Throwable $e) {
        if ($state !== null) {
            $state['sql_started'] = !empty($state['sql_started']) ? $state['sql_started'] : 0;
            mokko_upd_auto_revert($backupDir, $state, $e->getMessage());
        }
        throw $e;
    } finally {
        mokko_upd_unlock($lock);
    }
}

/* ── Откат ────────────────────────────────────────────────────────────────── */

function mokko_upd_rollback($token, $restoreDb = false, $force = false)
{
    mokko_upd_ensure_storage();
    if (!preg_match('/^[a-f0-9]{32}$/', (string)$token)) {
        throw new MokkoUpdateException('Некорректный идентификатор резервной копии.');
    }

    $backupDir = mokko_upd_backups_dir() . '/' . $token;
    $state = mokko_upd_read_json($backupDir . '/state.json');
    if ($state === null) {
        throw new MokkoUpdateException('Резервная копия не найдена.');
    }
    if ($state['status'] !== 'applied') {
        throw new MokkoUpdateException('Этот пакет нельзя откатить: статус «' . $state['status'] . '».');
    }

    $lock = mokko_upd_lock();
    try {
        // Проверяем, что после обновления файлы не правили руками.
        if (!$force) {
            $changed = array();
            foreach ($state['actions'] as $action) {
                if (empty($action['done']) || $action['op'] !== 'write') {
                    continue;
                }
                $abs = MOKKO_ROOT . '/' . $action['path'];
                if (!is_file($abs) || hash_file('sha256', $abs) !== $action['applied_sha']) {
                    $changed[] = $action['path'];
                }
            }
            if (!empty($changed)) {
                throw new MokkoUpdateException(
                    'После обновления изменены файлы: ' . implode(', ', array_slice($changed, 0, 5))
                    . '. Откат отменён, чтобы не потерять правки.'
                );
            }
        }

        $state['status'] = 'rolling_back';
        mokko_upd_save_state($backupDir, $state);

        mokko_upd_revert_files($backupDir, $state);

        $dbMessage = '';
        if (!empty($state['db_dump'])) {
            if ($restoreDb) {
                mokko_upd_db_restore($backupDir . '/' . $state['db_dump']);
                $dbMessage = 'База данных восстановлена из дампа.';
            } else {
                $dbMessage = 'БД не изменялась; дамп сохранён в ' . $backupDir . '/db.sql.';
            }
        }

        if ($state['type'] === 'update' && !empty($state['from_version'])) {
            mokko_upd_write_version($state['from_version']);
        }

        $state['status'] = 'rolled_back';
        $state['rolled_back_at'] = date('c');
        mokko_upd_save_state($backupDir, $state);

        mokko_upd_history_add(array(
            'token'   => $token,
            'time'    => date('c'),
            'type'    => $state['type'],
            'id'      => $state['id'],
            'title'   => $state['title'],
            'version' => $state['version'],
            'module'  => $state['module'],
            'files'   => count($state['actions']),
            'status'  => 'rolled_back',
        ));

        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
        clearstatcache(true);
        if (function_exists('mokko_modules_reset_cache')) {
            mokko_modules_reset_cache();
        }

        return array(
            'ok'      => true,
            'token'   => $token,
            'version' => $state['from_version'],
            'message' => 'Файлы возвращены к версии ' . $state['from_version'] . '. ' . $dbMessage,
        );
    } finally {
        mokko_upd_unlock($lock);
    }
}

/* ── История ──────────────────────────────────────────────────────────────── */

function mokko_upd_history()
{
    $data = mokko_upd_read_json(mokko_upd_history_file());
    return is_array($data) ? $data : array();
}

function mokko_upd_history_add($entry)
{
    mokko_upd_ensure_storage();
    $file = mokko_upd_history_file();
    $fp = @fopen($file, 'c+');
    if (!$fp) {
        return false;
    }

    $ok = false;
    if (@flock($fp, LOCK_EX)) {
        $raw = stream_get_contents($fp);
        $data = json_decode((string)$raw, true);
        if (!is_array($data)) {
            $data = array();
        }
        array_unshift($data, $entry);
        $data = array_slice($data, 0, 100);

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json !== false) {
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, $json);
            fflush($fp);
            $ok = true;
        }
        @flock($fp, LOCK_UN);
    }
    fclose($fp);

    return $ok;
}

/* ── Служебное для UI ─────────────────────────────────────────────────────── */

function mokko_upd_module_installed_version($slug)
{
    $all = mokko_modules_scan();
    return isset($all[$slug]) ? $all[$slug]['version'] : '';
}

function mokko_upd_format_bytes($bytes)
{
    $bytes = (int)$bytes;
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 1) . ' МБ';
    }
    if ($bytes >= 1024) {
        return round($bytes / 1024) . ' КБ';
    }
    return $bytes . ' Б';
}

/**
 * Состояние раздела для страницы и API (без секретов).
 */
function mokko_upd_ui_state()
{
    $modules = array();
    foreach (mokko_modules_scan() as $slug => $module) {
        $modules[] = array(
            'slug'        => $slug,
            'title'       => $module['title'],
            'version'     => $module['version'],
            'description' => $module['description'],
            'enabled'     => (bool)$module['enabled'],
            'error'       => $module['error'],
        );
    }

    $history = array();
    foreach (mokko_upd_history() as $item) {
        $history[] = array(
            'token'        => isset($item['token']) ? $item['token'] : '',
            'time'         => isset($item['time']) ? $item['time'] : '',
            'type'         => isset($item['type']) ? $item['type'] : '',
            'id'           => isset($item['id']) ? $item['id'] : '',
            'title'        => isset($item['title']) ? $item['title'] : '',
            'version'      => isset($item['version']) ? $item['version'] : '',
            'module'       => isset($item['module']) ? $item['module'] : '',
            'files'        => isset($item['files']) ? (int)$item['files'] : 0,
            'status'       => isset($item['status']) ? $item['status'] : '',
            'error'        => isset($item['error']) ? $item['error'] : '',
            'can_rollback' => isset($item['status']) && $item['status'] === 'applied',
        );
    }

    $limits = mokko_upd_limits();

    return array(
        'version'     => MOKKO_VERSION,
        'build'       => defined('MOKKO_BUILD') ? MOKKO_BUILD : '',
        'maintenance' => mokko_in_maintenance(),
        'secret'      => mokko_upd_secret() !== null,
        'require_sig' => is_file(MOKKO_ROOT . '/config/update.require_signature'),
        'limits'      => array(
            'zip'   => $limits['zip_bytes'],
            'max'   => mokko_upd_max_upload(),
            'files' => $limits['files'],
        ),
        'writable'    => mokko_upd_writable_report(),
        'modules'     => $modules,
        'history'     => $history,
    );
}

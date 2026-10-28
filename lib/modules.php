<?php
/**
 * MOKKO CRM — модули и хуки.
 *
 * Модуль — это каталог modules/<slug>/ с файлом module.json:
 *
 *   {
 *     "title":       "Лояльность",
 *     "version":     "1.0.0",
 *     "description": "Бонусы и скидки",
 *     "bootstrap":   "bootstrap.php",
 *     "requires":    "1.0.0"
 *   }
 *
 * bootstrap.php выполняется при каждом запросе (как плагин CMS) и может
 * регистрировать хуки:
 *
 *   mokko_add_hook('panel.nav',    function () { echo '<li>...</li>'; });
 *   mokko_add_hook('panel.layers', function () { echo '<div class="layer">...</div>'; });
 *
 * Доступные точки: app.boot, panel.head, panel.nav, panel.layers, panel.footer.
 *
 * ВАЖНО: код модуля выполняется с правами веб-сервера. Устанавливать модули
 * может только администратор, источник пакета должен быть доверенным.
 */

if (defined('MOKKO_MODULES')) {
    return;
}
define('MOKKO_MODULES', 1);

if (!defined('MOKKO_ROOT')) {
    define('MOKKO_ROOT', dirname(__DIR__));
}

function mokko_modules_dir()
{
    return MOKKO_ROOT . '/modules';
}

if (!function_exists('mokko_module_slug_ok')) {
    function mokko_module_slug_ok($slug)
    {
        return is_string($slug) && preg_match('/^[a-z0-9][a-z0-9\-]{1,31}$/', $slug) === 1;
    }
}

function mokko_modules_reset_cache()
{
    $GLOBALS['mokko_modules_cache'] = null;
}

/**
 * Список установленных модулей (по каталогам, без БД).
 *
 * @return array slug => array(slug,title,version,description,enabled,bootstrap,error)
 */
function mokko_modules_scan()
{
    if (isset($GLOBALS['mokko_modules_cache']) && is_array($GLOBALS['mokko_modules_cache'])) {
        return $GLOBALS['mokko_modules_cache'];
    }

    $cache = array();
    $dir = mokko_modules_dir();
    if (!is_dir($dir)) {
        $GLOBALS['mokko_modules_cache'] = $cache;
        return $cache;
    }

    $files = glob($dir . '/*/module.json');
    if (!is_array($files)) {
        $GLOBALS['mokko_modules_cache'] = $cache;
        return $cache;
    }

    foreach ($files as $file) {
        $slug = basename(dirname($file));
        if (!mokko_module_slug_ok($slug)) {
            continue;
        }

        $item = array(
            'slug'        => $slug,
            'title'       => $slug,
            'version'     => '0.0.0',
            'description' => '',
            'enabled'     => !is_file(dirname($file) . '/.disabled'),
            'bootstrap'   => '',
            'error'       => '',
        );

        $raw = @file_get_contents($file);
        $data = $raw === false ? null : json_decode($raw, true);
        if (!is_array($data)) {
            $item['error'] = 'Некорректный module.json';
            $cache[$slug] = $item;
            continue;
        }

        $item['title']       = isset($data['title']) && is_string($data['title']) && $data['title'] !== ''
            ? mb_substr(strip_tags($data['title']), 0, 120, 'UTF-8')
            : $slug;
        $item['version']     = isset($data['version']) && is_string($data['version']) ? $data['version'] : '0.0.0';
        $item['description'] = isset($data['description']) && is_string($data['description'])
            ? mb_substr(strip_tags($data['description']), 0, 300, 'UTF-8')
            : '';
        $item['bootstrap']   = isset($data['bootstrap']) && is_string($data['bootstrap'])
            ? $data['bootstrap']
            : '';

        $cache[$slug] = $item;
    }

    ksort($cache);
    $GLOBALS['mokko_modules_cache'] = $cache;
    return $cache;
}

function mokko_module_enabled($slug)
{
    $all = mokko_modules_scan();
    return isset($all[$slug]) && $all[$slug]['enabled'];
}

/**
 * Включить/выключить модуль. Выключение — файл .disabled, без изменения кода.
 */
function mokko_module_set_enabled($slug, $enabled)
{
    if (!mokko_module_slug_ok($slug)) {
        throw new InvalidArgumentException('Некорректный код модуля.');
    }

    $dir = mokko_modules_dir() . '/' . $slug;
    if (!is_dir($dir)) {
        throw new RuntimeException('Модуль не найден.');
    }

    $flag = $dir . '/.disabled';
    if ($enabled) {
        if (is_file($flag) && !@unlink($flag)) {
            throw new RuntimeException('Не удалось включить модуль: нет прав на запись.');
        }
    } else {
        if (!@file_put_contents($flag, date('c') . "\n")) {
            throw new RuntimeException('Не удалось выключить модуль: нет прав на запись.');
        }
    }

    mokko_modules_reset_cache();
    return true;
}

/**
 * Переместить модуль в storage/updates/removed (без безвозвратного удаления).
 */
function mokko_module_remove($slug)
{
    if (!mokko_module_slug_ok($slug)) {
        throw new InvalidArgumentException('Некорректный код модуля.');
    }

    $dir = mokko_modules_dir() . '/' . $slug;
    if (!is_dir($dir)) {
        throw new RuntimeException('Модуль не найден.');
    }

    $removed = MOKKO_ROOT . '/storage/updates/removed';
    if (!is_dir($removed) && !@mkdir($removed, 0755, true) && !is_dir($removed)) {
        throw new RuntimeException('Не удалось создать каталог для удалённых модулей.');
    }

    $target = $removed . '/' . $slug . '-' . date('Ymd-His');
    if (!@rename($dir, $target)) {
        throw new RuntimeException('Не удалось переместить модуль. Проверьте права на каталог modules/.');
    }

    mokko_modules_reset_cache();
    return $target;
}

/* ── Хуки ─────────────────────────────────────────────────────────────────── */

$GLOBALS['mokko_hooks'] = isset($GLOBALS['mokko_hooks']) && is_array($GLOBALS['mokko_hooks'])
    ? $GLOBALS['mokko_hooks']
    : array();

function mokko_add_hook($name, $callback)
{
    $name = (string)$name;
    if ($name === '' || !is_callable($callback)) {
        return false;
    }
    if (!isset($GLOBALS['mokko_hooks'][$name])) {
        $GLOBALS['mokko_hooks'][$name] = array();
    }
    $GLOBALS['mokko_hooks'][$name][] = $callback;
    return true;
}

function mokko_hook($name, $args = array())
{
    $name = (string)$name;
    if (empty($GLOBALS['mokko_hooks'][$name])) {
        return;
    }
    foreach ($GLOBALS['mokko_hooks'][$name] as $callback) {
        try {
            call_user_func($callback, is_array($args) ? $args : array());
        } catch (Throwable $e) {
            if (function_exists('mokko_log')) {
                mokko_log('hook ' . $name . ': ' . $e->getMessage());
            }
        }
    }
}

/**
 * Загрузка включённых модулей. Вызывается один раз за запрос.
 */
function mokko_modules_boot()
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    foreach (mokko_modules_scan() as $slug => $module) {
        if (empty($module['enabled']) || $module['bootstrap'] === '') {
            continue;
        }

        $base = realpath(mokko_modules_dir() . '/' . $slug);
        $file = realpath(mokko_modules_dir() . '/' . $slug . '/' . $module['bootstrap']);
        if ($base === false || $file === false) {
            if (function_exists('mokko_log')) {
                mokko_log('module ' . $slug . ': bootstrap not found');
            }
            continue;
        }

        // bootstrap.php не должен выходить за пределы каталога модуля.
        if (strpos($file, $base . DIRECTORY_SEPARATOR) !== 0 && $file !== $base) {
            if (function_exists('mokko_log')) {
                mokko_log('module ' . $slug . ': bootstrap outside module dir');
            }
            continue;
        }

        try {
            require_once $file;
        } catch (Throwable $e) {
            if (function_exists('mokko_log')) {
                mokko_log('module ' . $slug . ': ' . $e->getMessage());
            }
        }
    }

    if (function_exists('mokko_hook')) {
        mokko_hook('app.boot');
    }
}

/**
 * URL статического файла модуля (для CSS/JS/картинок).
 */
function mokko_module_asset($slug, $path)
{
    $slug = (string)$slug;
    $path = ltrim(str_replace('\\', '/', (string)$path), '/');
    if (!mokko_module_slug_ok($slug) || $path === '' || strpos($path, '..') !== false) {
        return '';
    }
    $base = defined('BASE_PATH') ? BASE_PATH : '';
    return $base . '/modules/' . $slug . '/' . $path;
}

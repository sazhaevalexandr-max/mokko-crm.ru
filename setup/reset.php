<?php
/**
 * Сброс установки MOKKO CRM (только из командной строки).
 *
 * Удаляет базу, доступы к ней, лицензию и lock-файл, чтобы установщик
 * снова открылся. Ключ установки (setup/install.key) не трогает.
 *
 * Запуск:
 *   php setup/reset.php --yes
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Not Found';
    exit;
}

$root = dirname(__DIR__);
$yes  = in_array('--yes', $argv, true);

if (!$yes) {
    fwrite(STDERR, "Сброс удалит базу данных и настройки установки.\n");
    fwrite(STDERR, "Если это действительно нужно: php setup/reset.php --yes\n");
    exit(1);
}

$configFile = $root . '/config/db_local.php';
$db = array();

if (is_file($configFile)) {
    $loaded = include $configFile;
    if (is_array($loaded)) {
        $db = $loaded;
    }
}

if (!empty($db['name']) && preg_match('/^[A-Za-z0-9_]+$/', (string)$db['name'])) {
    try {
        $host = isset($db['host']) ? (string)$db['host'] : 'localhost';
        $user = isset($db['user']) ? (string)$db['user'] : 'root';
        $pass = isset($db['pass']) ? (string)$db['pass'] : '';
        $pdo  = new PDO('mysql:host=' . $host . ';charset=utf8mb4', $user, $pass, array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ));
        $pdo->exec('DROP DATABASE IF EXISTS `' . $db['name'] . '`');
        echo 'База «' . $db['name'] . "» удалена.\n";
    } catch (Exception $e) {
        fwrite(STDERR, 'Не удалось удалить базу: ' . $e->getMessage() . "\n");
        fwrite(STDERR, "Удалите её вручную, затем повторите сброс.\n");
        exit(2);
    }
}

foreach (array('config/installed.lock', 'config/license.php', 'config/db_local.php', 'config/maintenance.flag') as $file) {
    $path = $root . '/' . $file;
    if (is_file($path)) {
        if (@unlink($path)) {
            echo 'Удалён ' . $file . ".\n";
        } else {
            fwrite(STDERR, 'Не удалось удалить ' . $file . " — проверьте права.\n");
            exit(3);
        }
    }
}

echo "\nГотово. Установщик снова доступен:\n";
echo '  /setup/install.php' . "\n";
echo "Ключ установки остался в setup/install.key.\n";

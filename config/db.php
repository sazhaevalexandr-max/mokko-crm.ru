<?php
/**
 * Подключение к MySQL.
 *
 * Приоритет источников:
 *   1. переменные окружения MOKKO_DB_* (удобно для контейнеров/CI);
 *   2. config/db.local.php — файл, который создаёт установщик;
 *   3. локальные значения по умолчанию (XAMPP: root без пароля).
 *
 * config/db.local.php содержит пароль БД и не должен попадать в git.
 */

function getDB()
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $local = array();
    $localFile = __DIR__ . '/db.local.php';
    if (is_file($localFile)) {
        $loaded = @include $localFile;
        if (is_array($loaded)) {
            $local = $loaded;
        }
    }

    $host = getenv('MOKKO_DB_HOST');
    $port = getenv('MOKKO_DB_PORT');
    $name = getenv('MOKKO_DB_NAME');
    $user = getenv('MOKKO_DB_USER');
    $pass = getenv('MOKKO_DB_PASS');

    if ($host === false || $host === '') { $host = isset($local['host']) ? (string)$local['host'] : 'localhost'; }
    if ($port === false || $port === '') { $port = isset($local['port']) ? (string)$local['port'] : ''; }
    if ($name === false || $name === '') { $name = isset($local['name']) ? (string)$local['name'] : 'mokko'; }
    if ($user === false || $user === '') { $user = isset($local['user']) ? (string)$local['user'] : 'root'; }
    if ($pass === false) { $pass = isset($local['pass']) ? (string)$local['pass'] : ''; }

    $dsn = 'mysql:host=' . $host;
    if ($port !== '') {
        $dsn .= ';port=' . (int)$port;
    }
    $dsn .= ';dbname=' . $name . ';charset=utf8mb4';

    $pdo = new PDO(
        $dsn,
        $user,
        $pass,
        array(
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        )
    );

    return $pdo;
}

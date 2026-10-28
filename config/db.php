<?php
/**
 * Подключение к MySQL.
 *
 * Приоритет учётных данных:
 *   1) config/db_local.php — файл, который создаёт установщик (setup/install.php)
 *      при установке на хостинге клиента; возвращает массив host/name/user/pass;
 *   2) переменные окружения MOKKO_DB_HOST / MOKKO_DB_NAME / MOKKO_DB_USER / MOKKO_DB_PASS;
 *   3) значения по умолчанию (localhost / mokko / root / пустой пароль).
 */

function getDB()
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    // Учётные данные, записанные установщиком, имеют наивысший приоритет.
    $local = array();
    $localFile = __DIR__ . '/db_local.php';
    if (is_file($localFile)) {
        $loaded = include $localFile;
        if (is_array($loaded)) {
            $local = $loaded;
        }
    }

    $pick = function ($key, $env) use ($local) {
        if (isset($local[$key]) && $local[$key] !== '') {
            return (string)$local[$key];
        }
        $v = getenv($env);
        return ($v === false) ? null : $v;
    };

    $host = $pick('host', 'MOKKO_DB_HOST');
    $name = $pick('name', 'MOKKO_DB_NAME');
    $user = $pick('user', 'MOKKO_DB_USER');
    // Пароль может быть намеренно пустым, поэтому отдельная обработка.
    if (isset($local['pass'])) {
        $pass = (string)$local['pass'];
    } else {
        $pass = getenv('MOKKO_DB_PASS');
    }

    if ($host === null || $host === '') { $host = 'localhost'; }
    if ($name === null || $name === '') { $name = 'mokko'; }
    if ($user === null || $user === '') { $user = 'root'; }
    if ($pass === false || $pass === null) { $pass = ''; }

    $pdo = new PDO(
        'mysql:host=' . $host . ';dbname=' . $name . ';charset=utf8mb4',
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

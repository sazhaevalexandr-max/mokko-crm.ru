<?php
/**
 * Подключение к MySQL.
 *
 * Учётные данные можно переопределить переменными окружения
 * MOKKO_DB_HOST / MOKKO_DB_NAME / MOKKO_DB_USER / MOKKO_DB_PASS,
 * чтобы не хранить пароль в коде и не использовать root в продакшене.
 */

function getDB()
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = getenv('MOKKO_DB_HOST');
    $name = getenv('MOKKO_DB_NAME');
    $user = getenv('MOKKO_DB_USER');
    $pass = getenv('MOKKO_DB_PASS');

    if ($host === false || $host === '') { $host = 'localhost'; }
    if ($name === false || $name === '') { $name = 'mokko'; }
    if ($user === false || $user === '') { $user = 'root'; }
    if ($pass === false) { $pass = ''; }

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

<?php
/**
 * Установщик MOKKO CRM.
 *
 * Защита: доступ только с локального хоста (дополнительно закрыт
 * setup/.htaccess), повторная установка при наличии пользователей
 * запрещена, пароль администратора больше не «admin123» — генерируется
 * случайным и показывается один раз.
 */

$remote = isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : '';
if (!in_array($remote, array('127.0.0.1', '::1'), true)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Forbidden';
    exit;
}

require_once dirname(__DIR__) . '/config/security.php';
mokko_harden_errors();
mokko_send_security_headers();

$host = getenv('MOKKO_DB_HOST');
$user = getenv('MOKKO_DB_USER');
$pass = getenv('MOKKO_DB_PASS');
$name = getenv('MOKKO_DB_NAME');
if ($host === false || $host === '') { $host = 'localhost'; }
if ($user === false || $user === '') { $user = 'root'; }
if ($pass === false) { $pass = ''; }
if ($name === false || $name === '') { $name = 'mokko'; }
if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
    http_response_code(500);
    exit('Некорректное имя базы данных.');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="robots" content="noindex">
<title>MOKKO CRM — Установка</title>
<style>
body { font-family: monospace; max-width: 700px; margin: 40px auto; padding: 0 20px; }
.ok   { color: green; }
.err  { color: red; }
.info { color: #666; }
pre   { background: #f4f4f4; padding: 10px; border-radius: 4px; }
</style>
</head>
<body>
<h2>MOKKO CRM — Создание базы данных</h2>
<?php

require_once dirname(__DIR__) . '/config/db.php';

// 1. Уже установлено? Ничего не меняем.
try {
    $existing = getDB();
    $count = (int)$existing->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($count > 0) {
        echo '<p class="info">ℹ База данных уже установлена (' . $count . ' пользователей). ';
        echo 'Установщик заблокирован.</p>';
        echo '<p><a href="../login">→ Перейти к входу</a></p>';
        echo '</body></html>';
        exit;
    }
} catch (Exception $e) {
    // Базы/таблиц ещё нет — продолжаем установку.
}

// 2. Подключение без указания БД
try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
    echo '<p class="ok">✓ Подключение к MySQL успешно</p>';
} catch (Exception $e) {
    mokko_log('install: connect failed: ' . $e->getMessage());
    die('<p class="err">✗ Не удалось подключиться к MySQL. Проверьте настройки подключения.</p>');
}

// 3. Создание БД
$pdo->exec('CREATE DATABASE IF NOT EXISTS `' . $name . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
echo '<p class="ok">✓ База данных <b>' . mokko_h($name) . '</b> создана / уже существует</p>';
$pdo->exec('USE `' . $name . '`');

// 4. Таблицы
$pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `email`         VARCHAR(255) UNIQUE NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `name`          VARCHAR(100) NOT NULL DEFAULT '',
    `lastname`      VARCHAR(100) NULL,
    `phone`         VARCHAR(20)  NULL,
    `wallet`        INT NOT NULL DEFAULT 0,
    `country_code`  VARCHAR(5)   NOT NULL DEFAULT 'ru',
    `role`          ENUM('admin','staff') NOT NULL DEFAULT 'admin',
    `status`        ENUM('new','active','disable','low_wallet') NOT NULL DEFAULT 'active',
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo '<p class="ok">✓ Таблица <b>users</b> создана / уже существует</p>';

$pdo->exec("CREATE TABLE IF NOT EXISTS `places` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`    INT NOT NULL,
    `name`       VARCHAR(255) NOT NULL,
    `address`    VARCHAR(255) NOT NULL DEFAULT '',
    `inn`        VARCHAR(20)  NULL,
    `kpp`        VARCHAR(20)  NULL,
    `ltd`        VARCHAR(255) NULL,
    `phone`      VARCHAR(20)  NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo '<p class="ok">✓ Таблица <b>places</b> создана / уже существует</p>';

$pdo->exec("CREATE TABLE IF NOT EXISTS `subscribers` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `phone`      VARCHAR(32)  NOT NULL,
    `email`      VARCHAR(254) NOT NULL,
    `ip`         VARCHAR(45)  NOT NULL DEFAULT '',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo '<p class="ok">✓ Таблица <b>subscribers</b> создана / уже существует</p>';

// 5. Демо-пользователь (если пользователей ещё нет)
$check = $pdo->prepare('SELECT id FROM users LIMIT 1');
$check->execute();

if (!$check->fetch()) {
    $generated = bin2hex(random_bytes(8));
    $hash = password_hash($generated, PASSWORD_DEFAULT);
    $ins  = $pdo->prepare("INSERT INTO users (email, password_hash, name, role, status) VALUES (?, ?, ?, 'admin', 'active')");
    $ins->execute(array('admin@mokko.local', $hash, 'Администратор'));
    $adminId = $pdo->lastInsertId();

    $pdo->prepare('INSERT INTO places (user_id, name) VALUES (?, ?)')->execute(array($adminId, 'Главная кофейня'));

    echo '<p class="ok">✓ Создан администратор</p>';
    echo '<pre>';
    echo "Email:    admin@mokko.local\n";
    echo 'Пароль:   ' . mokko_h($generated) . "\n";
    echo '</pre>';
    echo '<p class="err">Сохраните пароль и смените его после первого входа.</p>';
} else {
    echo '<p class="info">ℹ Администратор уже существует</p>';
}

echo '<hr>';
echo '<h3>Установка завершена!</h3>';
echo '<p><a href="../login">→ Перейти к входу</a></p>';
echo '<p class="err"><b>Удалите или закройте доступ к папке setup/ после установки!</b></p>';
?>
</body>
</html>

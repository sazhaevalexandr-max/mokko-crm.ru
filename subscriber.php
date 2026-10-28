<?php
/**
 * Подписка на предзаказ (форма на /subscribe).
 *
 * Исправлено: раньше файл подключался напрямую, использовал устаревшее
 * расширение mysql_*, хардкодил логин/пароль другой базы и вставлял
 * пользовательский ввод в SQL конкатенацией (SQL-инъекция).
 */

require_once __DIR__ . '/config/security.php';

mokko_harden_errors();
mokko_send_security_headers();

if (mokko_request_method() !== 'POST') {
    header('Allow: POST');
    mokko_json(array('error' => 'method not allowed'), 405);
}
if (!mokko_is_same_origin()) {
    mokko_json(array('error' => 'forbidden'), 403);
}

$phone = isset($_POST['phone']) ? mokko_str($_POST['phone'], 32) : '';
$email = isset($_POST['email']) ? mokko_str($_POST['email'], 254) : '';

if (!preg_match('/^[0-9+\-() ]{5,32}$/', $phone)) {
    mokko_json(array('error' => 'invalid phone'), 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    mokko_json(array('error' => 'invalid email'), 422);
}

if (!mokko_rate_limit('subscribe', 10, 3600)) {
    mokko_json(array('error' => 'limit'), 429);
}

require_once __DIR__ . '/config/db.php';

try {
    $pdo  = getDB();
    $stmt = $pdo->prepare('INSERT INTO subscribers (phone, email, ip) VALUES (?, ?, ?)');
    $stmt->execute(array(
        $phone,
        $email,
        isset($_SERVER['REMOTE_ADDR']) ? substr((string)$_SERVER['REMOTE_ADDR'], 0, 45) : '',
    ));
} catch (Exception $e) {
    mokko_log('subscribe: db error: ' . $e->getMessage());
    mokko_json(array('error' => 'server'), 500);
}

mokko_json(array('success' => true));

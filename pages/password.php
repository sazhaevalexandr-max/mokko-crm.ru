<?php
/**
 * Легаси-страница «восстановление пароля».
 *
 * Была уязвима: параметр ?mail= выводился без экранирования (reflected XSS),
 * а введённый пароль отправлялся на сторонний домен
 * http://mokko.azurewebsites.net/newpassword/... (утечка credentials).
 * Автоматический сброс отключён, страница носит информационный характер.
 */

if (!defined('MOKKO')) {
    define('MOKKO', true);
}
require_once dirname(__DIR__) . '/config/security.php';
mokko_harden_errors();
mokko_send_security_headers();

$mail = isset($_GET['mail']) ? mokko_str($_GET['mail'], 254) : '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Восстановление пароля — MOKKO CRM</title>
<link href="../css/bootstrap.css" rel="stylesheet">
<link href="../css/landing.css" rel="stylesheet">
</head>
<body>
<div class="container" style="max-width:640px;margin-top:80px;">
    <div class="panel panel-default">
        <div class="panel-heading"><h3 class="panel-title">Восстановление пароля</h3></div>
        <div class="panel-body">
            <?php if ($mail !== ''): ?>
                <p>Запрос для адреса: <b><?= mokko_h($mail) ?></b></p>
            <?php endif; ?>
            <p>Автоматический сброс пароля в этой сборке отключён по соображениям
                безопасности. Напишите в поддержку с адреса, привязанного к аккаунту:</p>
            <p><b><a href="mailto:info@mokko-crm.ru">info@mokko-crm.ru</a></b></p>
            <hr>
            <p><a class="btn btn-primary" href="../login">Вернуться к входу</a></p>
        </div>
    </div>
</div>
</body>
</html>

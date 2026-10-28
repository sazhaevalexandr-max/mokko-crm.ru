<?php
/**
 * Легаси-копия входа. Штатный маршрут — /login (index.php).
 * Приведено к тем же правилам безопасности.
 */

define('MOKKO', true);

require_once dirname(__DIR__) . '/config/security.php';
mokko_harden_errors();
mokko_session_start();
mokko_send_security_headers();

$base = rtrim(str_replace('\\', '/', dirname(dirname(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/'))), '/');
define('BASE_PATH', $base);
define('BASE_URL', (mokko_is_https() ? 'https' : 'http') . '://' . mokko_host() . $base);
unset($base);

if (mokko_is_authenticated()) {
    header('Location: ' . BASE_URL . '/panel');
    exit;
}
?>
<noindex>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Вход в CRM</title>
<link href="<?= mokko_h(BASE_PATH) ?>/css/bootstrap.css" rel="stylesheet">
<link href="<?= mokko_h(BASE_PATH) ?>/css/login.css" rel="stylesheet">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
<script src="<?= mokko_h(BASE_PATH) ?>/js/bootstrap.js"></script>
<script src="<?= mokko_h(BASE_PATH) ?>/js/jquery-ui.js"></script>
<script>var BASE_URL = <?= mokko_js(BASE_URL) ?>;</script>
<script src="<?= mokko_h(BASE_PATH) ?>/js/login.js?v=2"></script>
</head>
<body class="layer" data-depth="0.10" id="scene">
<div class="layer" data-depth="0.10">
<div class="left">
<section>
<div class="logo">
<img src="<?= mokko_h(BASE_PATH) ?>/images/cup.png">
</div>
<center><h1>MOKKO CRM</h1></center>
<center><hr></center>
<center><p class="desc_text">Простая и удобная CRM для автоматизации кофейни</p>
<button onclick="location.href='<?= mokko_h(BASE_URL) ?>/signup'" type="button" class="btn btn-primary demo_btn try">Регистрация</button>
<a class="repeir" href="#">Напомнить пароль</a>
</center>
</section>
</div>
<div class="right">
<section>
<form id="enter" class="form-signin" role="form">
<div class='report'></div>
<input type="text" class="form-control" placeholder="Email" name="user" required autofocus>
<input type="password" class="form-control" placeholder="Пароль" name="password" required>
<input class="btn btn-lg btn-primary btn-block enter" type="button" value="Вход"/>
</form>
</section>
</div>
</div>
</body>
</html>
</noindex>

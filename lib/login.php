<?php
    if(!defined('MOKKO'))die;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Вход — MOKKO CRM</title>
<meta name="description" content="MOKKO CRM — учёт для кофеен: заказы, гости, склад и статистика.">
<meta name="theme-color" content="#0f0d0b">
<link rel="icon" type="image/svg+xml" href="<?= mokko_h(BASE_PATH) ?>/images/brand/favicon.svg?v=2">
<link href="<?= mokko_h(BASE_PATH) ?>/css/bootstrap-4.6.2.min.css" rel="stylesheet">
<link href="<?= mokko_h(BASE_PATH) ?>/css/glyphicons.css" rel="stylesheet">
<link href="<?= mokko_h(BASE_PATH) ?>/css/theme.css?v=34" rel="stylesheet">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
<script src="<?= mokko_h(BASE_PATH) ?>/js/bootstrap-4.6.2.bundle.min.js"></script>
<script src="<?= mokko_h(BASE_PATH) ?>/js/jquery-ui.js"></script>
<script>var BASE_URL = <?= mokko_js(BASE_URL) ?>;</script>
<script src="<?= mokko_h(BASE_PATH) ?>/js/login.js?v=4"></script>
</head>
<body class="auth-body">
<div class="auth-shell">

    <section class="auth-brand">
        <div class="auth-brand-inner">
            <a class="brand brand--lg" href="<?= mokko_h(BASE_URL) ?>/login" aria-label="MOKKO CRM">
                <span class="brand-text"><img class="brand-word" src="<?= mokko_h(BASE_PATH) ?>/images/brand/wordmark.svg?v=1" alt="" width="240" height="50"><span class="brand-crm">CRM</span></span>
            </a>

            <h1 class="auth-headline">Учёт для кофейни,<br>который <em>не мешает</em> работать</h1>
            <p class="auth-lead">Заказы, гости, склад и выручка — в одном спокойном тёмном интерфейсе, который не утомляет глаза за стойкой.</p>

            <ul class="auth-points">
                <li>Счёт и оплата в пару касаний</li>
                <li>Меню, склад и смены без бумажных таблиц</li>
                <li>Выручка и средний чек в реальном времени</li>
            </ul>

            <div class="auth-footnote">Для кофеен и небольших ресторанов</div>
        </div>
    </section>

    <section class="auth-panel">
        <form id="enter" class="auth-card" role="form">
            <div class="auth-card-head">
                <h2 class="auth-title">Вход в систему</h2>
                <p class="auth-sub">Владелец и бариста входят здесь — каждый со своим логином</p>
            </div>

            <div class="report" role="alert"></div>

            <div class="auth-field">
                <label for="login-email">E-mail или логин</label>
                <input id="login-email" type="text" class="form-control" placeholder="you@example.com"
                       name="user" required autofocus autocomplete="username">
            </div>

            <div class="auth-field">
                <label for="login-password">Пароль</label>
                <input id="login-password" type="password" class="form-control" placeholder="••••••••"
                       name="password" required autocomplete="current-password">
            </div>

            <button class="btn btn-primary btn-block auth-submit enter" type="button">Войти</button>

            <div class="auth-links">
                <a class="repeir" href="<?= mokko_h(BASE_URL) ?>/repair">Забыли пароль?</a>
                <a href="<?= mokko_h(BASE_URL) ?>/signup">Создать аккаунт</a>
            </div>

            <div class="auth-note">После 10 неудачных попыток вход временно блокируется на 5 минут — это защита от подбора пароля.</div>
        </form>
    </section>

</div>
</body>
</html>

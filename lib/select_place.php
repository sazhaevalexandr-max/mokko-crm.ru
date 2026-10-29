<?php
    if(!defined('MOKKO'))die;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Выбор кофейни — MOKKO CRM</title>
<meta name="theme-color" content="#0f0d0b">
<link rel="icon" type="image/svg+xml" href="<?= mokko_h(BASE_PATH) ?>/images/brand/favicon.svg?v=2">
<link href="<?= mokko_h(BASE_PATH) ?>/css/bootstrap-4.6.2.min.css" rel="stylesheet">
<link href="<?= mokko_h(BASE_PATH) ?>/css/glyphicons.css" rel="stylesheet">
<link href="<?= mokko_h(BASE_PATH) ?>/css/theme.css?v=34" rel="stylesheet">
<script src="<?= mokko_h(BASE_PATH) ?>/js/jquery-1.11.1.min.js?v=1"></script>
<script src="<?= mokko_h(BASE_PATH) ?>/js/bootstrap-4.6.2.bundle.min.js"></script>
<script>
var BASE_URL = <?= mokko_js(BASE_URL) ?>;
var key = <?= mokko_js(isset($_SESSION['key']) ? (string)$_SESSION['key'] : '') ?>;
var uid = <?= mokko_js(isset($_SESSION['uid']) ? (string)$_SESSION['uid'] : '') ?>;
var sid = <?= mokko_js(isset($_SESSION['sid']) ? (string)$_SESSION['sid'] : '') ?>;
</script>
</head>
<body class="auth-body" id="places">

<div class="container">
    <div class="place-select-card">
        <a class="brand" href="<?= mokko_h(BASE_URL) ?>/login" aria-label="MOKKO CRM">
            <span class="brand-text"><img class="brand-word" src="<?= mokko_h(BASE_PATH) ?>/images/brand/wordmark-sm.svg?v=1" alt="" width="240" height="50"><span class="brand-crm">CRM</span></span>
        </a>

        <h3>Выберите кофейню</h3>
        <p>В какой точке вы начинаете смену?</p>

        <select class="form-control" name="place">
            <option disabled selected>Загрузка…</option>
        </select>

        <button type="submit" class="btn btn-primary" disabled>Продолжить</button>

        <div class="progress" style="margin-top:18px;display:none;">
            <div class="progress-bar progress-bar-striped progress-bar-animated"
                 role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:100%"></div>
        </div>

        <div class="auth-links" style="justify-content:center;margin-top:18px;">
            <a href="<?= mokko_h(BASE_URL) ?>/?do=exit">Выйти из аккаунта</a>
        </div>
    </div>
</div>

<script type="text/javascript" src="<?= mokko_h(BASE_PATH) ?>/js/select_place.js?v=2"></script>
<?php require_once __DIR__ . '/metrika.php'; ?>
</body>
</html>

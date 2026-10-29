<?php
if(!defined('MOKKO'))die;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>MOKKO CRM</title>
<link rel="icon" type="image/svg+xml" href="<?= mokko_h(BASE_PATH) ?>/images/brand/favicon.svg?v=2">
<link href="<?= mokko_h(BASE_PATH) ?>/css/bootstrap-4.6.2.min.css" rel="stylesheet">
<link href="<?= mokko_h(BASE_PATH) ?>/css/theme.css?v=34" rel="stylesheet">
</head>
<body class="auth-body auth-body--center">

<header class="auth-brandbar">
    <a class="brand" href="<?= mokko_h(BASE_URL) ?>/login" aria-label="MOKKO CRM">
        <span class="brand-text"><img class="brand-word" src="<?= mokko_h(BASE_PATH) ?>/images/brand/wordmark-sm.svg?v=1" alt="" width="240" height="50"><span class="brand-crm">CRM</span></span>
    </a>
</header>

<div class="container">
    <div class="auth-card" style="max-width:440px;text-align:center;">
        <h2 class="auth-title" style="font-size:20px;">Переадресация…</h2>
        <p class="auth-sub" style="margin-bottom:18px;">Сейчас откроем страницу входа.</p>
        <a class="btn btn-primary btn-block" href="<?= mokko_h(BASE_URL) ?>/login">Перейти к входу</a>
    </div>
</div>

<script>
window.location.href = <?= mokko_js(BASE_URL . '/login') ?>;
</script>
<?php require_once __DIR__ . '/metrika.php'; ?>
</body>
</html>

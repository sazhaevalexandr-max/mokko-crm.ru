<?php if (!defined('MOKKO')) { die; } ?>


<!--[if lt IE 7]> <html class="no-js ie6 oldie" lang="ru" > <![endif]-->
<!--[if IE 7]>    <html class="no-js ie7 oldie" lang="ru" > <![endif]-->
<!--[if IE 8]>    <html class="no-js ie8 oldie" lang="ru" > <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="ru" > <!--<![endif]-->

<head prefix="og: https://ogp.me/ns# article: https://ogp.me/ns/article#">

<meta charset="utf-8">

<meta name="description" content="сrm erp для автоматизации кофейни">
<meta name="keywords" content="site, google, internet">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<meta name="robots" content="index,follow">

<title>MOKKO CRM</title>



<link rel="icon" type="image/svg+xml" href="<?= BASE_PATH ?>/images/brand/favicon.svg?v=2">

<link href="<?= BASE_PATH ?>/css/bootstrap-4.6.2.min.css" rel="stylesheet">
<link href="<?= BASE_PATH ?>/css/glyphicons.css" rel="stylesheet">

<link rel="stylesheet" type="text/css" href="<?= BASE_PATH ?>/css/panel.css?v=6" />
<link rel="stylesheet" type="text/css" href="<?= BASE_PATH ?>/css/jquery-confirm.css" />
<link rel="stylesheet" type="text/css" href="<?= BASE_PATH ?>/css/datepicker.css" />

<link rel="stylesheet" href="<?= BASE_PATH ?>/css/chartist.css">
<link rel="stylesheet" href="<?= BASE_PATH ?>/css/chartist-plugin-tooltip.css">

<link href="<?= BASE_PATH ?>/css/theme.css?v=43" rel="stylesheet">
<?php mokko_hook('panel.head'); ?>

<script src="<?= BASE_PATH ?>/js/jquery-1.11.1.min.js?v=1"></script>
<script type="text/javascript" src="<?= BASE_PATH ?>/js/bootstrap-4.6.2.bundle.min.js"></script>
<script type="text/javascript" src="<?= BASE_PATH ?>/js/jquery.tap.js"></script>
<script type="text/javascript" src="<?= BASE_PATH ?>/js/jquery-confirm.js"></script>
<script type="text/javascript" src="<?= BASE_PATH ?>/js/bootstrap-datepicker.js"></script>
<script type="text/javascript" src="<?= BASE_PATH ?>/js/theme.js?v=14"></script>



<meta property="og:title" content="MOKKO CRM" />
<meta property="og:type" content="website" />
<meta property="og:url" content="http://crm.mokko-crm.ru/" />
<meta property="og:site_name" content="MOKKO CRM" />

<script>
var BASE_URL = <?= mokko_js(BASE_URL) ?>;
var key = <?= mokko_js(isset($_SESSION['key']) ? (string)$_SESSION['key'] : '') ?>;
var uid = <?= mokko_js(isset($_SESSION['uid']) ? (string)$_SESSION['uid'] : '') ?>;
var sid = <?= mokko_js(isset($_SESSION['sid']) ? (string)$_SESSION['sid'] : '') ?>;
</script>


</head>


<body class="layout theme-dark">

<div class="col-sm-3 col-md-2 col-12 sidebar panel-sidebar">

<a class="sidebar-brand brand" href="<?= BASE_URL ?>/panel" aria-label="MOKKO CRM">
    <span class="brand-text"><img class="brand-word" src="<?= BASE_PATH ?>/images/brand/wordmark-sm.svg?v=1" alt="" width="240" height="50"><span class="brand-crm">CRM</span></span>
</a>

<button type="button" class="panel-menu-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false" aria-controls="bs-example-navbar-collapse-1" aria-label="Открыть меню">
    <span class="burger" aria-hidden="true"><i></i><i></i></span>
</button>


<div class="your_name"><a href="#account" madule="account"><span>{Name}</span></a><small><a href="<?= BASE_URL ?>/?do=exit">Выйти</a></small></div>

<div id="bs-example-navbar-collapse-1" class="collapse">

<ul class="nav nav-sidebar navigation">
<li class="active"><a href="#home">Главная</a></li>
<li><a href="#places" module="places" >Кофейни</a></li>
<li><a href="#staff" module="staff">Сотрудники</a></li>
<li><a href="#menu" module="category">Меню</a></li>
<li><a href="#lager" module="lager">Склад</a></li>
<li><a href="#options" madule="options">Настройки</a></li>
<li><a href="#feedback">Техподдержка</a></li>
<?php mokko_hook('panel.nav'); ?>
</ul>

<style>
.nav-sidebar.nav-system{list-style:none;margin:6px 0 0;padding:8px var(--s3) 0;border-top:1px solid var(--border);}
.nav-sidebar.nav-system>li{margin:2px 0;}
.nav-sidebar.nav-system>li>a{position:relative;display:flex;align-items:center;gap:8px;padding:9px var(--s3);border-radius:var(--radius-sm);font-size:14px;font-weight:550;color:var(--text-dim);transition:background-color var(--t-fast) var(--ease),color var(--t-fast) var(--ease);}
.nav-sidebar.nav-system>li>a:hover{background:var(--hover);color:var(--text);}
.nav-sidebar.nav-system .upd-ver{margin-left:auto;font-family:var(--mono);font-size:11px;line-height:1;color:var(--text-mute);background:var(--surface-3);border:1px solid var(--border);border-radius:999px;padding:3px 7px;}
</style>
<ul class="nav nav-sidebar nav-system">
<li><a href="<?= BASE_URL ?>/updates">Обновления <span class="upd-ver">v<?= mokko_h(MOKKO_VERSION) ?></span></a></li>
</ul>

<div class="ballance">
<small>Баланс</small>
<span>0</span>
<small><a class="btn btn-default btn-xs pay_btn" href="#options" madule="options">подробнее</a></small>
</div>
</div>
</div>


<div class="col-sm-9 offset-sm-3 col-md-10 offset-md-2 main">

<div class="ember-view header_in_profile">
<div class="clearfix title-header">

<div class="pull-left title-header-title no_mobile">
<div class="ember-view ">Панель управления</div>
</div>

<div class="title-header-navigation">
<div class="bar pull-right go_crm">
<div class="ember-view nav-refresh">
<!--  <a class="ember-view bar__item navbar-secondary-item navbar-secondary-item-action demo_link no_mobile"  href="#">ДЕМО</a> --->
<!--  <a class="ember-view bar__item navbar-secondary-item navbar-secondary-item-action navbar-item-is-active" href="#">{link}</a>-->
<a class="ember-view bar__item navbar-secondary-item navbar-secondary-item-action crm_action" href="#startup">Принимать заказы<i class="glyphicon glyphicon glyphicon-cloud"></i></a>
</div>
</div>
</div>

</div>

</div>


<div class="bg-light">
<div class="row">
<div class="col-md-10 offset-md-1 c2">


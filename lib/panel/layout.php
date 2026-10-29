<?php if (!defined('MOKKO')) { die; } ?>


<!--[if lt IE 7]> <html class="no-js ie6 oldie" lang="ru" > <![endif]-->
<!--[if IE 7]>    <html class="no-js ie7 oldie" lang="ru" > <![endif]-->
<!--[if IE 8]>    <html class="no-js ie8 oldie" lang="ru" > <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="ru" > <!--<![endif]-->

<head prefix="og: https://ogp.me/ns# article: https://ogp.me/ns/article#">

<meta charset="utf-8">

<meta name="description" content="сrm erp для автоматизации кофейни">
<meta name="keywords" content="site, crm">
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

<link href="<?= BASE_PATH ?>/css/theme.css?v=59" rel="stylesheet">
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

<style>
/* Бургер панели: только на мобильных; на десктопе меню и так раскрыто */
.panel-menu-toggle{position:absolute;top:14px;right:12px;z-index:5;display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;background:transparent;border:0;border-radius:10px;cursor:pointer;color:var(--text);}
.panel-menu-toggle:hover{background:var(--hover);}
@media (min-width:768px){ .panel-menu-toggle{display:none;} }

/* CTA «Принимать заказы» — заметная кнопка на десктопе и мобильном */
.nav-refresh .crm_action.bar__item{
    display:inline-flex!important;align-items:center;justify-content:center;gap:9px;
    padding:11px 22px!important;border-radius:999px!important;
    font-weight:700!important;font-size:14px!important;letter-spacing:.2px;line-height:1;
    box-shadow:0 4px 16px rgba(var(--accent-rgb),.38);
    transition:transform var(--t-fast,.15s) var(--ease,ease),box-shadow var(--t-fast,.15s) var(--ease,ease),background var(--t-fast,.15s) var(--ease,ease);
    cursor:pointer;animation:crmPulse 2.6s ease-in-out infinite;
}
.nav-refresh .crm_action.bar__item:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(var(--accent-rgb),.5);}
.nav-refresh .crm_action.bar__item:active{transform:translateY(0);box-shadow:0 3px 12px rgba(var(--accent-rgb),.4);}
.nav-refresh .crm_action.bar__item .crm-ico{display:block!important;width:18px;height:18px;flex:0 0 auto;margin:0;}
@keyframes crmPulse{0%,100%{box-shadow:0 4px 16px rgba(var(--accent-rgb),.32)}50%{box-shadow:0 4px 22px rgba(var(--accent-rgb),.6)}}
@media (prefers-reduced-motion:reduce){ .nav-refresh .crm_action.bar__item{animation:none;} }
@media (max-width:767px){
    .title-header-navigation{text-align:center;}
    .bar.pull-right.go_crm{float:none;width:100%;padding:0;text-align:center;}
    .nav-refresh .crm_action.bar__item{width:auto!important;margin:0 auto!important;}
}
</style>

<div class="col-sm-3 col-md-2 col-12 sidebar panel-sidebar">

<a class="sidebar-brand brand" href="<?= BASE_URL ?>/panel" aria-label="MOKKO CRM">
    <span class="brand-text"><img class="brand-word" src="<?= BASE_PATH ?>/images/brand/wordmark-sm.svg?v=1" alt="" width="240" height="50"><span class="brand-crm">CRM</span></span>
</a>

<button type="button" class="panel-menu-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false" aria-controls="bs-example-navbar-collapse-1" aria-label="Открыть меню">
    <span class="burger" aria-hidden="true"><i></i><i></i></span>
</button>

<div id="bs-example-navbar-collapse-1" class="collapse">

<div class="your_name"><a href="#account" madule="account"><span>{Name}</span></a><small><a href="<?= BASE_URL ?>/?do=exit">Выйти</a></small></div>

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

<div class="ballance ballance--support">
<div class="ballance-support-head">
<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.8 8.6c0 5.2-8.8 10.4-8.8 10.4S3.2 13.8 3.2 8.6A4.6 4.6 0 0 1 12 7a4.6 4.6 0 0 1 8.8 1.6z"/></svg>
<b>Поддержать проект</b>
</div>
<p class="ballance-support-text">MOKKO CRM — некоммерческий проект. Донаты помогают окупать разработку в свободное время и выпускать новые модули и обновления.</p>
<a class="btn btn-primary btn-xs ballance-support-btn" href="https://boosty.to/alexandr220/donate" target="_blank" rel="noopener noreferrer">Поддержать на Boosty</a>
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
<a class="ember-view bar__item navbar-secondary-item navbar-secondary-item-action crm_action" href="#startup"><svg class="crm-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 5v14l11-7z" fill="currentColor" stroke="none"/></svg>Принимать заказы</a>
</div>
</div>
</div>

</div>

</div>


<div class="bg-light">
<div class="row">
<div class="col-md-10 offset-md-1 c2">


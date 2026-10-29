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

<link rel="stylesheet" type="text/css" href="<?= BASE_PATH ?>/css/panel.css?v=4" />
<link rel="stylesheet" type="text/css" href="<?= BASE_PATH ?>/css/crm.css?v=5" />
<link rel="stylesheet" type="text/css" href="<?= BASE_PATH ?>/css/jquery-confirm.css" />

<script src="<?= BASE_PATH ?>/js/jquery-1.11.1.min.js?v=1"></script>
<script type="text/javascript" src="<?= BASE_PATH ?>/js/bootstrap-4.6.2.bundle.min.js"></script>
<script type="text/javascript" src="<?= BASE_PATH ?>/js/jquery-confirm.js"></script>
<script src="<?= BASE_PATH ?>/js/jquery-ui.js"></script>
<script type="text/javascript" src="<?= BASE_PATH ?>/js/theme.js?v=14"></script>


<link rel="stylesheet" href="<?= BASE_PATH ?>/css/offline-theme-slide.css" />
<link rel="stylesheet" href="<?= BASE_PATH ?>/css/offline-language-english.css" />

<link href="<?= BASE_PATH ?>/css/theme.css?v=62" rel="stylesheet">

<style type="text/css">
.jconfirm.jconfirm-white .jconfirm-bg {
    background-color: rgba(5, 7, 10, .72);
}
/* Касса: контент занимает всю ширину после сайдбара (в панели остаётся 1120px) */
body.crm-page .bg-light > .row > .c2 { max-width: none !important; }
</style>

<meta property="og:title" content="MOKKO CRM" />
<meta property="og:type" content="website" />
<meta property="og:image" content="http://crm.mokko-crm.ru/framework/frontend/common/images/ls-logo.png" />
<meta property="og:url" content="http://crm.mokko-crm.ru/" />
<meta property="og:site_name" content="MOKKO CRM" />

<script>
var BASE_URL = <?= mokko_js(BASE_URL) ?>;
var key = <?= mokko_js(isset($_SESSION['key']) ? (string)$_SESSION['key'] : '') ?>;
var uid = <?= mokko_js(isset($_SESSION['uid']) ? (string)$_SESSION['uid'] : '') ?>;
var sid = <?= mokko_js(isset($_SESSION['sid']) ? (string)$_SESSION['sid'] : '') ?>;
var place = <?= mokko_js(isset($_SESSION['place']) ? (string)$_SESSION['place'] : '') ?>;
</script>


</head>


<body class="layout theme-dark crm-page">


<nav class="navbar navbar-default navbar-fixed-top fixed-top usertop" role="navigation">

    <ul class="nav navbar-nav">
        <li class="nav-item brand-nav">
            <a class="brand" href="<?= BASE_URL ?>/<?= $access == "admin" ? "panel" : "crm" ?>" aria-label="MOKKO CRM">
                <span class="brand-text"><img class="brand-word" src="<?= BASE_PATH ?>/images/brand/wordmark-sm.svg?v=1" alt="" width="240" height="50"><span class="brand-crm">CRM</span></span>
            </a>
        </li>
        <li class="navbar-brand time">
            <!--<b>00:00:00</b>-->
            <small>на рабочем месте</small>
            <div class="time_online"></div>
        </li>
    </ul>
       <ul class="nav navbar-nav pull-right ">
           
    <!--    <li class=""><a class="fullscreen_btn"><i class="glyphicon glyphicon-fullscreen"></i></a></li>--->
        
<li class="navbar-brand line_right no_mobile head-meta"><span class="place no_mobile"><svg class="mi" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg><em></em></span><span class='stuff'><?php print($type_ac); ?></span></li>
        <li class="dropdown user-dropdown"><a href="#" class="dropdown-toggle crm_menues menu-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false" aria-label="Меню"><span class="burger" aria-hidden="true"><i></i><i></i></span></a>
            <ul class="dropdown-menu usermenu" role="menu">
                <li class="avara"><span class="avatar_letter" aria-hidden="true"></span><div class="avara_text"><div class="name_avatar"></div><small><?php print($type_ac); ?></small></div><a href="#" class="pet-face-btn avara-pet" aria-label="Котик"><span class="pet-ico"><img src="<?= BASE_PATH ?>/images/pet-face.png?v=1" alt="" width="22" height="22"><span class="pet-dot" aria-hidden="true"></span></span></a></li>

<?php if($access == "admin"){ ?> <li><a href="<?= BASE_URL ?>/panel"><svg class="mi" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>Панель управления</a></li> <?php }else{ ?>
<li><a href="#" class="new_pass_s"><svg class="mi" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="8" cy="15" r="4"/><path d="M10.8 12.2 20 3m-3 3 3 3m-6-0 2 2"/></svg>Сменить пароль</a></li>
<?php } ?>

<li class="for_mobile"><a href="#dashboard"><svg class="mi" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 4h6M8 4H6a1 1 0 0 0-1 1v15a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V5a1 1 0 0 0-1-1h-2"/><path d="M9 11h6M9 15h4"/></svg>Заказ</a></li>
<li class="for_mobile"><a href="#guests" class="load_guests"><svg class="mi" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>Гости</a></li>
                <li class="divider"></li>
                <li class='im_out'><a href="#"><svg class="mi" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4.5" y="10.5" width="15" height="10" rx="2"/><path d="M8 10.5V7.5a4 4 0 0 1 8 0v3"/></svg>Отойти<small>заблокировать экран</small></a></li>
                <li class='shift_end'><a href="#"><svg class="mi" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v8"/><path d="M6.3 6.8a8 8 0 1 0 11.4 0"/></svg>Закрыть смену</a></li>
            </ul>
        </li>
    </ul>

</nav>

<div class="left_bar col-sm-3 col-md-2 col-12 sidebar">
    
    
    <ul class="menu_left">
        <li class="waves-effect waves-light"><a class="active" href="#dashboard" ><i class="glyphicon glyphicon-edit"></i> Заказ</a></li>
        <li  class="waves-effect waves-light"><a class="load_guests" href="#guests" module="guests"><i class="glyphicon glyphicon-time"></i> Гости</a></li>
        <!--<li class="waves-effect waves-light"><a href="#lager" module="lager"><i class="glyphicon glyphicon-list-alt"></i> Склад</a></li>-->
    </ul>
    
    
    <div class="total_summ">
        <button type="button" class="bill-pin" aria-label="Открепить чек">
            <span class="bill-pin-label">Открепить</span>
        </button>
        <button type="button" class="bill-toggle" aria-expanded="true" aria-controls="bill-items">
            <svg class="bill-chevron" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
            <span class="bill-toggle-label">Свернуть чек</span>
        </button>
        <span class="sum">0</span><small>рублей</small>
        
        <ul class="zakaz" id="bill-items">
            
        </ul>
        <center><button type="button" class="btn btn-default oplata_btn">Оплата</button></center>
    </div>
    
    
</div>




<div class="col-sm-9 offset-sm-3 col-md-10 offset-md-2 main">


<div class="bg-light">
<div class="row">
<div class="col-md-12 c2" style="    padding-top: 30px;">

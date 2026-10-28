<?php
    if(!defined('MOKKO'))die;
?>


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

<link rel="stylesheet" type="text/css" href="<?= BASE_PATH ?>/css/panel.css?v=4" />
<link rel="stylesheet" type="text/css" href="<?= BASE_PATH ?>/css/jquery-confirm.css" />
<link rel="stylesheet" type="text/css" href="<?= BASE_PATH ?>/css/datepicker.css" />

<link rel="stylesheet" href="<?= BASE_PATH ?>/css/chartist.css">
<link rel="stylesheet" href="<?= BASE_PATH ?>/css/chartist-plugin-tooltip.css">

<link href="<?= BASE_PATH ?>/css/theme.css?v=34" rel="stylesheet">

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
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

<div class="col-sm-3 col-md-2 col-12 sidebar">

<a class="sidebar-brand brand" href="<?= BASE_URL ?>/panel" aria-label="MOKKO CRM">
    <span class="brand-text"><img class="brand-word" src="<?= BASE_PATH ?>/images/brand/wordmark-sm.svg?v=1" alt="" width="240" height="50"><span class="brand-crm">CRM</span></span>
</a>

<img class="navbar-toggle collapsed pull_right ham"  data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false" src="<?= BASE_PATH ?>/images/ham.png?ver=0.2" >


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

<div class="layer active" id="home">
<!---HOME-->

<center id="load_mainbar"><img src="<?= BASE_PATH ?>/images/30.gif"></center>


<div class="alert top_alert" role="alert">
</div>

<div class="grid-row startup" id="startup">
<div class="grid-col l-pulse-grid-1-1">
<div id="ember4412" class="widget--static ember-view widget square_no">    <div class="widget--heading">
<h3>С чего начать?</h3>
</div>


<div class="widget--body">
<div class="widget-inner">
<div class="table-widget l-checklist-table navigation">


<div class="l-checklist-row">
<div class="l-checklist-cell l-checklist-icon">
<img src="<?= BASE_PATH ?>/images/mokko_icons/house.png" style="width: 50px;border-radius: 100px;border: 1px solid #439fe0;margin: auto;">

</div>
<div class="l-checklist-cell false false">
<h4>
<a href="#places" class="ember-view">
Добавьте кофейню
</a>
</h4>

<p class="l-checklist-hint l-checklist-hint-text hide-when-mobile">
Создайте новую кофейню, расскажите  о ней, чтобы  оптимально настроить CRM.
</p>
</div>
</div>

<div class="l-checklist-row">
<div class="l-checklist-cell l-checklist-icon">
<img src="<?= BASE_PATH ?>/images/mokko_icons/cup.png" style="width: 50px;border-radius: 100px;border: 1px solid #439fe0;margin: auto;">
</div>
<div class="l-checklist-cell false false">
<h4>
<a href="#menu" class="ember-view">
Заполните меню
</a>
</h4>

<p class="l-checklist-hint l-checklist-hint-text hide-when-mobile">
Добавьте категории и сформируйте своё меню.
</p>
</div>
</div>

<div class="l-checklist-row">
<div class="l-checklist-cell l-checklist-icon">
<img src="<?= BASE_PATH ?>/images/mokko_icons/tap.png" style="width: 50px;border-radius: 100px;border: 1px solid #439fe0;margin: auto;">
</div>
<div class="l-checklist-cell false false">
<h4>
<a href="#home" class="ember-view crm_action">
Начните работать в CRM
</a>
</h4>

<p class="l-checklist-hint l-checklist-hint-text hide-when-mobile">
Как только вы выполните минимальные настройки, вы сможете начать работу прямо сейчас.
</p>
</div>
</div>

</div>
</div>

</div>
</div>
</div>
</div>
<!---END HOME-->




<div id="statistic">
    
<div class="row" style="padding-bottom:0px">
<div class="col-md-5">
        <span>Показать статистику на период с</span>
        <form class="form-inline">
            <div class="form-group jalendar-input col-md-6">
                <input type="text" class="form-control start_date datepicker" placeholder="Начало периода">
                    <div id="start_day" class="jalendar"></div>
                    </div>
            
            <div class="form-group jalendar-input col-md-6">
                <input type="text" class="form-control stop_date datepicker" placeholder="Конец периода">
                    <div id="stop_day" class="jalendar"></div>
                    </div>
            </form>
    </div>
<div class="col-md-4">
    <div class="col-md-12 col-12 places_lager">
        <select class="form-control" name="place" style="margin-top: 20px;">
            <option disabled selected>Загрузка...<option>
            <option>
        </select>
    </div>
</div>

    <div class="col-md-3 col-12">
        <div style="margin-top: 15px;">
        <button type="submit" class="btn btn-primary col-md-12 col-12  show_stat">Показать</button>
        </div>
    </div>
    
    <div class="col-md-12" style="float: left;">
        <div>
<button type="submit" class="btn btn-link today">сегодня</button>
            <button type="submit" class="btn btn-link yesterday">вчера</button>
            <button type="submit" class="btn btn-link week">неделя</button>
            <button type="submit" class="btn btn-link month">месяц</button>
        </div>
    </div>
</div>

<div class="col-md-12">
    <hr/>
    <div class="progress" style="height: 20px;" id="progress_stat">
        <div class="progress-bar" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 0%;">

        </div>
        
    </div>
    <br/>
    </div>


<div id="charts_and_tables">
<div class="col-md-6">
<div class="col-md-6">
    <div class="panel panel-default">
        <div class="panel-heading">Выручка</div>
        <h1 class="proceeds" style="padding-top:30px;padding-bottom:30px;margin: 0px;font-size:27px;"><center>0 р.</center></h1>
    </div>
</div>

<div class="col-md-6">
    <div class="panel panel-default">
        <div class="panel-heading">Средний чек</div>
        <h1 class="bg-primary a_b" style="padding-top:25px;padding-bottom:25px;margin: 0px;font-size:27px;"><center>0 р.</center></h1>
    </div>
</div>

<hr/>
</div>

<div class="col-md-6">
    <div class="panel panel-default" id="sales">
        <div class="panel-heading">Продано</div>
        <div class="panel-body">
    <table class="table table_stat"> <thead> <tr>  <th>Наименование</th> <th>Количество</th> <th>На сумму</th> </tr> </thead>
        <tbody>

</tbody>
</table>
    <span class="pull-right total_price">Итого: <b>0</b> руб.</span>
        </div>
    </div>
</div>

<div class="col-md-12">
    <div class="panel panel-default" id="staff_stat">
        <div class="panel-heading">Выработка</div>
        <div class="panel-body">
            <table class="table table_stat"> <thead> <tr>  <th>Сотрудник</th> <th>Отработано</th> <th>Выработка</th></tr> </thead>
                <tbody>
                </tbody>
            </table>
            
        </div>
    </div>
</div>

<div class="col-md-12">
    <div class="panel panel-default">
        <div class="panel-heading chart-head">Выручка по дням
            <span class="chart-keys"><span><i class="k-sum"></i>Выручка</span><span><i class="k-avg"></i>Средний чек</span></span>
        </div>
        <div class="panel-body">
            <div class="mk-chart" id="staff_chart"></div>
        </div>
    </div>
</div>

<div class="col-md-12">
    <div class="panel panel-default">
        <div class="panel-heading">Доля продаж по позициям</div>
        <div class="panel-body">
            <div class="mk-chart" id="piechart"></div>
        </div>
    </div>
</div>
</div>


<div class="row plan-limits-row">
    <section class="plan-limits">
        <h3>Лимиты тарифа</h3>
        <ul>
            <li><b>2</b><span>кофейни</span></li>
            <li><b>5</b><span>категорий меню</span></li>
            <li><b>40</b><span>позиций в меню</span></li>
            <li><b>4</b><span>аккаунта сотрудников</span></li>
        </ul>
    </section>
</div>


</div>

<!---
<div class="col-md-12">
    <div class="panel panel-default" style="padding-bottom:30px;">
        <div class="panel-heading">Динамика по дням</div>
        <div class="panel-body">
<div class="ct-chart4 col-md-12 chrt" style="height:400px;"></div>
        </div>
    </div>
</div>


<div class="col-md-12">
    <div class="panel panel-default" style="padding-bottom:30px;">
        <div class="panel-heading">Рабы</div>
        <div class="panel-body">
<div class="ct-chart3 col-md-12 chrt" style="height:300px;"></div>
        </div>
    </div>
</div>
-->


</div>

<div class="layer" id="places">

<!--places-->
<div class="section-head">
    <h2>Кофейни</h2>
    <p>Касса, гости и смены ведутся отдельно для каждой точки. Меню и склад общие.</p>
</div>
<div class="col-md-8">



</div>
<div class="col-md-4">
<button type="button" class="place-add create_place"><span class="place-add-plus" aria-hidden="true">+</span><span class="place-add-title">Добавить кофейню</span><small>Название и адрес — остальное можно позже</small></button>
</div>



</div>

<div class="layer" id="staff">
<div class="section-head section-head--row">
    <div>
        <h2>Сотрудники <span class="staff-count">0 из 4</span></h2>
        <p>Бариста входит на обычной странице входа со своим логином и паролем и сразу попадает в кассу — без доступа к панели. Ставка нужна для расчёта выработки.</p>
    </div>
</div>
<div class="staff-grid">
    <button type="button" class="place-add staff-add add_staff"><span class="place-add-plus" aria-hidden="true">+</span><span class="place-add-title">Добавить сотрудника</span><small>Логин и пароль для входа в кассу</small></button>
</div>
<p class="staff-empty" style="display:none;">Пока никого нет. Создайте аккаунт — сотрудник войдёт в кассу под своим логином, а его смены попадут в статистику.</p>
<div class="progress"><div class="progress-bar progress-bar-striped progress-bar-items active" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="display: none; width: 100%;"><span class="sr-only"></span></div></div>
</div>

<div class="layer" id="options">

<div class="section-head">
    <h2>Настройки</h2>
    <p>Профиль владельца аккаунта, вход и баланс.</p>
</div>

<div class="settings-grid">

    <section class="settings-card">
        <header class="settings-card-head"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 20.5c1.6-3.6 4.6-5.5 8-5.5s6.4 1.9 8 5.5"/></svg><h3>Профиль</h3></header>
        <div class="set-row set-row--profile">
            <span class="set-avatar" aria-hidden="true"></span>
            <div class="set-main">
                <span class="set-label">Имя</span>
                <b class="set-value set_name name_value">—</b>
            </div>
            <button type="button" class="btn btn-default btn-sm name_option">Изменить</button>
        </div>
        <div class="set-row">
            <div class="set-main">
                <span class="set-label">E-mail для входа</span>
                <span class="set-value set_login">—</span>
            </div>
            <span class="set-note">Меняется через поддержку</span>
        </div>
        <div class="set-row">
            <div class="set-main">
                <span class="set-label">Телефон</span>
                <span class="set-value set_phone is-empty">Не указан</span>
            </div>
            <button type="button" class="btn btn-default btn-sm phone_option">Добавить</button>
        </div>
    </section>

    <section class="settings-card">
        <header class="settings-card-head"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l7.5 3v5.5c0 4.6-3.1 8.4-7.5 9.5-4.4-1.1-7.5-4.9-7.5-9.5V6L12 3z"/><path d="M9 12l2.2 2.2L15.5 10"/></svg><h3>Безопасность</h3></header>
        <div class="set-row">
            <div class="set-main">
                <span class="set-label">Пароль</span>
                <span class="set-value set-dots" aria-label="Пароль скрыт">••••••••••</span>
            </div>
            <button type="button" class="btn btn-default btn-sm password_option">Сменить пароль</button>
        </div>
        <p class="set-hint">После 10 неудачных попыток входа вход блокируется на 5 минут — это защищает аккаунт от подбора пароля.</p>
    </section>

    <section class="settings-card">
        <header class="settings-card-head"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="6" width="18" height="13" rx="2.5"/><path d="M3 10h18"/><path d="M16.5 14.5h1.5"/><path d="M6 6l9.5-2.7a1.5 1.5 0 0 1 1.9 1.4V6"/></svg><h3>Баланс</h3></header>
        <div class="set-balance">
            <span class="set-balance-sum"><b class="wallet_value">0</b> ₽</span>
            <span class="set-status" data-status="">Статус загружается</span>
        </div>
        <p class="set-hint">Онлайн-пополнение временно недоступно. Чтобы пополнить баланс, напишите в поддержку — укажите e-mail аккаунта.</p>
    </section>

    <section class="settings-card settings-card--wide">
        <header class="settings-card-head"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 10.5 12 4l8 6.5V20H4z"/><path d="M9.5 20v-5.5h5V20"/></svg><h3>Кофейни и реквизиты</h3></header>
        <p class="set-hint set-hint--top">Название, адрес и данные юрлица печатаются в чеке. ИНН и КПП можно оставить пустыми, если чеки не печатаете.</p>
        <div class="settings-places"><p class="set-empty">Загрузка…</p></div>
    </section>

</div>

</div>

<div class="layer" id="lager">


    <div class="row">
        <div class="col-md-4 col-12">
            <div style="margin-top: 15px;">
            <input placeholder="Поиск по списку" id="search_lager" type="text" class="validate form-control col-md-12 col-12">
                </div>
        </div>
        <div class="col-md-4 col-12 places_lager">
            <div style="margin-top: 15px;">
            <select class="form-control" name="place">
                <option disabled selected>Загрузка...<option>
                <option>
            </select>
            </div>
        </div>
        <div class="col-md-4 col-12">
            <div style="margin-top: 15px;">
            <button style="float: none;" class="btn btn-large btn-primary new_lager_item col-md-12 col-12">Добавить наименование</button>
            </div>
        </div>
    </div>
    
    <div style="float: left;width: 100%;overflow-x: scroll;">

    <table class="table table-striped lager_table col-md-12 col-12">
        
        <thead>
            <tr class="enable">
                <th><!--№--></th>
                <th>Наименование</th>
                <th>На складе</th>
                <th>Ед.</th>
                <th style="min-width: 120px;">Действия</th>
                <th style="min-width: 100px;">&nbsp;</th>
                <th>&nbsp;</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
</div>


<div class="layer" id="menu">


<div>


<ul class="nav nav-tabs special_tabs category_tabs" role="tablist">
<li role="presentation" class="active"><a href="#category" aria-controls="category" role="tab" data-toggle="tab"><i class="glyphicon glyphicon-plus"></i>Категория</a></li>
</ul>


<div class="tab-content">

<!--category--
<div role="tabpanel" class="tab-pane active" id="category">

<div class="empty_contend"><img class="icons_at_tabs" src="<?= BASE_PATH ?>/images/mokko_icons/folder.png"><br/>
<h4>Для начала формирования меню, создайте категории, <br/>например <b>"Напитки"</b></h4>
<button type="submit" style="float: none;" class="btn btn-primary col-md-4 col-8 create_category" href="#">Создать категорию</button>
</div>
</div>
-->


<div role="tabpanel" class="tab-pane" id="category1">

<div class="editor_item row">

<div class="col-8 col-md-3">
<input type="text" class="form-control" placeholder="Название" value="Напитки"/>
</div>

<div class="col-4 col-md-6">
<input id="package1" class="package-toggle toggle-round-flat" type="checkbox" name="lager" data-price="1000" checked>
<label for="package1"></label>
<span class="switch-label">Показывать в кассе</span>
</div>


<div class="col-12 col-md-3">
<button type="submit" style="float: none;" class="pull-right btn btn-primary col-12 no_mobile disabled" href="#">Сохранить</button>
</div>
</div>
<!---items-->

<!---if empty ---
<div class="empty_contend"><img class="icons_at_tabs" src="<?= BASE_PATH ?>/images/mokko_icons/items.png"><br/>
<h4>Заполните ваше меню, добавив<br/>например <b>"Кофе Эспрессо"</b></h4>
<button type="submit" style="float: none;" class="btn btn-primary col-md-4 col-8 create_item" href="#">Добавить пункт</button>
</div>

--->
<button type="submit" style="float: none;margin-top: 10px;margin-bottom: 10px;" class="create_item btn btn-primary col-md-4 col-12 offset-md-4" href="#">Добавить наименование</button>

<ul class="list-group">
<!--
<li class="list-group-item"><b>Кофе Капучино</b><span>0</span> р. <i class="items_edit pull-right glyphicon glyphicon-remove remove_item_cull"></i><i class="pull-right glyphicon glyphicon-pencil"></i></li>
<li class="list-group-item"><b>Кофе Эспрессо</b><span>0</span> р. <i class="items_edit pull-right glyphicon glyphicon-remove remove_item_cull"></i><i class="pull-right glyphicon glyphicon-pencil"></i></li>
<li class="list-group-item"><b>Кофе Латте</b><span>0</span> р. <i class="items_edit pull-right glyphicon glyphicon-remove remove_item_cull"></i><i class="pull-right glyphicon glyphicon-pencil"></i></li>
-->
</ul>

</div>
</div>

</div>



</div>

<div class="layer" id="feedback">
<center><h3>Мы тут еще не все настроили, но техподдержка скоро будет доступна! :)</h3><img src="https://d30y9cdsu7xlg0.cloudfront.net/png/116901-200.png" style="opacity:0.5;"><center>
</div>





</div>
</div>





</div>



<small class="app-foot">MOKKO CRM, 2026</small>
<script>
$(document).ready(function() {
                  
                  
                  "use strict";
                  
                  $('.next_step_for_buy').click(function(){
                                              var cart = $('#cart').serialize();
                                              window.location.href = "http://crm.mokko-crm.ru/payment/base/?"+cart;
                                              });
                  
                  $('.package-toggle').each(function () {
                                            $(this).change(function() {
                                                           var curr_class = '.' + $(this).attr('id');
                                                           var price = $(this).attr('data-price');
                                                           var price_box = $('.pricing-table li.price span');
                                                           
                                                           $(curr_class).toggleClass('active');
                                                           
                                                           if (price) {
                                                           if ($(curr_class).hasClass('active')) {
                                                           price_box.html(parseInt(price_box.html(), 10) + parseInt(price, 10));
                                                           }
                                                           else {
                                                           price_box.html(parseInt(price_box.html(), 10) - parseInt(price, 10));
                                                           }
                                                           }
                                                           });
                                            });
                  });
</script>


<hr>
</div>
<div class="darkness"></div>
<div class="edit_this_item">
<a href="#!" class="close_white modal-action modal-close waves-effect waves-green"><span>Отмена</span> <img src="<?= BASE_PATH ?>/images/close_white.png"></a>

<div class="progress"><div class="progress-bar progress-bar-striped progress-bar-items active" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="display: block; width: 100%;"><span class="sr-only"></span></div></div>

<div class="container" style="max-width: 980px;" item_id="14">
<div class="row item_edit_open">

<div class="col-md-3 col-12">
<label class="active">Наименование</label>
<input class="form-control" placeholder="Название" type="text" name="item_name" value="Эспрессо">
</div>

<div class="col-md-2 col-4 "><label>Стоимость</label>
<input class="only_numbers form-control" placeholder="0" value="60" name="item_price" type="text"></div>
<div class="col-md-2 col-4"><label>Обьем</label>
<input class="only_numbers form-control" placeholder="0" value="100" name="item_mass" type="text"></div>
<div class="col-md-2 col-4">
<label>Ед.</label>
<div class="input-field">
<select class="browser-default form-control" name="item_em"><option value="1">Мл.</option><option value="2">Гр.</option><option value="3">Шт.</option></select>
</div>
</div>

<input value="" name="item_id" type="hidden"  />
<input value="" name="category" type="hidden"/>

<div class="col-12 col-md-3">
<label>&nbsp;</label>
<button type="submit" style="float: none;" class="pull-right save_item btn btn-primary col-12" href="#">Сохранить</button>
</div>

</div>
<hr/>

<!--<center>Для добавления ингредиентов, активируйте модуль <a href="#">склад</a></center>-->

<div class="row ingredients_place">
   
</div>

</div>

</div>



<div id="pageloader">
    <h3 style="font-size: 60px;font-weight: 800;text-align: center;color: #1f9eda;">MOKKO CRM<br><small>загрузка...</small></h3>
    <div class="loader-item fa fa-spin colored-border"></div>
</div>
<div id="microloader">
    <div class="loader-item fa fa-spin colored-border"></div>
</div>

<div id="miniloader">
    <div class="loader-item fa fa-spin colored-border"></div>
</div>
<script src="<?= BASE_PATH ?>/js/moment.js"></script>
<script src="<?= BASE_PATH ?>/js/date.format.js"></script>

<!-- Google Charts больше не нужен: графики рисует js/charts.js -->
<!-- <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script> -->
<script type="text/javascript" src="<?= BASE_PATH ?>/js/charts.js?v=1"></script>


<script type="text/javascript" src="<?= BASE_PATH ?>/js/panel.js?ver=16"></script>






<? require_once('metrika.php'); ?>

</body>
</html>


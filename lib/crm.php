<?php
    if(!defined('MOKKO'))die;
    
    if($_SESSION['sid'] == "uid"){
    $access = "admin";
    $type_ac = "Администратор";
    }else{
    $access = "barista";
    $type_ac = "Бариста";
    }
    
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
<link rel="stylesheet" type="text/css" href="<?= BASE_PATH ?>/css/crm.css?v=5" />
<link rel="stylesheet" type="text/css" href="<?= BASE_PATH ?>/css/jquery-confirm.css" />

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
<script type="text/javascript" src="<?= BASE_PATH ?>/js/bootstrap-4.6.2.bundle.min.js"></script>
<script type="text/javascript" src="<?= BASE_PATH ?>/js/jquery-confirm.js"></script>
<script src="<?= BASE_PATH ?>/js/jquery-ui.js"></script>
<script type="text/javascript" src="<?= BASE_PATH ?>/js/theme.js?v=14"></script>


<link rel="stylesheet" href="<?= BASE_PATH ?>/css/offline-theme-slide.css" />
<link rel="stylesheet" href="<?= BASE_PATH ?>/css/offline-language-english.css" />

<link href="<?= BASE_PATH ?>/css/theme.css?v=34" rel="stylesheet">

<style type="text/css">
.jconfirm.jconfirm-white .jconfirm-bg {
    background-color: rgba(5, 7, 10, .72);
}
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

<!--
<img class="navbar-toggle collapsed pull_right ham"  data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false" src="../images/ham.png?ver=0.2" />-->

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
                <li class="avara"><span class="avatar_letter" aria-hidden="true"></span><div class="avara_text"><div class="name_avatar"></div><small><?php print($type_ac); ?></small></div></li>

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
        <div class="maximize"></div>
        <div class="short"></div>
        <span class="sum">0</span><small>рублей</small>
        
        <ul class="zakaz">
            
        </ul>
        <center><button type="button" class="btn btn-default oplata_btn">Оплата</button></center>
    </div>
    
    
</div>




<div class="col-sm-9 offset-sm-3 col-md-10 offset-md-2 main">


<div class="bg-light">
<div class="row">
<div class="col-md-12 c2" style="    padding-top: 30px;">

<div class="layer active" id="dashboard">
    <div class="col-md-12 bill-bar">
        <!-- Текущий счёт: название + переименование на месте -->
        <div class="bill-current edit_g guest is-empty" aria-live="polite">
            <span class="bill-label">Текущий счёт</span>
            <div class="bill-name-row">
                <span class="bill-dot" aria-hidden="true"></span>
                <a class="name_guest" phone="" href="#" tabindex="-1">Не открыт</a>
                <a class="edit_guest" href="#" aria-label="Переименовать счёт" title="Переименовать"><svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4L19 9a2.8 2.8 0 0 0-4-4L4 16v4z"/><path d="M13.5 6.5l4 4"/></svg></a>
                <div class="input_guest"><form id="edit_g" onsubmit="return false;">
                    <input type="text" class="new_name form-control" placeholder="Название счёта" name="name_guest" maxlength="40" autocomplete="off" aria-label="Новое название счёта">
                    <!---<input type='text' maxlength='11' name='phone_guest' class='new_phone' placeholder='+7' />--->
                </form></div>
                <button style="display:none;" type="button" class="btn btn-primary btn-sm save_guest">Сохранить</button>
            </div>
        </div>

        <!-- Новый счёт: поле и кнопка одной группой -->
        <form id="guest_form" class="bill-new" role="form" onsubmit="return false;">
            <input type="hidden" name="type_guest" value="new">
            <input type="hidden" name="id_guest">
            <div class="bill-new-group">
                <div class="ui-widget">
                    <input type="text" class="form-control" id="guest" name="guest" placeholder="Например, Стол 4" maxlength="40" autocomplete="off" aria-label="Название нового счёта">
                </div>
                <button class="btn btn-primary add_guest" type="button"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg><span>Открыть счёт</span></button>
            </div>
            <small class="bill-hint">Без названия счёт получит номер <b class="bill-next">—</b></small>
        </form>
    </div>
    <div class="col-md-12">
        <div class="btn-group btn-group-sm filter_items">

        </div>
        <br/><br/><br/>
    </div>
    
    <div id="dash">
<center><h1 class="nope">Загружаю меню <div class="loader-menu fa fa-spin colored-border"></div></h1></center>
           </div>
</div>

<div class='layer' id="guests">
    
   <div class="loader-menu fa fa-spin colored-border"></div>


    </div>

<div id="check">
    <br/>
    <center>************************</center>
    <center><span class="place_name_check"></span><br/><span class="place_address_check"></span>
       </center>
     <center>ЧЕК № <span class="check_id"></span></center>
     <center>************************</center>
    <center>Кассир: <span class="lastname_check"></span> <span class="name_check"></span></center>
    <br/>
     <div style="padding:15px;" class="list_zakaz_check">
      <br/>
      Такое 1<span style="float:right;">РУБ:  100</span><br/>
      ----------------------------------------<br/>
      Такое 1<span style="float:right;">РУБ:  100</span><br/>
      ----------------------------------------<br/>
      Такое 1<span style="float:right;">РУБ:  100</span><br/>
      ----------------------------------------<br/>
      Такое 1<span style="float:right;">РУБ:  100</span><br/>
      ----------------------------------------<br/>
          <center>************************</center>
      </div>
     <center>Итого:<span class="itogo_check"></span> РУБ.</center>
     <br/><br/>
<center><b>Большое спасибо!<br/>Произвольная строка</b>
      <br/><br/>

      ИНН: ***********, КПП:*********<br/>
      11/12/2017, 12:12:12<br/>
      Тел: 0(000)000-00-00<br/>
      <u>MOKKO-CRM.RU</u>
<br/>
<br/>
      </center>
</div>

</div>



</div>
</div>



</div>




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
<!---если залогинен------>
<!--- тарифы
<div class="container-table">
<div class="pricing wow fadeInRight table-1 animated" data-wow-offset="400" data-wow-delay=".2s" style="visibility: visible;-webkit-animation-delay: .2s; -moz-animation-delay: .2s; animation-delay: .2s;">
<h3>Кофейня</h3>
<div class="price-container ">
<div class="col-md-12 dollar">4000</div>
<div class="col-md-12 per-month">РУБ. / МЕСЯЦ</div>
</div>
<em class="detail-price">Тариф для небольшой кофейни</em>
<ul class="price-features">
<li>
<span class="feature">Формирование заказа</span>
<span class="check glyphicon glyphicon-ok-sign pull-right"></span>
</li>
<li>
<span class="feature">Формирование меню</span>
<span class="check glyphicon glyphicon-ok-sign pull-right"></span>
</li>
<li>
<span class="feature">Подробная статистика</span>
<span class="check glyphicon glyphicon-ok-sign pull-right"></span>
</li>
<li>
<span class="feature">Кухня</span>
<span class="cross icon-cross"></span>
</li>
<li>
<span class="feature">Учет склада</span>
<span class="cross icon-cross"></span>
</li>
<li>
<span class="feature">Кофейни</span>
<span class="check pull-right">от 1</span>
</li>
<li>
<span class="feature">Пользователи</span>
<span class="check pull-right">от 3</span>
</li>
</ul>
<a class="order" href="#">Выбрать</a>

</div>


<div class="pricing wow bounceIn animated" data-wow-offset="300" style="visibility: visible;">
<h3>Ресторан</h3>
<div class="price-container ">
<div class="col-md-12 dollar">9000</div>
<div class="col-md-12 per-month">РУБ. / МЕСЯЦ</div>
</div>
<em class="detail-price">Тариф для бльшой кофейни</em>
<ul class="price-features">
<li>
<span class="feature">Формирование заказа</span>
<span class="check glyphicon glyphicon-ok-sign pull-right"></span>
</li>
<li>
<span class="feature">Формирование меню</span>
<span class="check glyphicon glyphicon-ok-sign pull-right"></span>
</li>
<li>
<span class="feature">Подробная статистика</span>
<span class="check glyphicon glyphicon-ok-sign pull-right"></span>
</li>
<li>
<span class="feature">Кухня</span>
<span class="check glyphicon glyphicon-ok-sign pull-right"></span>
</li>
<li>
<span class="feature">Учет склада</span>
<span class="check glyphicon glyphicon-ok-sign pull-right"></span>
</li>
<li>
<span class="feature">Кофейни</span>
<span class="check pull-right"> от 3</span>
</li>
<li>
<span class="feature">Пользователи</span>
<span class="check pull-right">от 10</span>
</li>        </ul>
<a class="order" href="#">Выбрать</a>

</div>


<div class="pricing wow fadeInLeft table-3 animated" data-wow-offset="400" data-wow-delay=".2s" style="visibility: visible;-webkit-animation-delay: .2s; -moz-animation-delay: .2s; animation-delay: .2s;">
<h3>Business</h3>
<div class="price-container ">
<div class="col-md-12 dollar">$60</div>
<div class="col-md-12 per-month">/ MONTH</div>
</div>
<em class="detail-price">Little description about this.</em>
<ul class="price-features">
<li>
<span class="feature">Feature #1</span>
<span class="check icon-checkmark"></span>
</li>
<li>
<span class="feature">Feature #2</span>
<span class="check icon-checkmark"></span>
</li>
<li>
<span class="feature">Feature #3</span>
<span class="check icon-checkmark"></span>
</li>
<li>
<span class="feature">Feature #4</span>
<span class="cross icon-cross"></span>
</li>
</ul>
<a class="order" href="#">Order Now!</a>

</div>
</div>
<!-----если залогинен конец---->

<hr>
<small class="app-foot">MOKKO CRM, 2026</small>
</div>

<div class="darkness"></div>






<div id="pageloader">
<div class="loader-item fa fa-spin colored-border"></div>
</div>


<div id="microloader">
<div class="loader-item fa fa-spin colored-border"></div>
</div>

<div id="miniloader">
<div class="loader-item fa fa-spin colored-border"></div>
</div>

<script src="<?= BASE_PATH ?>/js/offline.js"></script>
<script src="<?= BASE_PATH ?>/js/chartist.js"></script>
<script src="<?= BASE_PATH ?>/js/jquery.cookie.js"></script>
<script src="<?= BASE_PATH ?>/js/moment.js"></script>
<script src="<?= BASE_PATH ?>/js/date.js"></script>

<script type="text/javascript" src="<?= BASE_PATH ?>/js/crm.js?ver=20"></script>
<script type="text/javascript" src="<?= BASE_PATH ?>/js/interface.js?ver=1"></script>

<? require_once('metrika.php'); ?>


</body>
</html>
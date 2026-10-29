<?PHP
if(!defined('MOKKO'))die;

?>
    <!DOCTYPE html>
    <html>
    <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="keywords" content="CRM ERP система для автмоатизации общепита, автоматизация общественного питания, ERP CRM, MOKKO CRM ERP" />
<meta name="description" content="CRM ERP для автоматизации общественного питания, CRM ERP для кофеен" />
    <title>Предзаказ MOKKO CRM</title>
<script type="text/javascript" src="//vk.com/js/api/openapi.js?129"></script>

<!-- VK Widget -->
<div id="vk_community_messages"></div>
<script type="text/javascript">
VK.Widgets.CommunityMessages("vk_community_messages", 40968365, {});
</script>

    <link rel="icon" type="image/svg+xml" href="<?= BASE_PATH ?>/images/brand/favicon.svg?v=2">
    <link href="<?= BASE_PATH ?>/css/bootstrap-4.6.2.min.css" rel="stylesheet">
    <link href="<?= BASE_PATH ?>/css/glyphicons.css" rel="stylesheet">
    <link href="<?= BASE_PATH ?>/css/theme.css?v=34" rel="stylesheet">

    </head>
    
    <body class="subscribe-body">
    
    <div class="site-wrapper">
    
    <div class="site-wrapper-inner">
    
    <div class="cover-container">
    
    <div class="masthead clearfix">
    <div class="inner">
    <a class="brand" href="<?= BASE_PATH ?>/login" aria-label="MOKKO CRM">
        <span class="brand-text"><img class="brand-word" src="<?= BASE_PATH ?>/images/brand/wordmark-sm.svg?v=1" alt="" width="240" height="50"><span class="brand-crm">CRM</span></span>
    </a>
    <nav>
    </nav>
    </div>
    </div>


<a class="vk" title="mokko crm вконтакте" href="https://vk.com/mokko_crm"><img src="<?= BASE_PATH ?>/images/VK.png"></a>

    <div class="inner cover">
<h1 class="cover-heading bad">Предзаказ</h1>
    <p class="lead bad">Оставьте свои контактные данные, как только мы завершим разработку, оповестим Вас и поможем начать работу с CRM!</p>
    <p class="lead">
<div class="row">
<div class="col-3">
</div>
<div class="col-6">
<noindex><form>
<input maxlength="11" class="form-control input-lg phone" placeholder="Телефон" type="text" data-toggle="popover" data-placement="left" data-content="Тут совершенно пусто"/>
<input class="form-control input-lg" placeholder="Email" type="email" data-toggle="popover" data-placement="left" data-content="Тут совершенно пусто"/>
<button type="submit" class="btn btn-primary btn-lg">Оставить заявку</button>
</form></noindex>
</div>
</div>
    </p>
    </div>
    
    <div class="mastfoot">
    <div class="inner">
<p style="cursor:pointer;" onclick="alert('Настоящим в соответствии с Федеральным законом № 152-ФЗ «О персональных данных» от 27.07.2006 года свободно, своей волей и в своем интересе выражаю свое безусловное согласие на обработку моих персональных данных , зарегистрированным в соответствии с законодательством РФ по адресу: www.mokko-crm.ru (далее по тексту - Оператор). Персональные данные - любая информация, относящаяся к определенному или определяемому на основании такой информации физическому лицу.Настоящее Согласие выдано мною на обработку следующих персональных данных: телефон, дата, электронная почта.Согласие дано Оператору для совершения следующих действий с моими персональными данными с использованием средств автоматизации и/или без использования таких средств: сбор, систематизация, накопление, хранение, уточнение (обновление, изменение), использование, обезличивание, а также осуществление любых иных действий, предусмотренных действующим законодательством РФ как неавтоматизированными, так и автоматизированными способами. Данное согласие дается Оператору для обработки моих персональных данных в следующих целях: - предоставление мне услуг/работ; - направление в мой адрес уведомлений, касающихся предоставляемых услуг/работ; - подготовка и направление ответов на мои запросы; - направление в мой адрес информации, в том числе рекламной, о мероприятиях/товарах/услугах/работах Оператора. Настоящее согласие действует до момента его отзыва путем направления соответствующего уведомления на электронный адрес info@mokko-crm.ru В случае отзыва мною согласия на обработку персональных данных Оператор вправе продолжить обработку персональных данных без моего согласия при наличии оснований, указанных в пунктах 2 – 11 части 1 статьи 6, части 2 статьи 10 и части 2 статьи 11 Федерального закона №152-ФЗ «О персональных данных»');">Нажимая кнопку «Оставить заявку», я даю свое согласие на обработку моих персональных данных, в соответствии с Федеральным законом от 27.07.2006 года №152-ФЗ «О персональных данных», на условиях и для целей, определенных в Согласии на обработку персональных данных<br/>&copy; Удобрая CRM для кофеен - <a href="https://mokko-crm.ru/">МОККО CRM</a>.</p>
    </div>
    </div>
    
    </div>
    
    </div>
    
    </div>

    <script src="<?= BASE_PATH ?>/js/jquery-1.11.1.min.js?v=1"></script>
    <script src="<?= BASE_PATH ?>/js/bootstrap-4.6.2.bundle.min.js"></script>
    <script src="<?= BASE_PATH ?>/js/theme.js?v=14"></script>
<script>
$(document).ready(function($) {
                                      $('input:first').popover('hide');
                                      $('input:last').popover('hide');
                  
                  
                  $('input').on('keydown', function(event) {
                                if(event.which == 13){
                                return false;
                                }
                                });

$(".phone").keydown(function(event) {

                        if ( event.keyCode == 46 || event.keyCode == 8 || event.keyCode == 9 || event.keyCode == 27 ||
                            (event.keyCode == 65 && event.ctrlKey === true) ||
                            (event.keyCode >= 35 && event.keyCode <= 39)) {
                        return;
                        }else {
                        if ((event.keyCode < 48 || event.keyCode > 57) && (event.keyCode < 96 || event.keyCode > 105 )) {
                        event.preventDefault();
                        }
                        }
                        });
                  
                  
                  $( "form" ).submit(function( event ) {
                    if ( $( "input:first" ).val() == "" ) {
                    $('input:first').popover('show');
                    
                    if ( $( "input:last" ).val() == "" ) {
                    $('input:last').popover('show');
                    }
                    return false;
                    }else{
                    if ( $( "input:last" ).val() == "" ) {
                    $('input:last').popover('show');
                    return false;
                    }else{
                    //
                    var phone = $( "input:first" ).val();
                    var email = $( "input:last" ).val();
                    $.ajax({
                    type: "POST",
                    url: "subscriber.php",
                    data: "phone="+phone+"&email="+email,
                    success: function(data){
                    /////
                    $('.inner.cover').empty().append('<h1 class="cover-heading bad">Большое спасибо!</h1>');
                    /////
                    
                    }
                    });
                    //
                                     
                    }
                    }
                    return false;
                    });
                                     
});
</script>
<!-- Yandex.Metrika counter -->
<?php
    include __DIR__ . '/metrika.php';
?>
<!-- /Yandex.Metrika counter -->
    </body>
    </html>
<?php if (!defined('MOKKO')) { die; } ?>
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

<?php require_once __DIR__ . '/../copyright.php'; ?>
<hr>
<small class="app-foot"><?= mokko_copyright() ?></small>
</div>

<div class="darkness"></div>






<div id="pageloader" role="status" aria-live="polite" aria-label="Загрузка">
<div class="mk-page-loader">
<div class="mk-page-loader-brand">
<img src="<?= BASE_PATH ?>/images/brand/wordmark-sm.svg?v=1" alt="" width="150" height="32">
<span>CRM</span>
</div>
<span class="mk-page-loader-ring" aria-hidden="true"></span>
<span class="mk-page-loader-text">Загружаем рабочее место…</span>
</div>
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

<script type="text/javascript" src="<?= BASE_PATH ?>/js/api.js?v=2"></script>
<script type="text/javascript" src="<?= BASE_PATH ?>/js/order.js?ver=1"></script>
<script type="text/javascript" src="<?= BASE_PATH ?>/js/crm.js?ver=25"></script>
<script type="text/javascript" src="<?= BASE_PATH ?>/js/interface.js?ver=5"></script>
<script type="text/javascript" src="<?= BASE_PATH ?>/js/pet.js?ver=13"></script>



</body>
</html>
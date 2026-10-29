<?php if (!defined('MOKKO')) { die; } ?>






</div>
</div>





</div>



<?php require_once __DIR__ . '/../copyright.php'; ?>
<small class="app-foot"><?= mokko_copyright() ?></small>
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
<script src="<?= BASE_PATH ?>/js/moment.js"></script>
<script src="<?= BASE_PATH ?>/js/date.format.js"></script>

<script type="text/javascript" src="<?= BASE_PATH ?>/js/charts.js?v=1"></script>


<script type="text/javascript" src="<?= BASE_PATH ?>/js/api.js?v=1"></script>
<script type="text/javascript" src="<?= BASE_PATH ?>/js/panel/helpers.js?v=2"></script>
<script type="text/javascript" src="<?= BASE_PATH ?>/js/panel.js?ver=26"></script>

<script type="text/javascript">
// Мобильный бургер: клик по любому пункту меню закрывает список.
// Вешаем прямо на ссылки (а не делегатом на document): обработчик .navigation a
// в panel.js делает return false (= stopPropagation), и делегат бы не сработал.
// Обработчики на одном элементе выполняются все, stopPropagation их не блокирует.
(function ($) {
    $(function () {
        $('#bs-example-navbar-collapse-1 a').on('click', function () {
            if ($('.panel-menu-toggle').is(':visible')) {
                $('#bs-example-navbar-collapse-1').collapse('hide');
            }
        });
    });
})(window.jQuery);
</script>







<?php mokko_hook('panel.footer'); ?>

</body>
</html>


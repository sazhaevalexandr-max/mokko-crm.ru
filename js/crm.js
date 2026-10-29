

// Явные переменные, которые раньше создавались неявно внутри функций.
// Убирает загрязнение window и готовит файл к strict mode / переводу в модуль.
// (check вынесен в модуль заказа order.js — см. order.collect / collectForPayment.)
var month, date_time, load_id;

// ── Таймер смены ────────────────────────────────────────────────────────────
// Время считается от момента начала смены, а не прибавлением секунд:
// браузер замедляет таймеры в свёрнутых вкладках, а при сне компьютера
// они стоят — счёт «по тикам» отставал. Начало смены приходит с сервера
// (startshift), поэтому после перезагрузки таймер продолжает ту же смену.
var shiftTimer = (function () {
    var startedAt = null, handle = null, el = null;
    function view() {
        if (!el) {
            var box = document.getElementsByClassName('time_online')[0];
            if (!box) { return null; }
            el = document.createElement('b');
            box.appendChild(el);
        }
        return el;
    }
    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function elapsedSec() { return startedAt ? Math.max(0, Math.floor((Date.now() - startedAt) / 1000)) : 0; }
    function render() {
        var v = view(); if (!v) { return; }
        var t = elapsedSec();
        v.textContent = Math.floor(t / 3600) + ':' + pad(Math.floor(t / 60) % 60) + ':' + pad(t % 60);
    }
    return {
        start: function (elapsedSeconds) {
            startedAt = Date.now() - (Number(elapsedSeconds) || 0) * 1000;
            clearInterval(handle);
            render();
            handle = setInterval(render, 1000);
        },
        stop: function () {
            clearInterval(handle);
            handle = null;
            render();
            var frozen = elapsedSec();
            startedAt = null;
            return frozen;
        },
        elapsed: function () {
            var t = elapsedSec();
            return { h: Math.floor(t / 3600), m: Math.floor(t / 60) % 60, s: t % 60, total: t };
        },
        running: function () { return handle !== null; }
    };
})();
// Старые cookie счётчика больше не нужны — убираем, чтобы не мешали
$.removeCookie('time_h'); $.removeCookie('time_m'); $.removeCookie('time_s');
$(function () { var v = document.getElementsByClassName('time_online')[0]; if (v && !v.firstChild) { v.innerHTML = '<b>0:00:00</b>'; } });

// Настройки печати чеков сохраняются между сменами (выбираются в окне «Начало смены»)
if (!$.cookie('checkswitch')) { $.cookie('checkswitch', 'off', { expires: 365 }); }
if (!$.cookie('checkssize')) { $.cookie('checkssize', '80', { expires: 365 }); }

function clock() {
    var d = new Date();
    var month_num = d.getMonth()
    var day = d.getDate();
    var hours = d.getHours();
    var minutes = d.getMinutes();
    var seconds = d.getSeconds();
    month=new Array("января", "февраля", "марта", "апреля", "мая", "июня",
                    "июля", "августа", "сентября", "октября", "ноября", "декабря");
    
    
    if (day <= 9) day = "0" + day;
    if (hours <= 9) hours = "0" + hours;
    if (minutes <= 9) minutes = "0" + minutes;
    if (seconds <= 9) seconds = "0" + seconds;
    
    date_time = "<h4>" + day + " " + month[month_num] +
    "<br/><small>"+ hours + ":" + minutes + ":" + seconds;
    if (document.layers) {
        document.layers.doc_time.document.write(date_time);
        document.layers.doc_time.document.close();
    }
    else document.getElementById("doc_time").innerHTML = date_time;
    setTimeout(clock, 1000);
}
///clock();

if(typeof($.cookie('last_num')) !== undefined ){
    $.cookie('last_num', '0');
    console.log($.cookie('last_num'));
}

function get_number(){
    var lastnum = Number($.cookie('last_num'))+1
    if(lastnum > Number($.cookie('max_num'))){
    lastnum = 1;
    }

    $.cookie('last_num', lastnum);
    
    return lastnum;
}

// ── Строка «Текущий счёт / Открыть счёт» ───────────────────────────────────
function billNextNumber() {
    var prefix = $.cookie('prefix') || '';
    var n = (Number($.cookie('last_num')) || 0) + 1;
    var max = Number($.cookie('max_num')) || 0;
    if (max && n > max) { n = 1; }
    return $.trim(prefix + ' ' + n);
}
function billRefreshHint() { $('.bill-next').text(billNextNumber()); }
function billSetOpen(isOpen) {
    $('.bill-current').toggleClass('is-empty', !isOpen).removeClass('is-editing');
    if (!isOpen) { $('a.name_guest').text('Не открыт'); }
    billRefreshHint();
}
function billNudge() {
    var $g = $('.bill-new-group');
    $g.removeClass('is-nudge'); void $g[0].offsetWidth; $g.addClass('is-nudge');
    $('#guest').trigger('focus');
}
$(document).on('animationend', '.bill-new-group', function () { $(this).removeClass('is-nudge'); });
$(document).on('keydown', '#guest', function (e) {
    if (e.which === 13) { e.preventDefault(); $('.add_guest').trigger('click'); }
});
$(document).on('keydown', '.new_name', function (e) {
    if (e.which === 13) { e.preventDefault(); $('.save_guest').trigger('click'); }
    if (e.which === 27) { e.preventDefault(); billCancelRename(); }
});
function billCancelRename() {
    $('.input_guest').hide();
    $('.save_guest').hide();
    $('.name_guest').css('display', '');
    $('.edit_guest').css('display', '');
    $('.bill-current').removeClass('is-editing');
}
$(document).on('click', '.name_guest', function (e) { e.preventDefault(); });
$(document).on('click', '.edit_guest', function () {
    if (window.guest_id === undefined) { billNudge(); return false; }
    $('.bill-current').addClass('is-editing');
    setTimeout(function () { var $n = $('.new_name'); $n.trigger('focus'); try { $n[0].select(); } catch (e) {} }, 0);
});
$(function () { billRefreshHint(); });

$(document).ready(function($) {
                  window.guests_list = '';
                  
				   $.ajax({
                         type: 'GET',
                         url: 'api:///startshift/{"uid":"'+uid+'","sid":"'+sid+'","key":"'+key+'","place": "'+place+'"}',
                         success: function(data){
						 if (data && data.error) { console.error('startshift:', data.error); shiftSetup(); return; }
						 window.shift = data.id;
						 var box = document.getElementsByClassName('time_online')[0];
						 if (box) { box.innerHTML = ''; }
						 shiftTimer.start(data.elapsed || 0);
						 // Окно «Начало смены» — только для новой смены. Если смена уже идёт
						 // (перезагрузка страницы), сервер её продолжает — окно не нужно.
						 if (!data.resumed) { shiftSetup(); } else { setTimeout(function () { if (typeof billRefreshHint === 'function') { billRefreshHint(); } }, 0); }
                    },
                         error: function(){ shiftSetup(); }
                   });
						 
                  
          
                                   if ($(window).width() <= '900'){
                                                $('.total_summ').addClass('fixed');
                                                // Новая кнопка закрепления не удаляется старым кодом.
                                                $('.total_summ .bill-pin-label').text('Закрепить');
                                                $('body').append($('.total_summ'));
                   }
                  
                                   
                                   
                  

                  
                  
                  // ── Окно «Начало смены»: показывается при каждом входе в кассу ──────────
                  function shiftSetup(){
                      var esc = function (v) { return $('<div>').text(v == null ? '' : String(v)).html(); };
                      var letters = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
                      var prefix = $.cookie('prefix') || letters.charAt(Math.floor(Math.random() * letters.length));
                      var maxNum = $.cookie('max_num') || '50';
                      var printOn = $.cookie('checkswitch') === 'on';
                      var size = $.cookie('checkssize') === '57' ? '57' : '80';
                      // значения по умолчанию, даже если окно закроют крестиком
                      if (!$.cookie('prefix')) { $.cookie('prefix', prefix, { expires: 365 }); }
                      if (!$.cookie('max_num')) { $.cookie('max_num', maxNum, { expires: 365 }); }

                      $.alert({
                          title: 'Начало смены',
                          columnClass: 'col-md-6 offset-md-3 col-sm-10 offset-sm-1 shift-dialog',
                          confirmButton: 'Начать смену',
                          content:
                            '<div class="shift-setup" id="shift-setup">' +
                              '<p class="shift-lead">Проверьте настройки перед работой — они сохранятся до следующей смены.</p>' +
                              '<section class="shift-block">' +
                                '<div class="shift-block-title">Номера заказов</div>' +
                                '<div class="shift-numbers">' +
                                  '<div class="shift-example" aria-hidden="true"><small>Так увидит гость</small><b><span class="prefix_ex">' + esc(prefix) + '</span>&thinsp;12</b></div>' +
                                  '<div class="shift-fields">' +
                                    '<div class="pf"><label for="prefix">Префикс</label><input type="text" id="prefix" name="prefix" class="form-control" maxlength="5" autocomplete="off" value="' + esc(prefix) + '"></div>' +
                                    '<div class="pf"><label for="max_num">Начинать заново после №</label><input type="text" id="max_num" name="max_num" class="form-control" inputmode="numeric" maxlength="3" autocomplete="off" value="' + esc(maxNum) + '"></div>' +
                                  '</div>' +
                                '</div>' +
                              '</section>' +
                              // Блок «Чеки» скрыт — печать чеков временно отключена. Чтобы вернуть, раскомментируйте строки ниже
                              // '<section class="shift-block">' +
                                // '<div class="shift-block-title">Чеки</div>' +
                                // '<div class="shift-print">' +
                                  // '<div class="checkset"><input id="checkon" class="package-toggle toggle-round-flat" type="checkbox" name="checkon"' + (printOn ? ' checked' : '') + '><label for="checkon"></label><span>Печатать чек после оплаты</span></div>' +
                                  // '<select class="form-control" name="checksize" aria-label="Ширина чековой ленты"' + (printOn ? '' : ' disabled') + '>' +
                                    // '<option value="80"' + (size === '80' ? ' selected' : '') + '>Лента 80 мм</option>' +
                                    // '<option value="57"' + (size === '57' ? ' selected' : '') + '>Лента 57 мм</option>' +
                                  // '</select>' +
                                // '</div>' +
                              // '</section>' +
                              '<p class="pf-error" role="alert"></p>' +
                            '</div>',
                          confirm: function(){
                              var $f = $('#shift-setup');
                              var p = $.trim($f.find('input[name=prefix]').val());
                              var m = $.trim($f.find('input[name=max_num]').val());
                              var bad = function (name, text) {
                                  $f.find('input[name=' + name + ']').addClass('is-invalid').focus();
                                  $f.find('.pf-error').text(text).addClass('is-open');
                                  return false;
                              };
                              $f.find('.form-control').removeClass('is-invalid');
                              if (!p) { return bad('prefix', 'Укажите префикс — например, букву K.'); }
                              if (!/^\d+$/.test(m) || Number(m) < 1) { return bad('max_num', 'Укажите номер от 1, например 50.'); }
                              // Печать чеков скрыта — настройки печати не сохраняем (раскомментировать вместе с блоком «Чеки»)
                              // var on = $f.find('input[name=checkon]').is(':checked');
                              $.cookie('prefix', p, { expires: 365 });
                              $.cookie('max_num', String(Number(m)), { expires: 365 });
                              setTimeout(billRefreshHint, 0);
                              // $.cookie('checkswitch', on ? 'on' : 'off', { expires: 365 });
                              // $.cookie('checkssize', $f.find('select[name=checksize]').val(), { expires: 365 });
                          }
                      });
                  }

                  $('body').on('keydown', '#shift-setup input[name=max_num]', function(event) {
                      if (event.keyCode == 46 || event.keyCode == 8 || event.keyCode == 9 || event.keyCode == 27 || event.keyCode == 13 ||
                          (event.keyCode == 65 && event.ctrlKey === true) || (event.keyCode >= 35 && event.keyCode <= 39)) { return; }
                      if ((event.keyCode < 48 || event.keyCode > 57) && (event.keyCode < 96 || event.keyCode > 105)) { event.preventDefault(); }
                  });
                  $('body').on('input', '#shift-setup input', function() {
                      $(this).removeClass('is-invalid');
                      $('#shift-setup .pf-error').removeClass('is-open');
                      if (this.name === 'prefix') {
                          $('#shift-setup .prefix_ex').text(this.value || '—');
                          var $ex = $('#shift-setup .shift-example b');
                          $ex.removeClass('is-bumping'); void $ex[0].offsetWidth; $ex.addClass('is-bumping');
                      }
                  });
                  // Печать чеков скрыта: переключатель ширины ленты — раскомментировать вместе с блоком «Чеки»
                  // $('body').on('change', '#shift-setup input[name=checkon]', function() {
                      // $('#shift-setup select[name=checksize]').prop('disabled', !this.checked);
                  // });
                  ////
                  

                  load_menu();
                 // load_items("all");
                  
                  var
                  $online = $('.online'),
                  $offline = $('.offline');
                  
                  Offline.on('confirmed-down', function () {
                             $online.fadeOut(function () {
                                             $offline.fadeIn();
                                             });
                             });
                  
                  Offline.on('confirmed-up', function () {
                             $offline.fadeOut(function () {
                                              $online.fadeIn();
                                              });
                             });
                  
                  
                  //$('ul.tabs').tabs();
                  
                  // window.guest_id = '';
                  
                  

           
                  
                   
                  
                  
                  
                  var input_guest = $('.input_guest');
                  input_guest.css('display','none');
                  ///////////
                  $('.layer').css('display','none');
                  $('#dashboard').css('display','block');
                  //////////
                  $('body').on('click', '.edit_guest', function(){
                               if(window.guest_id!==undefined){
                               var name_guest = $('.guest a').text();
                               var phone_guest = $('.guest a').attr('phone');
                               $('.name_guest').css('display','none');
                               $('.edit_guest').css('display','none');
                               $('.save_guest').css('display','block');
                               input_guest.css('display','block');
                               input_guest.find('.new_name').val(name_guest);
                               input_guest.find('.new_phone').val(phone_guest);
                               }
                               return false;
                               });
                  
                  
                  function add_guest_tap(){
                  $('.add_guest').prop( "disabled", true );
                  
                  var name = $('#guest_form input[name="guest"]').val();
                  if(name ==''){
                  name = $.cookie('prefix')+" "+get_number();
                  }
                
                  var type = $('#guest_form input[name="type_guest"]').val();
                  var id_guest = $('#guest_form input[name="id_guest"]').val();

                  var activeguest = window.guest_id;
                  payed = order.collect(activeguest);

                  var price = Number($('.total_summ .sum').text());

                  if(activeguest !== undefined && payed.length > 0){
                      $.ajax({
                             type: 'GET',
                             url: 'api:///guests/edit/{"uid":"'+uid+'","sid":"'+sid+'","key":"'+key+'","id":"'+activeguest+'","place": "'+place+'","items":"'+JSON.stringify(payed)+'","price":"'+price+'"}',
                             success: function(data){
                                 console.log('saved bill', activeguest, data);
                                 the_new_guest(name);
                             },
                             error: function(){
                                 the_new_guest(name);
                             }
                             });
                  } else {
                      the_new_guest(name);
                  }

                  }
               
                  function load_if_empty_dash(){
                  
                  if($('#dash div').length==0){
                  load_menu();
                  //load_items('all');
                  }else{
                  $('.item.active').removeClass('active').find('.kolvo').empty().append(1);
                  
                  }
                  
                  return false;
                  }
                  

                  function the_new_guest(name){
                  if(typeof((name))=='undefined'){
                  var name = '';
                  }
				  console.log("ADD_GUEST");
				 // alert('api:///guests/new/{"uid":"'+uid+'","sid":"'+sid+'","name" : "'+name+'", "key":"'+key+'","place": "'+place+'"}');
                  $.ajax({
                         type: 'GET',
                         url: 'api:///guests/new/{"uid":"'+uid+'","sid":"'+sid+'","name" : "'+name+'", "key":"'+key+'","place": "'+place+'"}',
                         success: function(data){
							 
						
                         console.log(data);
                         if(data!==''){

                         var guest_load = data;
                         $('.name_guest').empty().append(guest_load.name);
                         // $('.name_guest').attr('phone',guest_load.phone);
                         $('.add_guest').prop( "disabled", false );
                         window.guest_id = guest_load.Id;
                         $('.check_id').empty().append(guest_load.Id);
                         console.log(window.guest_id);
                         $('.filter_items').css('display','block');
                         $('.name_guest').empty().append(guest_load.name);
                         ///перезапуск даш и чек
                         //$("#dash").empty();
                         $("#odd span").empty().append('0');
                         $(".zakaz").empty();
                         $("input.vneseno").attr('value','');
                         
                         load_if_empty_dash();
                        // load_menu();
                        // load_items('all');
                         
                         total_sum();
                         $('#guest').val('');
                         $('input[name=type_guest]').val('new');
                         $('input[name=id_guest]').val('');
                         $('.add_guest span').text('Открыть счёт');
                         billSetOpen(true);
                         //////
                         }
                         }
                         });
                  return false;
                  }
                  
                  $('body').on('click', '.save_guest', function(){
                               input_guest.css('display','none');
                               var new_name = $('input.new_name').val();
                             //  var new_phone = $('.new_phone').attr('value');
                               $('.save_guest').prop("disabled", true);
                               //var msg = $('#edit_g').serialize();
                               //console.log(msg);
                               $.ajax({
                                      type: 'GET',
                                      url: 'api:///guests/update/{"uid":"'+uid+'","sid":"'+sid+'","key":"'+key+'","id":"'+window.guest_id+'","name":"'+new_name+'"}',
                                      success: function(data){
                                      console.log(data);
                                      if(data!=='error'){
                                      $('.name_guest').css('display','').text(new_name);
                                      $('.bill-current').removeClass('is-editing');
                                      ///$('.name_guest').attr('phone',new_phone);
                                      $('.edit_guest').css('display','inline');
                                      $('.save_guest').css('display','none');
                                      }
                                      $('.save_guest').prop( "disabled", false );
                                      //console.log(data);
                                      
                                      }
                                      });
                               });
                  
                  function active_guest(id_guest){
                  
                  var name = $('a.name_guest').text();
                  var price = Number($('.total_summ .sum').text());
                  
                  payed = order.collect(id_guest);
                  
    if(id_guest!==undefined){
    $.ajax({
    type: 'GET',
    url: 'api:///guests/edit/{"uid":"'+uid+'","sid":"'+sid+'","key":"'+key+'","id":"'+window.guest_id+'","place":"'+place+'","items":"'+JSON.stringify(payed)+'","price":"'+price+'"}',
           success: function(data){
           console.log(data);
           }
           });
    }else{
                  }
    }
                  
                  function get_guest(id_guest){
                  $('#microloader').css('display','block');
                  $('#microloader .colored-border').css('display','block');

                  $.ajax({
                         type: 'GET',
                         url: 'api:///guests/get/{"uid":"'+uid+'","sid":"'+sid+'","key":"'+key+'", "id": "'+id_guest+'"}',
                         success: function(data){
                           $('#microloader').css('display','none');
                         
                         var guest = data;
                         console.log(data);
                         if(guest.name == ''){
                         guest.name = "#"+guest.id;
                         }
                         window.guest_id = guest.id;
                         $('.check_id').empty().append(guest.id);
                         $('.name_guest').css('display','').text(guest.name);
                         billSetOpen(true);
                         ///$("#dash").empty();
                         $("#odd span").empty().append(0);
                         $(".zakaz").empty();
                         $("input.vneseno").attr('value','');
                         //load_items('all');
                         total_sum();
                         $('.layer').css('display','none');
                         $('#dashboard').css('display','block');
                         $('.menu_left').find('a.active').removeClass('active');
                         
                         $('.menu_left a:first').addClass('active');
                         $('.filter_items').css('display','block');
                         
                         
                         var guest_items = JSON.parse(guest.items);
                         
                         // 13,1,644
                         
                         if(guest.price!==''){
                         if(guest.items.leigth !== 2){
                         $('.total_summ span.sum').empty().append(guest.price);
                       
                         //setTimeout(function(){
                                    $.each(guest_items, function (i,item){
                                           
                                           var item_name = $('.item[item_id="'+item[0]+'"] span').text();
                                           var item_price = $('.item[item_id="'+item[0]+'"] small b').text();
                                           
                                           $('.item[item_id="'+item[0]+'"]').addClass('active');
                                           
                                           if(item[1]!==1){
                                           $('.item[item_id="'+item[0]+'"]').find('.kolvo').empty().append(Number(item[1]));
                                           item[1] = '<b>'+Number(item[1])+'</b>';
                                           
                                           }else{
                                           item[1] = '';
                                           }
                                           
                                           $('ul.zakaz').append('<li item_id="'+item[0]+'"><span>'+item_name+'</span></div></strong><strong class="pull-right">'+item_price+'</strong>'+item[1]+'</li>');
                                           });
                          //          },4000);
                         

                         }
                         
                         }
                         }
                         });
                  

                  
                  
                  
                  return false;
                  }
                  
                  $('body').on('click', '.load_guests', function(){
                               load_guests();
                               //load_menu();
                               });
                  
                  $('body').on('click', '.edit_guest', function(){
                               if(window.guest_id!==undefined){
                               var name_guest = $('.guest a').text();
                               var phone_guest = $('.guest a').attr('phone');
                               $('.name_guest').css('display','none');
                               $('.edit_guest').css('display','none');
                               $('.save_guest').css('display','block');
                               input_guest.css('display','block');
                               input_guest.find('.new_name').val(name_guest);
                               input_guest.find('.new_phone').val(phone_guest);
                               }
                               });
                  
                  $('body').on('click','.add_guest',function(){
                               add_guest_tap();
                               });
                  
                  // Enter не отправляет формы; поля счёта обрабатывают Enter сами (открыть / переименовать)
                  $('input').not('#guest, .new_name').on('keydown', function(event) {
                                if(event.which == 13){
                                return false;
                                }
                                });
                  
                  $('body').on('keydown', '.guest_list', function(e){
                               if (e.which === 13 || e.which === 32) { e.preventDefault(); $(this).trigger('click'); }
                               });
                  $('body').on('click', '.guest_list', function(){
                               load_id = $(this).attr('guest');
                               
                               get_guest(load_id);
                               
                               active_guest(window.guest_id);
                               load_if_empty_dash();
                               });
                  
                  
                  function load_guests(){
                  $.ajax({
                         type: 'GET',
                         url: 'api:///guests/load/{"uid":"'+uid+'","sid":"'+sid+'","key":"'+key+'","place":"'+place+'"}',

                         success: function(data){
                       
                         var guests = (typeof data === 'string') ? JSON.parse(data) : data;
                         if (!$.isArray(guests)) { guests = []; }
                         window.guests_list = guests;

                         function esc(v) { return $('<div>').text(v == null ? '' : String(v)).html(); }
                         function money(v) {
                             var n = Math.round(Number(v) || 0);
                             return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, '\u2009') + '\u00a0₽';
                         }
                         function plural(n) {
                             var m10 = n % 10, m100 = n % 100;
                             if (m10 === 1 && m100 !== 11) { return 'позиция'; }
                             if (m10 >= 2 && m10 <= 4 && (m100 < 12 || m100 > 14)) { return 'позиции'; }
                             return 'позиций';
                         }
                         function countItems(items) {
                             try { items = (typeof items === 'string') ? JSON.parse(items) : items; } catch (e) { return 0; }
                             var n = 0;
                             $.each(items || [], function (k, it) { n += ($.isArray(it) ? (Number(it[1]) || 1) : 1); });
                             return n;
                         }

                         var $wrap = $("#guests").empty();
                         $wrap.append("<div class='guests_head'><h3>Открытые счета</h3><span class='guests_count'>" + guests.length + "</span></div>");
                         if (!guests.length) {
                             $wrap.append("<div class='guests_empty'><b>Открытых счетов нет</b><span>Откройте счёт на вкладке «Заказ» — он появится здесь.</span></div>");
                             return;
                         }
                         var $grid = $("<div class='guests_grid'></div>").appendTo($wrap);
                         $.each(guests, function (i, guest){
                                var name = guest.name ? guest.name : '#' + (guest.id || guest.Id);
                                var price = Number(guest.price) || 0;
                                var qty = countItems(guest.items);
                                var isEmpty = price <= 0 && qty === 0;
                                var cls = 'guest_list' + (isEmpty ? ' is-empty' : '') + (String(window.guest_id) === String(guest.id) ? ' is-current' : '');
                                var meta = isEmpty ? 'Пустой счёт' : (qty ? qty + ' ' + plural(qty) : 'Без позиций');
                                $grid.append(
                                    "<div guest='" + esc(guest.id) + "' class='" + cls + "' role='button' tabindex='0'>" +
                                        "<span class='g-name'>" + esc(name) + "</span>" +
                                        "<span class='g-sum'>" + money(price) + "</span>" +
                                        "<span class='g-meta'>" + meta + "</span>" +
                                    "</div>");
                                });
                         }
                         });
                  
                  
                  }
                  
                  
                  $('.only_numbers').keydown(function(event) {
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
                  
                  
                  function ini(){
                  $('.edit_this_item').hide();
                  $('.layer').hide();
                  $('.layer.active').show();
                  $('.darkness').hide();
                  $('.top_alert').hide();
                  $('.startup').hide();
                  $('#miniloader').hide();
                  // load("categiry");
                  
                  $.ajax({
                         type: 'GET',
                         url: 'api:///ini/{"uid":"'+uid+'","sid":"'+sid+'","key":"'+key+'"}',
                         timeout:3000,
                         success: function(data) {
                         console.log(data);
                         var answer = data;
                         

                         if(answer['error']){
                         window.location.href = BASE_URL + "/?do=exit";
                         }else{
				
							 
							 
                         var answer = data;
                         $('#pageloader').hide();
                         $('.name_avatar').empty().append(answer.settings.name);
                         $('.avatar_letter').text(String(answer.settings.name || '').trim().charAt(0).toUpperCase() || '·');
                         $('.name_check').empty().append(answer.settings.name);
                         
                         if(typeof(answer.settings.lastname)!==undefined){
                         $('.lastname_check').empty().append(answer.settings.lastname);
                         }
                         var currency = answer.settings.currency;
                         ballance(answer.settings.wallet);
                         
                         switch(answer.settings.status){
                         case "new":
                         $('.top_alert').show().addClass('alert-info').empty().append('<center>У Вас нет активных подписок на сервисы</center>');
                         $('.startup').show();
                         break;
                         
                         case "active":
                         $('.startup').remove();
                         break;
                         
                         case "disable":
                         $('.startup').remove();
                         $('.top_alert').show().addClass('alert-danger').empty().append('<center>Аккаунт декативирван</center>');
                         break;
                         
                         case "low_wallet":
                         $('.startup').remove();
                         $('.top_alert').show().addClass('alert-warning').empty().append('<center>Срок подписки заканчивается, пополните счет во избежании блокировки</center>');
                         break;
                         
                         }
                    

                         }
						 	 $.ajax({
                                type: 'GET',
                                url: 'api:///places/get/{"uid":"'+uid+'","sid":"'+sid+'","key":"'+key+'","place":"'+place+'"}',
                                timeout:10000,
                                success: function(data) {
								console.log(data);
                                var place_data = data;
                                $(".place em").empty().append(place_data.name);
                                $(".place_name_check").empty().append(place_data.name);
                                $(".place_address_check").empty().append(place_data.address);
                                }
                                });
                         /*
                         $.ajax({
                                type: 'GET',
                                url: 'api:///items/load/{"uid":"'+uid+'","sid":"'+sid+'","key":"'+key+'","category":"all"}',
                                timeout:10000,
                                success: function(data) {
									console.log(data);
                                window.items_json = data;
                                }
                                });
                         */
                         }
                         });
                  
                  }
                  
                  ini();
                  /////функции
                  
                  function ballance(summ){
                  if(summ==0){
                  //$('html').empty().append('<center><h3>Пополните счет</h3></center>');
                  //return false;
                  }
                  }
                  
                  
                  
                  
                  function load(module){
                  $('#miniloader').show();
                  
                  
                  $.ajax({
                         
                         type: 'GET',
                         url: 'api:///'+module+'/load/{"uid":"'+uid+'","sid":"'+sid+'","key":"'+key+'"}',
                         timeout:4000,
                         success: function(data) {
                         viewer(module,data);
                         $('#miniloader').hide();
                         if(data['error']){
                         toaster(data);
                         ///обработчик ошибок
                         }else{
                         return data;
                         }
                         
                         }
                         });
                  
                  }
                  
                
                  
                  $('.navigation a').click(function(){
                                         $('.collapse.in').removeClass('in');
                                         $('.navigation .active').removeClass('active');
                                         
                                         $('.nav-sidebar.navigation a[href='+$(this).attr('href')+']').parent('li').addClass('active');
                                         
                                         $('.layer.active').hide();
                                         $('.layer.active').removeClass('active');
                                         
                                         $($(this).attr('href')).show();
                                         
                                         $($(this).attr('href')).addClass('active');
                                         var module = $(this).attr('module');
                                         if(typeof(module) !== "undefined"){
                                         load(module);
                                         }
                                         
                                         return false;
                                         });
                  
                  $('main_affix').affix('checkPosition');
                  
                  
                  
                function viewer(module,data,parametr){
                  
                  switch(module){
            
                    }
                  
                  }
                  
                  
                  
     
                  
                  // ── Смена своего пароля (бариста) ────────────────────────────
                  $('.new_pass_s').on('click', function(e){
                      e.preventDefault();
                      var f = function (name, label, hint) {
                          return '<div class="pf pf--wide"><label for="np-' + name + '">' + label + '</label>' +
                              '<input id="np-' + name + '" type="password" name="' + name + '" class="form-control" autocomplete="' + (name === 'old_pass' ? 'current-password' : 'new-password') + '">' +
                              (hint ? '<p class="pf-sub">' + hint + '</p>' : '') + '</div>';
                      };
                      $.confirm({
                          title: 'Сменить пароль',
                          content: '<div class="form-group place-form" id="old_pass"><div class="pf-grid">' +
                                   f('old_pass', 'Текущий пароль') +
                                   f('new_pass', 'Новый пароль', 'Не короче 8 символов') +
                                   f('new_pass_two', 'Новый пароль ещё раз') +
                                   '</div><p class="pf-error" role="alert"></p></div>',
                          columnClass: 'col-md-4 offset-md-4 col-sm-8 offset-sm-2 place-dialog',
                          confirmButton: 'Сохранить',
                          cancelButton: 'Отмена',
                          onOpen: function () { setTimeout(function () { $('#np-old_pass').trigger('focus'); }, 50); },
                          confirm: function(){
                              var dlg = this, $f = $('#old_pass');
                              var v = function (n) { return $f.find('input[name="' + n + '"]').val() || ''; };
                              var fail = function (n, msg) {
                                  if (n) { $f.find('input[name="' + n + '"]').addClass('is-invalid').trigger('focus'); }
                                  var $e = $f.find('.pf-error'); $e.text(msg).removeClass('is-open'); void $e[0].offsetWidth; $e.addClass('is-open');
                                  return false;
                              };
                              $f.find('.form-control').removeClass('is-invalid');
                              if (!v('old_pass')) { return fail('old_pass', 'Введите текущий пароль.'); }
                              if (v('new_pass').length < 8 || v('new_pass').length > 72) { return fail('new_pass', 'Новый пароль — от 8 до 72 символов.'); }
                              if (v('new_pass') !== v('new_pass_two')) { return fail('new_pass_two', 'Пароли не совпадают.'); }
                              if (v('new_pass') === v('old_pass')) { return fail('new_pass', 'Новый пароль совпадает с текущим.'); }
                              var payload = { uid: uid, sid: sid, key: key, old_password: v('old_pass'), password: v('new_pass') };
                              $f.addClass('is-saving');
                              $('.jconfirm .buttons button').prop('disabled', true);
                              $.ajax({
                                  type: 'GET',
                                  url: 'api:///staff/newpassword/' + JSON.stringify(payload).replace(/%/g, '%25'),
                                  timeout: 6000,
                                  success: function (data) {
                                      if (!data || data.error) {
                                          if (data && data.error === 'wrong password') { fail('old_pass', 'Текущий пароль введён неверно.'); }
                                          else if (data && /8-72/.test(data.error)) { fail('new_pass', 'Новый пароль — от 8 до 72 символов.'); }
                                          else { fail(null, 'Не удалось сменить пароль. Попробуйте ещё раз.'); }
                                          return;
                                      }
                                      $f.html('<div class="pw-done"><svg viewBox="0 0 64 64" aria-hidden="true"><circle cx="32" cy="32" r="29"/><path d="M20 33.5l8 8 16-17"/></svg><b>Пароль изменён</b><span>В следующий раз входите с новым паролем.</span></div>');
                                      $('.jconfirm .buttons button').first().hide();
                                      $('.jconfirm .buttons button').last().text('Готово');
                                  },
                                  error: function () { fail(null, 'Нет связи с сервером. Попробуйте ещё раз.'); },
                                  complete: function () { $f.removeClass('is-saving'); $('.jconfirm .buttons button').prop('disabled', false); }
                              });
                              return false;
                          }
                      });
                  });
                  $(document).on('input', '#old_pass .form-control', function () {
                      $(this).removeClass('is-invalid');
                      $('#old_pass .pf-error').removeClass('is-open');
                  });


                  $('.oplata_btn.btn-default').on('click', function(){
                                                  
                                                  $.dialog({
                                                            title: 'Оплата',
                                    content: '<div id="oplata" class="pay-sheet"><div class="pay-total"><small class="pay-label">К оплате</small><div class="pay-amount"><span class="sum">0</span><span class="pay-cur">₽</span></div></div><ul class="nav nav-tabs pay-switch" id="pay" role="tablist"><li class="active"><a class="active" href="#card" role="tab" data-toggle="tab"><i class="glyphicon glyphicon-credit-card"></i>Карта</a></li><li><a href="#cash" role="tab" data-toggle="tab"><i class="glyphicon glyphicon-usd"></i>Наличные</a></li></ul><div class="tab-content pay-panes"><div class="tab-pane active" id="card"><p class="pay-hint">Проведите оплату на терминале, затем подтвердите её здесь.</p></div><div class="tab-pane" id="cash"><div class="pay-row"><label class="pay-label" for="vneseno">Получено от гостя</label><button type="button" class="btn btn-link cash_quick cash_exact" data-v="exact">Без сдачи</button></div><div class="pay-input"><input type="text" inputmode="numeric" id="vneseno" name="vneseno" class="form-control vneseno" placeholder="0" autocomplete="off"><i>₽</i></div><div class="pay-quick"><button type="button" class="btn cash_quick" data-v="500">500</button><button type="button" class="btn cash_quick" data-v="1000">1000</button><button type="button" class="btn cash_quick" data-v="2000">2000</button><button type="button" class="btn cash_quick" data-v="5000">5000</button></div><div id="odd" class="pay-change"><small class="pay-label">Сдача</small><b><span>0</span> ₽</b></div></div></div><button type="button" class="btn btn-primary oplata_btn btn_big modal-trigger">Провести оплату</button></div>',
                                                            columnClass: 'col-md-4 offset-md-4 col-sm-8 offset-sm-2 pay-dialog',
                                                            confirmButton: 'Оплатить',

                                                            confirm: function(){
                                                            $.alert('Оплачено!');
                                                            }
                                                           });
                                                  var summ = $('.total_summ span.sum').text();
                                                  $('.sum').empty().append(summ);
                                                  
                    });
                  
                  var activeCats = {};
                  function load_menu(){
                  $.ajax({
                         type: 'GET',
                         url: 'api:///category/load/{"uid":"'+uid+'","sid":"'+sid+'","key":"'+key+'"}',
                         success: function(data){
                         $(".filter_items").empty().append('<button type="button" show="all" class="btn btn-primary active">Все</button>');
                         var menus = data;
                         //console.log(menus);
                         activeCats = {};
                         $.each(menus, function (i,item){
                                // в кассе показываем только активные категории (флажок «Показывать в кассе»)
                                if(item.status != 'on'){ return true; }
                                activeCats[item.id] = true;
                                $(".filter_items").append('<button type="button" show='+item.id+' class="btn btn-primary" index="'+i+'"><span>'+item.name+'</span></button>');
                          });
                        // $('.filter_items').append('<a type="button" class="btn btn-default refresh_menu"><i class="glyphicon glyphicon-refresh"></i></a>');
                       
                         load_items("all");
                         }
                         });
      
                  return true;
                  
                  }
                  
                  
                  $('body').on('click','.refresh_menu', function(){
                    load_menu();
                    //load_items("all");
                  });

                  
                  
                  
                  ////    функция загрузки items
                  function load_items(type){
                  $.ajax({
                         type: 'GET',
                         url: 'api:///items/load/{"uid":"'+uid+'","sid":"'+sid+'","key":"'+key+'","category":"'+type+'"}',
                         success: function(data){
                         $("#dash").empty();
                         var items = (typeof data === 'string') ? JSON.parse(data) : data;
                         console.log("items:", data);
                         $.each(items, function (i,item){
                                // товары из скрытых категорий в кассе не показываем (режим «Все»)
                                if(!activeCats[item.type]){ return true; }

                                if(item.em == '1'){
                                item.em = 'Мл.';
                                }
                                if(item.em == '2'){
                                item.em = 'Гр.';
                                }
                                
                                if(item.em == '3'){
                                item.em = 'Шт.';
                                }
                                
                               // item[3]
                                var indx = $('.filter_items').find('button[show="'+item.type+'"]').attr('index');
                                console.log(indx);
                                $("#dash").append("<div item_id="+item.id+" class='item col-sm-4 col-md-2 col-12 col-md-2-items item_color_"+indx+" type_"+item.type+"'><div class='kolvo'>1</div><span>"+item.name+"</span><i style='float: right;'>"+item.mass+" "+item.em+"</i><div class='dell'><i class='glyphicon glyphicon-remove'></i></div><small><b>"+item.price+"</b> руб</small></div>");
                                });
                         }
                         });
                  return true;
                  }

                  ///////клик
                  $('body').on('click', '.item', function(){
                               if(window.guest_id!==undefined){
                               var item_id = $(this).attr('item_id');
                               var price = $(this).find('small b').text();
                               var name = $(this).find('span').text();
                               ////////выделенный item
                               $(this).addClass('active');
                               $(this).find('.kolvo').empty().append(tap_item(item_id,price,name));
                               }else{
                               billNudge();
                               }
                               return false;
                               });
                  
                  
                  //////удалить item
                  $('body').on('click', '.dell', function(){
                               var item_id = $(this).parent().attr('item_id');
                               $(this).parent('.item').find('.kolvo').empty().append(dell_from_check(item_id));
                               $(this).parent('.item').removeClass('active');
                               return false;
                               });
                  
                  $('body').on('click','.filter_items button',function(){
                               var type = $(this).attr('show');
                               if(show_items(type)==true){
                               $(this).parent().find('button.active').removeClass('active');
                               $(this).addClass('active');
                               }
                               });
                  
                  // ── Закрытие смены: подтверждение → закрытие → отчёт ──────────────────────
                  function shiftEsc(v) { return $('<div>').text(v == null ? '' : String(v)).html(); }
                  function shiftMoney(v) {
                      var n = Math.round(Number(v) || 0);
                      return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ₽';
                  }
                  function shiftPlural(n, one, few, many) {
                      var m10 = n % 10, m100 = n % 100;
                      if (m10 === 1 && m100 !== 11) { return one; }
                      if (m10 >= 2 && m10 <= 4 && (m100 < 12 || m100 > 14)) { return few; }
                      return many;
                  }
                  function shiftTime() { return shiftTimer.elapsed(); }
                  function shiftDuration(t) {
                      if (!t.h && !t.m) { return 'меньше минуты'; }
                      return (t.h ? t.h + ' ч ' : '') + t.m + ' мин';
                  }

                  $('body').on('click', '.shift_end a', function(e){
                      e.preventDefault();
                      $('.usertop .dropdown-menu').removeClass('show').closest('.dropdown').removeClass('show open');
                      var t = shiftTime();
                      var dlg = $.confirm({
                          title: 'Закрыть смену?',
                          columnClass: 'col-md-4 offset-md-4 col-sm-8 offset-sm-2 shift-close-dialog',
                          confirmButton: 'Закрыть смену',
                          cancelButton: 'Продолжить работу',
                          content: '<div class="shift-confirm">' +
                                     '<p>Смена длится <b>' + shiftDuration(t) + '</b>. После закрытия вы увидите итоги по кассе, а продолжить продажи можно будет только в новой смене.</p>' +
                                     '<div class="shift-open-bills" hidden></div>' +
                                   '</div>',
                          confirm: function(){ closeShift(); }
                      });
                      // предупреждение об открытых счетах
                      $.ajax({
                          type: 'GET',
                          url: 'api:///guests/load/{"uid":"'+uid+'","sid":"'+sid+'","key":"'+key+'","place":"'+place+'"}',
                          success: function(data){
                              var list = (typeof data === 'string') ? JSON.parse(data) : data;
                              if (!$.isArray(list)) { return; }
                              var open = $.grep(list, function (g) { return Number(g.price) > 0; });
                              if (!open.length) { return; }
                              var sum = 0; $.each(open, function (i, g) { sum += Number(g.price) || 0; });
                              $('.shift-open-bills').html('<b>' + open.length + ' ' + shiftPlural(open.length, 'счёт не оплачен', 'счёта не оплачены', 'счетов не оплачены') + '</b> на ' + shiftMoney(sum) + '. Они останутся открытыми и попадут в следующую смену.').prop('hidden', false);
                          }
                      });
                      return false;
                  });

                  function shiftScreen(inner) {
                      var $s = $('#shift-screen');
                      if (!$s.length) { $s = $('<div id="shift-screen" class="shift-screen" role="dialog" aria-modal="true"></div>').appendTo('body'); }
                      $('body').addClass('shift-closing');
                      $s.html('<div class="shift-screen-card">' + inner + '</div>');
                      return $s;
                  }

                  function closeShift(){
                      var t = shiftTime();
                      var placeName = $.trim($('.usertop .place em').first().text());
                      var cashier = $.trim($('.name_avatar').first().text());
                      shiftScreen('<div class="shift-loading"><span class="shift-spin" aria-hidden="true"></span><h2>Закрываем смену…</h2><p>Считаем выручку по кассе. Не закрывайте страницу.</p></div>');

                      $.ajax({
                          type: 'GET',
                          url: 'api:///shift/{"shift":"'+(window.shift || 0)+'","uid":"'+uid+'","sid":"'+sid+'","key":"'+key+'","place":"'+place+'","hours":'+t.h+',"min":'+t.m+',"sec":'+t.s+'}',
                          timeout: 15000,
                          success: function(data){
                              data = (typeof data === 'string') ? JSON.parse(data) : data;
                              if (!data || data.error) { shiftError(data && data.error); return; }
                              shiftTimer.stop();
                              window.shift = null;

                              var cash = 0, card = 0;
                              $.each(data, function (method, sales) {
                                  if (Number(method) === 1) { cash = Number(sales.price) || 0; }
                                  else if (Number(method) === 2) { card = Number(sales.price) || 0; }
                              });
                              var total = cash + card;
                              var cashPct = total ? Math.round(cash / total * 100) : 0;
                              var cardPct = total ? 100 - cashPct : 0;
                              var now = new Date();
                              var months = ['января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабря'];
                              var pad = function (n) { return (n < 10 ? '0' : '') + n; };
                              var when = now.getDate() + ' ' + months[now.getMonth()] + ', ' + pad(now.getHours()) + ':' + pad(now.getMinutes());

                              shiftScreen(
                                  '<div class="shift-report">' +
                                    '<div class="shift-report-head">' +
                                      '<svg class="shift-done" viewBox="0 0 64 64" aria-hidden="true"><circle cx="32" cy="32" r="29"/><path d="M20 33.5l8 8 16-17"/></svg>' +
                                      '<div><h2>Смена закрыта</h2><p>' + shiftEsc(placeName || 'Кофейня') + (cashier ? ' — ' + shiftEsc(cashier) : '') + '</p></div>' +
                                    '</div>' +
                                    '<dl class="shift-meta">' +
                                      '<div><dt>Закрыта</dt><dd>' + when + '</dd></div>' +
                                      '<div><dt>Длительность</dt><dd>' + shiftDuration(t) + '</dd></div>' +
                                    '</dl>' +
                                    '<div class="shift-total"><span>Выручка за смену</span><b class="shift-total-sum">' + shiftMoney(total) + '</b></div>' +
                                    '<div class="shift-split" role="img" aria-label="Наличные ' + cashPct + '%, карта ' + cardPct + '%">' +
                                      '<i class="is-cash" style="--w:' + cashPct + '%"></i><i class="is-card" style="--w:' + cardPct + '%"></i>' +
                                    '</div>' +
                                    '<ul class="shift-lines">' +
                                      '<li><span class="dot is-cash"></span><span>Наличные</span><small>' + cashPct + '%</small><b>' + shiftMoney(cash) + '</b></li>' +
                                      '<li><span class="dot is-card"></span><span>Карта</span><small>' + cardPct + '%</small><b>' + shiftMoney(card) + '</b></li>' +
                                    '</ul>' +
                                    (total ? '<p class="shift-note">Сверьте деньги в кассе: там должно быть ' + shiftMoney(cash) + ' наличными сверх размена.</p>' : '<p class="shift-note">За смену не было оплат.</p>') +
                                    '<a class="btn btn-primary btn-block shift-exit" href="' + BASE_URL + '/?do=exit">Выйти из аккаунта</a>' +
                                  '</div>'
                              );
                          },
                          error: function(){ shiftError(); }
                      });
                  }

                  function shiftError(code){
                      shiftScreen('<div class="shift-loading is-error"><span class="shift-err" aria-hidden="true">!</span><h2>Смена не закрыта</h2><p>' +
                          (code ? 'Сервер ответил ошибкой: ' + shiftEsc(code) + '.' : 'Нет связи с сервером.') +
                          ' Продажи сохранены — попробуйте ещё раз.</p>' +
                          '<div class="shift-actions"><button type="button" class="btn btn-primary shift-retry">Попробовать снова</button><button type="button" class="btn btn-default shift-back">Вернуться в кассу</button></div></div>');
                  }
                  $('body').on('click', '.shift-retry', function(){ closeShift(); });
                  $('body').on('click', '.shift-back', function(){ $('#shift-screen').remove(); $('body').removeClass('shift-closing'); });


                  ///// функция тапа по item
                  function tap_item(item_id,price,name){
                  if($('.zakaz li[item_id='+item_id+']').length){
                  if($('.zakaz li[item_id='+item_id+']').find("b").length){
                  var amt = $('.zakaz li[item_id='+item_id+']').find("b").text();
                  var amt = parseInt(amt) + 1;
                  $('.zakaz li[item_id='+item_id+']').find("b").empty().append(amt);
                  $('.zakaz li[item_id='+item_id+']').find('strong').empty().append(price*amt);
                  }else{
                  var amt = 2;
                  $('.zakaz li[item_id='+item_id+']').append("<b>2</b>");
                  $('.zakaz li[item_id='+item_id+']').find('strong').empty().append(price*amt);
                  }
                  }else{
                  var amt = 1;
                  ///

                  if(name.length > 19){
                  name = name.slice(0, 15) + '...';
                  }
                  ///
                  $("ul.zakaz").append("<li item_id="+item_id+"><span>"+name+"</span><strong class='pull-right'>"+price+"</strong></li>");
                  }
                  
                  total_sum();
                  return amt;
                  }
                  //////итого в чеке
                  function total_sum(){
                  var sum = 0;
                  var item_price = $("ul.zakaz li").find('strong.pull-right');
                  i=item_price.length;
                  while(i--){
                  sum+=+item_price[i].innerHTML;
                  }
                  $('.sum').empty().append(sum);
                  get_check();
                  }
                  function dell_from_check(item_id){
                  $('li[item_id='+item_id+']').remove();
                  total_sum();
                  return 1;
                  }
                  
                  
                  function get_check() {
                  var items_in_ul = $(".zakaz li").length;
                  if(items_in_ul > 6){
                  $('.zakaz').addClass('withscroll');
                  }else{
                  
                  $('.zakaz').removeClass('withscroll');
                  }
                  }
                  
                  function show_items(type){
                  if(type=='all'){
                  $('.item').css('display','block');
                  }else{
                  $('.item').css('display','none');
                  $('.item.type_'+type).css('display','block');
                  }
                  return true;
                  }
                  
                  // Быстрые купюры: подставляют сумму в «Получено от гостя» и пересчитывают сдачу
                  $("body").on('click', '.cash_quick', function(){
                                var v = $(this).data('v');
                                if (v === 'exact') { v = parseInt($('.sum:first').text(), 10) || 0; }
                                $('.cash_quick').removeClass('is-picked');
                                $(this).addClass('is-picked');
                                $('input.vneseno').val(v).trigger('keyup');
                  });
                  $("body").on('input', '.vneseno', function(){ $('.cash_quick').removeClass('is-picked'); });
                  $("body").on('shown.bs.tab', '#pay a[href="#cash"]', function(){ $('input.vneseno').trigger('focus'); });

                  $( "body" ).on('keyup keypress','.vneseno', function(e){
                                          setTimeout(function(){
                                                     var payed = $('.sum:first').text();
                                                     var cupura = $('input.vneseno').val();
                                                     if (parseInt(cupura) > parseInt(payed)){
                                                     var odd = parseInt(cupura)-parseInt(payed);
                                                     
                                                     }else{
                                                     var odd = 0;
                                                     }
                                                     $('#odd').find('span').empty().append(odd);
                                                     },300);
                                     
                                          });

                  
                  $('body').on('click','.oplata_btn.btn_big', function(){
					  if(window.guest_id!==undefined){
                                              $(this).prop( "disabled", true );
                               // ПЕЧАТЬ ЧЕКОВ ВРЕМЕННО ОТКЛЮЧЕНА — чтобы вернуть, раскомментируйте блок ниже (и блоки в окне «Начало смены»)
                               // if($.cookie('checkswitch') == 'on'){
                               // var list_zakaz = $('ul.zakaz').clone();
                               // var total_check = $('.total_summ .sum').clone();
                               // $('.list_zakaz_check').empty().append(list_zakaz);
                               // $('.itogo_check').empty().append(total_check);
                                              // window.print();
                               // }
                                              payed = order.collectForPayment(Number(window.guest_id));
                               var totaly_sum = $('.total_summ .sum').text();
                          console.log( 'api:///buy/check/{"uid":"'+uid+'","sid":"'+sid+'","key":"'+key+'","place":"'+place+'","payed":'+JSON.stringify(payed)+',"total":'+totaly_sum+',"shift":'+window.shift+'}');
                                              $.ajax({
                                                     type: 'GET',
                                                     url: 'api:///buy/check/{"uid":"'+uid+'","sid":"'+sid+'","key":"'+key+'","place":"'+place+'","payed":'+JSON.stringify(payed)+',"total":'+totaly_sum+',"shift":'+window.shift+'}',
                                                     success: function(data){
                                                     console.log(JSON.stringify(data));
                                                     //active_guest(window.guest_id);
                                                     $('.item.active').find('.kolvo').empty().append(1);
                                                     $('.item.active').removeClass('active')
                                                     
                                                     $(".total_summ .sum").empty().append('0');
                                                     $(".zakaz").empty();
                                                     billSetOpen(false);
                                                     delete window.guest_id;
                                                     
                                                     $('.jconfirm.jconfirm-white').remove();
                                                     $('.oplata_btn.btn_big').prop( "disabled", false );
                                                     //$('#oplata').modal('hide');
                                                     }
                                                     });
					  }

                                                });
  
                  function toaster(msg){
                  if(msg == "invalid token"){
                  window.location.href = BASE_URL + "/?do=exit";

                  }else if(typeof(msg) == 'undefined'){
                  $.alert({
                          title: 'Ошибка!',
                          content: "Не удлось получить ответ от сервера",
                          autoClose: 'cancel|1000',
                          theme: 'black',
                          confirmButton: 'Закрыть'
                          
                          
                          });
                  }else{
                  $.alert({
                          title: 'Ошибка!',
                          content: msg,
                          autoClose: 'cancel|1000',
                          theme: 'black',
                          confirmButton: 'Закрыть'
                          });
                  }
                  return false;
                  }
                  
                  });
$(document).ready(function($) {
                  
                  $(window).load(function() {
                                 $(".loader-item").delay(700).fadeOut();
                                 $("#pageloader").delay(800).fadeOut("slow");
                                 });
                  
                  //$('.datepicker').pickadate({
                  //                           selectMonths: true, // Creates a dropdown to control month
                  //                           selectYears: 15 // Creates a dropdown of 15 years to control year
                  //                           });
                  
                  
                  
                  
                  $(document).bind("contextmenu",function(e){
                                   //return false;
                                   });
                  
                  
                  
                  
                  $('.fullscreen_btn').click(function(){
                                             toggleFullScreen();
                                             $(this).toggleClass('active');
                                             });
                  
                  
                  function toggleFullScreen() {
                  if (!document.fullscreenElement && !document.mozFullScreenElement && !document.webkitFullscreenElement && !document.msFullscreenElement ) {
                  if (document.documentElement.requestFullscreen) {
                  document.documentElement.requestFullscreen();
                  } else if (document.documentElement.msRequestFullscreen) {
                  document.documentElement.msRequestFullscreen();
                  } else if (document.documentElement.mozRequestFullScreen) {
                  document.documentElement.mozRequestFullScreen();
                  } else if (document.documentElement.webkitRequestFullscreen) {
                  document.documentElement.webkitRequestFullscreen(Element.ALLOW_KEYBOARD_INPUT);
                  }
                  } else {
                  if (document.exitFullscreen) {
                  document.exitFullscreen();
                  } else if (document.msExitFullscreen) {
                  document.msExitFullscreen();
                  } else if (document.mozCancelFullScreen) {
                  document.mozCancelFullScreen();
                  } else if (document.webkitExitFullscreen) {
                  document.webkitExitFullscreen();
                  }
                  }
                  }
                  
                  
                  $(".new_phone").keydown(function(event) {
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
                  
                  $('body').on('click','.total_summ .maximize', function(){
                               $(this).removeClass('maximize').addClass('minimize');
                               $(this).parent().addClass('fixed');
                               $('#body').append($('.total_summ'));
                               });
                  
                  $('body').on('click','.total_summ .minimize',function(){
                               $(this).removeClass('minimize').addClass('maximize');
                               $(this).parent().removeClass('fixed');
                               $('ul.zakaz').css('height','auto');
                               $('.left_bar').append($('.total_summ'));
                               $('.total_summ .not_short').addClass('short').removeClass('not_short');
                               });
                  
                  $('body').on('click','.short',function(){
                               $('ul.zakaz').css('height','1px');
                               $(this).addClass('not_short').removeClass('short');
                               });
                  
                  $('body').on('click','.not_short',function(){
                               $('ul.zakaz').css('height','auto');
                               $(this).addClass('short').removeClass('not_short');
                               });
                  
                  
                  window.onbeforeunload = function() {
                  return 'Мы не рекомендуем Вам обновлять, либо покидать страницу! Вы можете потерять не сохраненные данные!';
                  };
                  
                  
                  $(".vneseno").keydown(function(event) {
                                        if ( event.keyCode == 46 || event.keyCode == 8 || event.keyCode == 9 || event.keyCode == 27 || (event.keyCode == 65 && event.ctrlKey === true) ||
                                            (event.keyCode >= 35 && event.keyCode <= 39)) {
                                        return;
                                        }else {
                                        if ((event.keyCode < 48 || event.keyCode > 57) && (event.keyCode < 96 || event.keyCode > 105 )) {
                                        event.preventDefault();
                                        }
                                        }
                                        });
                  
                  if($.cookie('locked') !== undefined ){
                  im_out();
                  }
                  
                  $('li.im_out').on("click",function(){
                                    $.cookie('locked', 1);
                                    im_out();
                                    });
                  
                  function im_out(){
                  $('#body').addClass('blur');
                  $('body').append('<div class="darkness"><div class="lock_key"><form><input type="password" class="form-control" id="pass_lock" placeholder="Пароль"></form></div></div>');
                  }
                  
                  
                  $("body").on('submit','.lock_key form',function(){
                var pass = $("#pass_lock").val();
                ///
                               $.ajax({
                                      type: 'GET',
                                      url: 'http://mokkoapi.azurewebsites.net/user/unlock/' + JSON.stringify({uid: uid, key: key, sid: sid, password: pass}).replace(/%/g, '%25'),
                                      success: function(data){
                                      console.log(data.unlock);
                                      if(data.unlock == "true"){
                                      $('.darkness').remove();
                                      $.removeCookie('locked')
                                      }else{
                                      $( ".lock_key form" ).effect("shake");

                                      }
                                     
                                      }

                                });
                ///
                               return false;
                               });
                  
/*
                  $('.darkness').on("click",function(){
                                      $(this).remove();
                                      $('#body').removeClass('blur');
                                      //alert('hg');
                                      });
                  */
                  
                  $('.modal-content').on("click",function(){
                                           return false;
                                           });
                  
                  $('#oplata').on("click",function(){
                                    $('#body').removeClass('blur');
                                    });
                  $('#oplata .close').on("click",function(){
                                           $('#body').removeClass('blur');
                                           });
                  
                  $('.lock_key').on("click",function(){
                                      return false;
                                      });
                  
                  
                  
                  
                  $('.menu_left a').on("click",function(){
                                       $('.layer').css('display','none');
                                       var layer = $(this).attr('href');
                                       $(layer).css('display','block');
                                       $('.menu_left').find('a.active').removeClass('active');
                                       $(this).delay(200).queue(function(){
                                                                $(this).addClass('active').dequeue();
                                                                });
                                       });
                  
                  $('.for_mobile a').on("click",function(){
                                        $('.layer').css('display','none');
                                        var layer = $(this).attr('href');
                                        $(layer).css('display','block');
                                        $('.menu_left').find('a.active').removeClass('active');
                                        $(this).delay(200).queue(function(){
                                                                 $(this).addClass('active').dequeue();
                                                                 });

                                        });
                  
                  
                  
                  //    $('.menu_left a').mawbutton({
                  //            speed : 200,
                  //            scale : 15,
                  //            effect : "ripple",
                  //            transitionEnd:function(){
                  //            }
                  //       });
                  });

(function($){
 
	$.fn.mawbutton = function (options) {
 var settings = $.extend({
                         effect : "ripple",
                         scale : 3,
                         speed : 300,					// ms
                         transitionEnd : function(){}	// callback when transition ends.
                         }, options);
 
 return this.each(function() {
                  
                  var $this = $(this);
                  var supportEvent = ('ontouchstart' in window ) ? 'touchstart':'click';
                  
                  $this.addClass('mawbutton')
                  .on(supportEvent, function(e) {		//bind touch/click event
                      e.preventDefault();
                      $this.append('<div class="mawbutton-'+settings.effect+'" ></div>');
                      
                      // Fetch click position and size
                      var posX = $this.offset().left,
                      posY = $this.offset().top;
                      
                      var w = $this.width(),
                      h = $this.height();
                      var targetX= e.pageX - posX;
                      var targetY= e.pageY - posY;
                      
                      //Fix target position
                      if(!targetX || !targetY){
                      targetX = e.originalEvent.touches[0].pageX;
                      targetY = e.originalEvent.touches[0].pageY;
                      }
                      
                      var ratio = settings.scale / 2;    							
                      
                      //Animate Start
                      $effectElem = $this.children(':last');
                      $effectElem.addClass("mawbutton-stop")
                      .css({
                           "top" : targetY,
                           "left" : targetX,
                           "width" : h * settings.scale,
                           "height" : h * settings.scale,
                           "margin-left" : -h * ratio,
                           "margin-top" : -h * ratio,
                           "transition-duration" : settings.speed+"ms",
                           "-webkit-transition-duration" : settings.speed+"ms",
                           "-moz-transition-duration" : settings.speed+"ms",
                           "-o-transition-duration" : settings.speed+"ms"
                           });
                      $effectElem.removeClass("mawbutton-stop");
                      //Animate End
                      setTimeout(function(){
                                 $effectElem.addClass("mawbutton-"+settings.effect+"-out").css({
                                                                                               "transition-duration" : settings.speed+"ms",
                                                                                               "-webkit-transition-duration" : settings.speed+"ms",
                                                                                               "-moz-transition-duration" : settings.speed+"ms",
                                                                                               "-o-transition-duration" : settings.speed+"ms"
                                                                                               });
                                 setTimeout(function(){
                                            $this.find(".mawbutton-"+settings.effect).first().remove();
                                            $('.menu_left').find('a.active').removeClass('active');
                                            $this.addClass('active');
                                            settings.transitionEnd.call(this);
                                            },settings.speed);
                                 }, settings.speed);
                      });
                  });
	}
 
 }(jQuery));
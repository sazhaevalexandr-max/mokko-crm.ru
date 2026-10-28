
$(document).ready(function($) {

    $(".repeir").click(function(e) {
        var href = $(this).attr('href');
        // Новая разметка ведёт на страницу восстановления — не мешаем переходу.
        if (href && href !== '#') { return; }
        e.preventDefault();
        $('input[name="password"]').remove();
        $('.enter').val('Восстановить').addClass('get_pass').removeClass('enter');
    });

    // Отправка формы по Enter (кнопка не submit, чтобы не перезагружать страницу).
    $('#enter').on('keydown', 'input', function(e) {
        if (e.key === 'Enter' || e.keyCode === 13) {
            e.preventDefault();
            $('.enter').trigger('click');
        }
    });

    $(".menulink a").click(function() {
        $("nav").stop(true, true).fadeToggle('fast');
    });

    resize_intro();
    $(".left section").stop(true, true).fadeIn('fast');
    $(".right section").stop(true, true).fadeIn('fast');

    $('body').on('click', ".get_pass", function() {
        $('#enter').empty().append('<h5><center>Письмо с указаниями выслано Вам на почту!</center></h5>');
    });

    $("body").on('click', ".enter", function() {
        var msg = $("#enter").serializeArray();
        if (!msg || msg.length < 2) return;

        $(this).prop("disabled", true);
        $('.report').empty();

        $.ajax({
            type: 'POST',
            url: BASE_URL + '/api/auth.php?action=login',
            contentType: 'application/json',
            data: JSON.stringify({ login: msg[0].value, password: msg[1].value }),
            dataType: 'json',
            timeout: 20000,
            success: function(answer) {
                $(".enter").prop("disabled", false);

                if (answer.error) {
                    var text;
                    switch (answer.error) {
                        case 'limit':
                            text = 'Слишком много неудачных попыток войти.<br/><br/>';
                            $(".enter").val('Восстановить доступ');
                            $("input[name=password]").remove();
                            $("input[name=user]").css("margin-bottom", "44px").attr('name', 'remember');
                            break;
                        case 'invalid':
                            // Не раскрываем, что именно неверно (email или пароль).
                            text = 'Неверный логин или пароль<br/><br/>';
                            break;
                        case 'server':
                            text = 'Внутренняя ошибка сервера, попробуйте позже<br/><br/>';
                            break;
                        default:
                            text = 'Ошибка: ' + $('<div>').text(String(answer.error)).html() + '<br/><br/>';
                    }
                    $('.report').empty().append(text);
                    if ($.fn.effect) {
                        $(".right section").effect("shake");
                    }
                } else {
                    // Сессия уже установлена сервером — просто переходим
                    var target = (answer.sid === 'uid')
                        ? BASE_URL + '/panel'
                        : BASE_URL + '/enter';
                    window.location.href = target;
                }
            },
            error: function(jqXHR, exception) {
                $(".enter").prop("disabled", false);
                var text;
                if (jqXHR.status === 0)           text = 'Нет соединения с сервером<br/><br/>';
                else if (exception === 'timeout')  text = 'Превышено время ожидания<br/><br/>';
                else if (jqXHR.status === 429) {
                    var retry = (jqXHR.responseJSON && jqXHR.responseJSON.retry_after) ? parseInt(jqXHR.responseJSON.retry_after, 10) : 0;
                    text = 'Слишком много попыток входа.<br/>' + (retry > 0
                        ? 'Повторите через ' + Math.max(1, Math.ceil(retry / 60)) + ' мин.<br/><br/>'
                        : 'Попробуйте позже.<br/><br/>');
                }
                else if (jqXHR.status === 500)     text = 'Ошибка сервера (500)<br/><br/>';
                else                               text = 'Ошибка: ' + jqXHR.status + ' ' + jqXHR.responseText;
                $('.report').empty().append(text);
            }
        });
    });

});

$(window).resize(function() {
    resize_intro();
});

function resize_intro() {
    var page_height  = $(window).height();
    var right_height = $('.right section').height() + 40;
    var left_height  = $('.left section').height() + 40;

    if (left_height < page_height) {
        $(".left").css('height', page_height + 'px');
        $(".left section").css('margin-top', (((page_height - left_height) / 2) - 30) + 'px');
    }

    if (right_height < page_height && $(window).width() > 800) {
        $(".right section").css('margin-top', ((page_height - right_height) / 2) + 'px');
    }
}

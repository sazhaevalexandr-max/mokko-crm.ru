$(document).ready(function($) {

    $('.edit_this_item').hide();
    $('.layer').hide();
    $('.layer.active').show();
    $('.darkness').hide();
    $('.top_alert').hide();
    $('.startup').hide();

    // Загружаем настройки аккаунта
    $.ajax({
        type: 'GET',
        url: BASE_URL + '/api/places.php?action=ini&uid=' + uid + '&key=' + key + '&sid=' + sid,
        dataType: 'json',
        timeout: 5000,
        success: function(answer) {
            if (answer['error']) {
                window.location.href = BASE_URL + '/?do=exit';
                return;
            }

            $('#pageloader').hide();
            var status = answer.settings ? answer.settings.status : 'active';

            switch (status) {
                case 'new':
                    $('.top_alert').show().addClass('alert-info').empty()
                        .append('<center>У Вас нет активных подписок на сервисы</center>');
                    $('.startup').show();
                    break;
                case 'active':
                    $('.startup').remove();
                    break;
                case 'disable':
                    $('.startup').remove();
                    $('.top_alert').show().addClass('alert-danger').empty()
                        .append('<center>Аккаунт деактивирован</center>');
                    break;
                case 'low_wallet':
                    $('.startup').remove();
                    $('.top_alert').show().addClass('alert-warning').empty()
                        .append('<center>Срок подписки заканчивается, пополните счёт во избежание блокировки</center>');
                    break;
            }

            // Загружаем список кофеен
            $.ajax({
                type: 'GET',
                url: BASE_URL + '/api/places.php?action=load&uid=' + uid + '&key=' + key + '&sid=' + sid,
                dataType: 'json',
                timeout: 5000,
                success: function(data_arr) {
                    $('#places .form-control').empty()
                        .append('<option disabled selected>Выберите кофейню</option>');
                    // Бариста с одной кофейней сразу попадает в кассу
                    if (sid === 'staff' && data_arr && data_arr.length === 1) {
                        window.location.href = BASE_URL + '/enter?place=' + encodeURIComponent(data_arr[0].id);
                        return;
                    }
                    $.each(data_arr, function(i, place) {
                        $('#places .form-control')
                            .append('<option value="' + place.id + '">' + place.name + '</option>');
                    });
                    $('#pageloader').hide();
                }
            });
        },
        error: function() {
            $('#pageloader').hide();
            $('.top_alert').show().addClass('alert-danger').empty()
                .append('<center>Не удалось загрузить данные. Проверьте подключение.</center>');
        }
    });

    $('body').on('click change', 'select', function() {
        $('button').attr('disabled', false).removeClass('disabled');
        return true;
    });

    $('body').on('click', 'button', function() {
        var place = $('select[name="place"]').val();
        if (place !== null) {
            $('.progress-bar').css('display', 'block');
            window.location.href = BASE_URL + '/enter?place=' + place;
        }
    });

});

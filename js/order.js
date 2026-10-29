// Модуль текущего заказа кассы.
// Сборка позиций из DOM (.zakaz li) в формат payed[] раньше была продублирована
// в трёх обработчиках (редактирование гостя, сохранение, оплата) через глобальный
// скретч `check`. Теперь она здесь — классический модуль (window.order), первый шаг
// к ES-модулю. Глобальная переменная `check` больше не нужна.
//
// ВАЖНО: два метода намеренно РАЗНЫЕ. Исходные циклы отличались на денежном пути:
//   • collect()           — как в guests/edit: item и qulity приводятся к Number ДО проверки;
//   • collectForPayment()  — как в buy/check: qulity остаётся строкой в проверке, добавляются
//                            метод оплаты и цена.
// Поведение каждого сохранено байт-в-байт (проверено дифференциальным тестом).
var order = (function () {
    'use strict';

    // Метод оплаты из активной вкладки #pay: cash→1, card→2, иначе исходная строка.
    function payMethod() {
        var pay_method = $('#pay').find('.active a').attr('href');
        pay_method = pay_method.replace("#", "");
        if (pay_method == "cash") {
            pay_method = 1;
        } else if (pay_method == "card") {
            pay_method = 2;
        }
        return pay_method;
    }

    return {
        // Позиции для сохранения гостя (guests/edit): [[item, qulity, uid], ...].
        // Точная копия циклов add_guest_tap() / active_guest().
        collect: function (guestId) {
            var payed = [];
            var bays = $('.zakaz').find('li');
            $.each(bays, function (i, el) {
                var id_item = Number($(el).attr('item_id'));
                var qulity = Number($(el).find('b').text());
                if (qulity == '') {
                    qulity = 1;
                }
                var row = [];
                row.push(Number(id_item), Number(qulity), guestId);
                payed.push(row);
            });
            return payed;
        },

        // Позиции для оплаты (buy/check): [[item, qulity, uid, method, price], ...].
        // Точная копия цикла обработчика .oplata_btn.btn_big.
        collectForPayment: function (guestId) {
            var payed = [];
            var bays = $('.zakaz').find('li');
            var method = payMethod();
            $.each(bays, function (i, el) {
                var id_item = $(el).attr('item_id');
                var qulity = $(el).find('b').text();
                var price = $(el).find('strong').text();
                if (qulity == '') {
                    qulity = 1;
                }
                var row = [];
                row.push(Number(id_item), Number(qulity), guestId, method, price);
                payed.push(row);
            });
            return payed;
        }
    };
})();

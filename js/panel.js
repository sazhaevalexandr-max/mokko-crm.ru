
// ── Local API shim: redirect all mokkoapi.azurewebsites.net calls to /api/crm.php ──
if (typeof BASE_URL === 'undefined') { var BASE_URL = ''; }

$.ajaxPrefilter(function(options) {
    var url = options.url || '';
    if (url.indexOf('mokkoapi.azurewebsites.net') < 0) return;
    var path = url.replace(/^https?:\/\/mokkoapi\.azurewebsites\.net\//, '');
    var jsonStart = path.indexOf('{');
    var pathPart  = jsonStart >= 0 ? path.substring(0, jsonStart) : path;
    var jsonPart  = jsonStart >= 0 ? path.substring(jsonStart)    : '{}';
    pathPart = pathPart.replace(/\/$/, '');
    var parts  = pathPart.split('/').filter(function(x){ return x; });
    var module = parts[0] || '';
    var action = parts[1] || 'get';
    var params;
    try { params = JSON.parse(decodeURIComponent(jsonPart)); } catch(e) { params = {}; }
    options.type        = 'POST';
    options.url         = BASE_URL + '/api/crm.php?module=' + encodeURIComponent(module) + '&action=' + encodeURIComponent(action);
    options.contentType = 'application/json';
    options.data        = JSON.stringify(params);
    options.dataType    = 'json';
});
// ── End shim ──────────────────────────────────────────────────────────────────


// ── Кофейни: карточка и форма (общие для списка, создания и правки) ─────────
function mkEsc(v) { return String(v == null || v === 'null' || v === 'undefined' ? '' : v).replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
function placeCard(place) {
    var name = mkEsc(place.name), addr = mkEsc(place.address), phone = mkEsc(formatPhone(place.phone));
    var letter = (String(place.name || '?').trim().charAt(0) || '?').toUpperCase();
    return '<div class="panel panel-default place-card" id_place="' + mkEsc(place.id) + '" address="' + addr + '" inn="' + mkEsc(place.inn) + '" kpp="' + mkEsc(place.kpp) + '" ltd="' + mkEsc(place.ltd) + '" phone="' + mkEsc(place.phone) + '">' +
        '<div class="place-card-main">' +
            '<span class="place-mark" aria-hidden="true">' + mkEsc(letter) + '</span>' +
            '<div class="place-info"><span class="place-name">' + name + '</span>' +
            '<small class="place-address' + (addr ? '' : ' is-empty') + '">' + (addr || 'Адрес не указан') + '</small></div>' +
        '</div>' +
        '<div class="place-facts">' +
            (phone ? '<span><i class="glyphicon glyphicon-earphone"></i>' + phone + '</span>' : '') +
            (place.inn && place.inn !== 'null' ? '<span>ИНН ' + mkEsc(place.inn) + '</span>' : '<span class="is-empty">Реквизиты не заполнены</span>') +
        '</div>' +
        '<div class="place-actions">' +
            '<a class="btn btn-primary enter_place" href="' + BASE_URL + '/?view=enter&place=' + encodeURIComponent(place.id) + '">Открыть кассу</a>' +
            '<a class="btn btn-default edit_place" href="#">Изменить</a>' +
        '</div>' +
    '</div>';
}
$(document).on('input', '.place-form .form-control', function () {
    $(this).removeClass('is-invalid');
    $(this).closest('.place-form').find('.pf-error').removeClass('is-open');
});

// ── Настройки: заполнение профиля, баланса и статуса ──────────────────────
function fillSettings(st) {
    st = st || {};
    var name = String(st.name || '').trim();
    $('.set_name').text(name || '—');
    $('.set-avatar').text((name.charAt(0) || '·').toUpperCase());
    $('.set_login').text(st.login || '—');
    setPhoneView(st.phone);
    $('.wallet_value').text(String(Math.round(Number(st.wallet) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, ' '));
    var statuses = {
        active: 'Подписка активна',
        low_wallet: 'Баланс заканчивается',
        disable: 'Аккаунт отключён',
        'new': 'Подписка не оформлена'
    };
    $('.set-status').attr('data-status', st.status || '').text(statuses[st.status] || 'Статус неизвестен');
    loadSettingsPlaces();
}
$(document).on('click', '.pay_btn', function (e) {
    e.preventDefault();
    $('.navigation a[href="#options"]').trigger('click');
});
function dialogError($scope, text) {
    var $e = $scope.find('.pf-error');
    $e.text(text).removeClass('is-open');
    void $e[0].offsetWidth;
    $e.addClass('is-open');
}
$(document).on('input', '.settings-form .form-control', function () {
    $(this).removeClass('is-invalid');
    $(this).closest('.settings-form').find('.pf-error').removeClass('is-open');
});

// ── Кофейни: проверка формы, сохранение и списки в «Кофейнях» и «Настройках» ──
function placeFormData($f) {
    var v = function (n) { return $.trim($f.find('input[name="' + n + '"]').val() || ''); };
    return { name: v('name'), address: v('address'), phone: v('phone'), ltd: v('ltd'),
             inn: v('inn').replace(/\s+/g, ''), kpp: v('kpp').replace(/\s+/g, '') };
}
function placeFormCheck($f, d) {
    var bad = function (n, msg) { $f.find('input[name="' + n + '"]').addClass('is-invalid').focus(); dialogError($f, msg); return false; };
    $f.find('.form-control').removeClass('is-invalid');
    if (!d.name) { return bad('name', 'Укажите название кофейни.'); }
    if (!d.address) { return bad('address', 'Укажите адрес — он печатается в чеке.'); }
    var digits = d.phone.replace(/\D/g, '');
    if (d.phone && (digits.length < 10 || digits.length > 15)) { return bad('phone', 'Телефон — от 10 до 15 цифр, например +7 999 123-45-67.'); }
    if (d.inn && !/^(\d{10}|\d{12})$/.test(d.inn)) { return bad('inn', 'ИНН — 10 цифр для организации или 12 для ИП.'); }
    if (d.kpp && !/^\d{9}$/.test(d.kpp)) { return bad('kpp', 'КПП — 9 цифр. У ИП КПП нет, оставьте поле пустым.'); }
    return true;
}
var placeServerErrors = {
    'invalid inn': ['inn', 'Сервер не принял ИНН: нужно 10 или 12 цифр.'],
    'invalid kpp': ['kpp', 'Сервер не принял КПП: нужно 9 цифр.'],
    'invalid phone': ['phone', 'Сервер не принял телефон: нужно 10–15 цифр.'],
    'name and address required': ['name', 'Укажите название и адрес.'],
    'not found': [null, 'Кофейня не найдена — обновите страницу.']
};
// Прослойка вверху файла ищет в адресе JSON по символу «{» и прогоняет его через
// decodeURIComponent — поэтому экранируем только «%», кавычки и пробелы JSON.stringify уже обработал.
function apiUrl(module, action, payload) {
    return 'http://mokkoapi.azurewebsites.net/' + module + '/' + action + '/' + JSON.stringify(payload).replace(/%/g, '%25');
}
function placeApiUrl(action, payload) { return apiUrl('places', action, payload); }
function savePlace(mode, dlg, $f, id) {
    var d = placeFormData($f);
    if (!placeFormCheck($f, d)) { return false; }
    var payload = $.extend({ uid: uid, sid: sid, key: key }, d);
    if (mode === 'edit') { payload.place = id; }
    $f.addClass('is-saving');
    $('.jconfirm .buttons button').prop('disabled', true);
    $.ajax({
        type: 'GET',
        url: placeApiUrl(mode, payload),
        timeout: 6000,
        success: function (data) {
            if (data && data.error) {
                var m = placeServerErrors[data.error] || [null, 'Не удалось сохранить: ' + data.error];
                if (m[0]) { $f.find('input[name="' + m[0] + '"]').addClass('is-invalid').focus(); }
                dialogError($f, m[1]);
                return;
            }
            var place = { id: data.id || data.Id || id, name: data.name, address: data.address,
                          inn: data.inn != null ? data.inn : d.inn, kpp: data.kpp != null ? data.kpp : d.kpp,
                          ltd: data.ltd != null ? data.ltd : d.ltd, phone: data.phone != null ? data.phone : d.phone };
            upsertPlace(place);
            if (dlg && typeof dlg.close === 'function') { dlg.close(); } else { $('.jconfirm').remove(); }
        },
        error: function () { dialogError($f, 'Нет связи с сервером. Попробуйте ещё раз.'); },
        complete: function () { $f.removeClass('is-saving'); $('.jconfirm .buttons button').prop('disabled', false); }
    });
    return false;
}
function upsertPlace(place) {
    var $list = $('#places .col-md-8');
    var $card = $list.find('.panel[id_place="' + place.id + '"]');
    if ($card.length) { $card.replaceWith(placeCard(place)); } else { $list.append(placeCard(place)); }
    var $row = $('.settings-places .settings-place[id_place="' + place.id + '"]');
    if ($row.length) { $row.replaceWith(settingsPlaceRow(place)); } else { $('.settings-places').append(settingsPlaceRow(place)).find('.set-empty').remove(); }
    $('.places_lager .form-control option[value="' + place.id + '"]').text(place.name);
}
function formatPhone(p) {
    var d = String(p || '').replace(/\D/g, '');
    if (d.length === 11 && (d.charAt(0) === '7' || d.charAt(0) === '8')) {
        return '+7 ' + d.substr(1, 3) + ' ' + d.substr(4, 3) + '-' + d.substr(7, 2) + '-' + d.substr(9, 2);
    }
    return p ? String(p) : '';
}
function settingsPlaceRow(place) {
    var has = function (v) { return v && v !== 'null' && v !== 'undefined'; };
    var facts = [];
    if (has(place.ltd)) { facts.push('<span>' + mkEsc(place.ltd) + '</span>'); }
    if (has(place.inn)) { facts.push('<span>ИНН ' + mkEsc(place.inn) + '</span>'); }
    if (has(place.kpp)) { facts.push('<span>КПП ' + mkEsc(place.kpp) + '</span>'); }
    var complete = has(place.ltd) && has(place.inn);
    return '<div class="set-row place-card settings-place" id_place="' + mkEsc(place.id) + '" address="' + mkEsc(place.address) + '" inn="' + mkEsc(place.inn) + '" kpp="' + mkEsc(place.kpp) + '" ltd="' + mkEsc(place.ltd) + '" phone="' + mkEsc(place.phone) + '">' +
        '<div class="set-main"><span class="set-value place-name">' + mkEsc(place.name) + '</span>' +
        '<span class="set-facts">' + (facts.length ? facts.join('') : '<span class="is-warn">Реквизиты для чека не заполнены</span>') + '</span></div>' +
        '<button type="button" class="btn btn-default btn-sm edit_place">' + (complete ? 'Изменить' : 'Заполнить реквизиты') + '</button>' +
    '</div>';
}
function loadSettingsPlaces() {
    var $box = $('.settings-places');
    if (!$box.length) { return; }
    $.ajax({
        type: 'GET',
        url: placeApiUrl('load', { uid: uid, sid: sid, key: key }),
        timeout: 6000,
        success: function (data) {
            if (!$.isArray(data)) { $box.html('<p class="set-empty">Не удалось загрузить кофейни.</p>'); return; }
            if (!data.length) { $box.html('<p class="set-empty">Кофеен пока нет. Добавьте первую в разделе «Кофейни».</p>'); return; }
            $box.html($.map(data, settingsPlaceRow).join(''));
        },
        error: function () { $box.html('<p class="set-empty">Не удалось загрузить кофейни.</p>'); }
    });
}

function setPhoneView(phone) {
    var has = !!(phone && String(phone).replace(/\D/g, ''));
    $('.set_phone').text(has ? formatPhone(phone) : 'Не указан').toggleClass('is-empty', !has).attr('data-raw', has ? phone : '');
    $('.phone_option').text(has ? 'Изменить' : 'Добавить');
}
$(document).on('click', '.phone_option', function () {
    var current = $('.set_phone').attr('data-raw') || '';
    $.confirm({
        content: '<div class="form-group settings-form" id="newphone"><div class="pf"><label for="ph-new">Телефон</label>' +
                 '<input id="ph-new" type="tel" name="phone" class="form-control" inputmode="tel" autocomplete="tel" placeholder="+7 999 123-45-67" value="' + mkEsc(formatPhone(current)) + '"></div>' +
                 '<p class="pf-sub">Нужен, чтобы связаться с вами по аккаунту. Чтобы удалить номер, очистите поле.</p>' +
                 '<p class="pf-error" role="alert"></p></div>',
        title: current ? 'Изменить телефон' : 'Добавить телефон',
        columnClass: 'col-md-4 offset-md-4 col-sm-8 offset-sm-2 place-dialog',
        confirmButton: 'Сохранить',
        cancelButton: 'Отмена',
        confirm: function () {
            var dlg = this, $f = $('#newphone'), $in = $f.find('input[name="phone"]');
            var raw = $.trim($in.val()), digits = raw.replace(/\D/g, '');
            if (raw && (digits.length < 10 || digits.length > 15)) { $in.addClass('is-invalid').focus(); dialogError($f, 'Номер — от 10 до 15 цифр, например +7 999 123-45-67.'); return false; }
            var phone = raw ? (raw.charAt(0) === '+' ? '+' : '') + digits : '';
            if (/^8\d{10}$/.test(phone)) { phone = '+7' + phone.substr(1); }
            $('.jconfirm .buttons button').prop('disabled', true);
            $.ajax({
                type: 'GET',
                url: apiUrl('user', 'newphone', { uid: uid, key: key, phone: phone }),
                timeout: 6000,
                success: function (data) {
                    if (data && data.error) { $in.addClass('is-invalid').focus(); dialogError($f, data.error === 'invalid phone' ? 'Сервер не принял номер: нужно 10–15 цифр.' : 'Не удалось сохранить номер.'); return; }
                    setPhoneView(data && data.phone != null ? data.phone : phone);
                    if (dlg && typeof dlg.close === 'function') { dlg.close(); } else { $('.jconfirm').remove(); }
                },
                error: function () { dialogError($f, 'Нет связи с сервером. Попробуйте ещё раз.'); },
                complete: function () { $('.jconfirm .buttons button').prop('disabled', false); }
            });
            return false;
        }
    });
});
function placeForm(formId, place) {
    place = place || {};
    function f(name, label, opts) {
        opts = opts || {};
        return '<div class="pf' + (opts.wide ? ' pf--wide' : '') + '"><label for="' + formId + '-' + name + '">' + label +
            (opts.required ? '' : ' <span class="pf-opt">необязательно</span>') + '</label>' +
            '<input type="text" id="' + formId + '-' + name + '" name="' + name + '" class="form-control' + (opts.numbers ? ' only_numbers' : '') + '"' +
            (opts.mode ? ' inputmode="' + opts.mode + '"' : '') +
            ' placeholder="' + (opts.ph || '') + '" value="' + mkEsc(name === 'phone' ? formatPhone(place[name]) : place[name]) + '"' + (opts.required ? ' required' : '') + '></div>';
    }
    return '<div class="form-group place-form" id="' + formId + '"' + (place.id ? ' id_place="' + mkEsc(place.id) + '"' : '') + '>' +
        '<div class="pf-group"><div class="pf-title">Основное</div><div class="pf-grid">' +
            f('name', 'Название', { required: true, wide: true, ph: 'Например, Мокко на Рубинштейна' }) +
            f('address', 'Адрес', { required: true, wide: true, ph: 'Город, улица, дом' }) +
            f('phone', 'Телефон для чека', { mode: 'tel', ph: '+7 999 123-45-67' }) +
        '</div></div>' +
        '<div class="pf-group"><div class="pf-title">Реквизиты для чека</div><div class="pf-grid">' +
            f('ltd', 'Юридическое название', { wide: true, ph: 'ООО «Мокко»' }) +
            f('inn', 'ИНН', { mode: 'numeric', ph: '10 или 12 цифр' }) +
            f('kpp', 'КПП', { mode: 'numeric', ph: '9 цифр, у ИП нет' }) +
        '</div></div>' +
        '<p class="pf-error" role="alert"></p>' +
    '</div>';
}

// ── Сотрудники: карточки, формы и действия ─────────────────────────────────
var STAFF_LIMIT = 4; // как в «Лимитах тарифа»
function staffHas(v) { return v != null && v !== '' && v !== 'null' && v !== 'undefined'; }
function staffRate(v) { var n = Number(v); return isFinite(n) && n > 0 ? String(Math.round(n * 100) / 100).replace('.', ',') : ''; }
function staffCard(s) {
    var name = $.trim((staffHas(s.name) ? s.name : '') + ' ' + (staffHas(s.lastname) ? s.lastname : '')) || 'Без имени';
    var initials = ((staffHas(s.name) ? String(s.name).charAt(0) : '') + (staffHas(s.lastname) ? String(s.lastname).charAt(0) : '')).toUpperCase() || '·';
    var rate = staffRate(s.rate), phone = staffHas(s.phone) ? formatPhone(s.phone) : '';
    return '<article class="staff-card" staff_id="' + mkEsc(s.id) + '" data-name="' + mkEsc(s.name) + '" data-lastname="' + mkEsc(s.lastname) + '" data-login="' + mkEsc(s.login) + '" data-phone="' + mkEsc(s.phone) + '" data-rate="' + mkEsc(s.rate) + '">' +
        '<div class="staff-head">' +
            '<span class="staff-avatar" aria-hidden="true">' + mkEsc(initials) + '</span>' +
            '<div class="staff-who"><span class="staff-name">' + mkEsc(name) + '</span><small>Бариста</small></div>' +
            '<button type="button" class="staff-icon dell_staff" aria-label="Удалить сотрудника" title="Удалить">' +
                '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3"/></svg></button>' +
        '</div>' +
        '<dl class="staff-facts">' +
            '<div><dt>Логин</dt><dd>' + (staffHas(s.login) ? mkEsc(s.login) : '<span class="is-empty">не задан</span>') + '</dd></div>' +
            '<div><dt>Телефон</dt><dd>' + (phone ? mkEsc(phone) : '<span class="is-empty">не указан</span>') + '</dd></div>' +
            '<div><dt>Ставка</dt><dd>' + (rate ? mkEsc(rate) + '&nbsp;₽/ч' : '<span class="is-empty">не задана</span>') + '</dd></div>' +
        '</dl>' +
        '<div class="staff-actions">' +
            '<button type="button" class="btn btn-default btn-sm edit_staff">Изменить</button>' +
            '<button type="button" class="btn btn-link btn-sm replace_password">Сменить пароль</button>' +
        '</div>' +
    '</article>';
}
function staffSync() {
    var n = $('#staff .staff-card').length;
    $('#staff .staff-count').text(n + ' из ' + STAFF_LIMIT);
    $('#staff .staff-empty').toggle(n === 0);
    $('#staff .staff-add').toggleClass('is-full', n >= STAFF_LIMIT)
        .find('small').text(n >= STAFF_LIMIT ? 'Лимит тарифа: ' + STAFF_LIMIT + ' аккаунта' : 'Логин и пароль для входа в кассу');
}
function staffRender(list) {
    var $grid = $('#staff .staff-grid');
    $grid.find('.staff-card').remove();
    $grid.prepend($.map($.isArray(list) ? list : [], staffCard).join(''));
    staffSync();
}
function staffUpsert(s) {
    var $old = $('#staff .staff-card[staff_id="' + s.id + '"]');
    if ($old.length) { $old.replaceWith(staffCard(s)); }
    else { $(staffCard(s)).insertBefore('#staff .staff-add').addClass('is-new'); }
    staffSync();
}
function staffPassField(id, label) {
    return '<div class="pf pf--wide"><label for="' + id + '">' + label + '</label>' +
        '<div class="pf-pass"><input id="' + id + '" type="text" name="pass" class="form-control" autocomplete="new-password" spellcheck="false" placeholder="Не короче 8 символов">' +
        '<button type="button" class="pf-gen">Придумать</button></div>' +
        '<p class="pf-sub">Пароль виден, чтобы его можно было передать сотруднику.</p></div>';
}
function staffForm(formId, s) {
    s = s || {};
    function f(name, label, opts) {
        opts = opts || {};
        var val = staffHas(s[name]) ? s[name] : '';
        if (name === 'phone') { val = formatPhone(val); }
        return '<div class="pf' + (opts.wide ? ' pf--wide' : '') + '"><label for="' + formId + '-' + name + '">' + label +
            (opts.optional ? ' <span class="pf-opt">необязательно</span>' : '') + '</label>' +
            (opts.suffix ? '<div class="pf-suffix" data-suffix="' + opts.suffix + '">' : '') +
            '<input type="' + (opts.type || 'text') + '" id="' + formId + '-' + name + '" name="' + name + '" class="form-control"' +
            (opts.mode ? ' inputmode="' + opts.mode + '"' : '') + (opts.ac ? ' autocomplete="' + opts.ac + '"' : '') +
            ' placeholder="' + (opts.ph || '') + '" value="' + mkEsc(val) + '">' + (opts.suffix ? '</div>' : '') + '</div>';
    }
    return '<div class="form-group place-form staff-form" id="' + formId + '"' + (s.id ? ' staff_id="' + mkEsc(s.id) + '"' : '') + '>' +
        '<div class="pf-group"><div class="pf-title">Сотрудник</div><div class="pf-grid">' +
            f('name', 'Имя', { ph: 'Илья', ac: 'off' }) +
            f('lastname', 'Фамилия', { ph: 'Петров', ac: 'off', optional: true }) +
            f('phone', 'Телефон', { type: 'tel', mode: 'tel', ph: '+7 999 123-45-67', optional: true }) +
            f('rate', 'Ставка', { mode: 'decimal', ph: '250', suffix: '₽/ч', optional: true }) +
        '</div></div>' +
        '<div class="pf-group"><div class="pf-title">Вход в кассу</div><div class="pf-grid">' +
            f('login', 'Логин или e-mail', { wide: true, ph: 'ilya@example.com', ac: 'off' }) +
            (s.id ? '' : staffPassField(formId + '-pass', 'Пароль')) +
        '</div></div>' +
        '<p class="pf-error" role="alert"></p>' +
    '</div>';
}
function staffDialogDone(dlg) { if (dlg && typeof dlg.close === 'function') { dlg.close(); } else { $('.jconfirm').remove(); } }
var staffServerErrors = { 'password must be 8-72 characters': ['pass', 'Пароль — от 8 до 72 символов.'] };
function staffSave(mode, dlg, $f, id) {
    var v = function (n) { return $.trim($f.find('input[name="' + n + '"]').val() || ''); };
    var d = { name: v('name'), lastname: v('lastname'), login: v('login'), phone: v('phone'), rate: v('rate').replace(',', '.') };
    var bad = function (n, msg) { $f.find('input[name="' + n + '"]').addClass('is-invalid').focus(); dialogError($f, msg); return false; };
    $f.find('.form-control').removeClass('is-invalid');
    if (!d.name) { return bad('name', 'Укажите имя сотрудника.'); }
    var digits = d.phone.replace(/\D/g, '');
    if (d.phone && (digits.length < 10 || digits.length > 15)) { return bad('phone', 'Телефон — от 10 до 15 цифр, например +7 999 123-45-67.'); }
    if (d.rate && !(Number(d.rate) >= 0)) { return bad('rate', 'Ставка — число, например 250.'); }
    if (!d.login) { return bad('login', 'Укажите логин — с ним сотрудник входит в кассу.'); }
    if (/\s/.test(d.login)) { return bad('login', 'В логине не должно быть пробелов.'); }
    if (d.phone) { d.phone = (d.phone.charAt(0) === '+' ? '+' : '') + digits; if (/^8\d{10}$/.test(d.phone)) { d.phone = '+7' + d.phone.substr(1); } }
    var payload = $.extend({ uid: uid, sid: sid, key: key }, d, { rate: d.rate || '0' });
    if (mode === 'new') {
        var pass = $f.find('input[name="pass"]').val() || '';
        if (pass.length < 8 || pass.length > 72) { return bad('pass', 'Пароль — от 8 до 72 символов.'); }
        payload.pass = pass;
    } else { payload.id = id; }
    $f.addClass('is-saving');
    $('.jconfirm .buttons button').prop('disabled', true);
    $.ajax({
        type: 'GET', url: apiUrl('staff', mode, payload), timeout: 6000,
        success: function (data) {
            if (!data || data.error) {
                var m = (data && staffServerErrors[data.error]) || [null, 'Не удалось сохранить' + (data && data.error ? ': ' + data.error : '.')];
                if (m[0]) { $f.find('input[name="' + m[0] + '"]').addClass('is-invalid').focus(); }
                dialogError($f, m[1]); return;
            }
            staffUpsert({ id: data.Id || data.id || id, name: data.name, lastname: data.lastname, login: data.login, phone: data.phone, rate: data.rate });
            staffDialogDone(dlg);
        },
        error: function () { dialogError($f, 'Нет связи с сервером. Попробуйте ещё раз.'); },
        complete: function () { $f.removeClass('is-saving'); $('.jconfirm .buttons button').prop('disabled', false); }
    });
    return false;
}
function staffFromCard($c) {
    return { id: $c.attr('staff_id'), name: $c.attr('data-name'), lastname: $c.attr('data-lastname'),
             login: $c.attr('data-login'), phone: $c.attr('data-phone'), rate: $c.attr('data-rate') };
}
function staffFullName(s) { return $.trim((staffHas(s.name) ? s.name : '') + ' ' + (staffHas(s.lastname) ? s.lastname : '')) || 'сотрудника'; }

$(document).on('click', '.pf-gen', function () {
    var abc = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789', out = '', rnd = new Uint32Array(10);
    (window.crypto || window.msCrypto).getRandomValues(rnd);
    for (var i = 0; i < 10; i++) { out += abc.charAt(rnd[i] % abc.length); }
    var $in = $(this).siblings('input');
    $in.val(out).trigger('input').trigger('focus');
    try { $in[0].select(); } catch (e) {}
});
$(document).on('click', '.add_staff', function () {
    if ($('#staff .staff-card').length >= STAFF_LIMIT) {
        $.alert({ title: 'Лимит тарифа', content: 'На вашем тарифе можно завести до ' + STAFF_LIMIT + ' аккаунтов сотрудников. Удалите неактуальный аккаунт, чтобы добавить новый.', confirmButton: 'Понятно', columnClass: 'col-md-4 offset-md-4 col-sm-8 offset-sm-2 place-dialog' });
        return false;
    }
    $.confirm({
        title: 'Новый сотрудник',
        content: staffForm('staff_new'),
        columnClass: 'col-md-6 offset-md-3 col-sm-10 offset-sm-1 place-dialog',
        confirmButton: 'Создать аккаунт',
        cancelButton: 'Отмена',
        onOpen: function () { setTimeout(function () { $('#staff_new-name').trigger('focus'); }, 50); },
        confirm: function () { return staffSave('new', this, $('#staff_new')); }
    });
    return false;
});
$(document).on('click', '.edit_staff', function () {
    var s = staffFromCard($(this).closest('.staff-card'));
    $.confirm({
        title: staffFullName(s),
        content: staffForm('staff_edit', s),
        columnClass: 'col-md-6 offset-md-3 col-sm-10 offset-sm-1 place-dialog',
        confirmButton: 'Сохранить',
        cancelButton: 'Отмена',
        confirm: function () { return staffSave('edit', this, $('#staff_edit'), s.id); }
    });
    return false;
});
$(document).on('click', '.replace_password', function () {
    var s = staffFromCard($(this).closest('.staff-card'));
    $.confirm({
        title: 'Новый пароль',
        content: '<div class="form-group place-form staff-form" id="staff_pass"><p class="pf-sub pf-lead"><b>' + mkEsc(staffFullName(s)) + '</b>' +
                 (staffHas(s.login) ? ', логин <b>' + mkEsc(s.login) + '</b>' : '') + '. Старый пароль перестанет работать сразу.</p>' +
                 '<div class="pf-grid">' + staffPassField('staff_pass-in', 'Пароль') + '</div><p class="pf-error" role="alert"></p></div>',
        columnClass: 'col-md-4 offset-md-4 col-sm-8 offset-sm-2 place-dialog',
        confirmButton: 'Сохранить пароль',
        cancelButton: 'Отмена',
        onOpen: function () { setTimeout(function () { $('#staff_pass-in').trigger('focus'); }, 50); },
        confirm: function () {
            var dlg = this, $f = $('#staff_pass'), $in = $f.find('input[name="pass"]'), pass = $in.val() || '';
            if (pass.length < 8 || pass.length > 72) { $in.addClass('is-invalid').focus(); dialogError($f, 'Пароль — от 8 до 72 символов.'); return false; }
            $('.jconfirm .buttons button').prop('disabled', true);
            $.ajax({
                type: 'GET', url: apiUrl('staff', 'newpass', { uid: uid, sid: sid, key: key, id: s.id, password: pass }), timeout: 6000,
                success: function (data) {
                    if (!data || data.error) { $in.addClass('is-invalid').focus(); dialogError($f, (data && staffServerErrors[data.error] || [0, 'Не удалось сменить пароль.'])[1]); return; }
                    staffDialogDone(dlg);
                    var $c = $('#staff .staff-card[staff_id="' + s.id + '"]').addClass('is-saved');
                    setTimeout(function () { $c.removeClass('is-saved'); }, 1600);
                },
                error: function () { dialogError($f, 'Нет связи с сервером. Попробуйте ещё раз.'); },
                complete: function () { $('.jconfirm .buttons button').prop('disabled', false); }
            });
            return false;
        }
    });
    return false;
});
$(document).on('click', '.dell_staff', function () {
    var $c = $(this).closest('.staff-card'), s = staffFromCard($c);
    $.confirm({
        title: 'Удалить сотрудника?',
        content: '<p class="pf-sub pf-lead"><b>' + mkEsc(staffFullName(s)) + '</b> больше не сможет войти в кассу' + (staffHas(s.login) ? ' с логином <b>' + mkEsc(s.login) + '</b>' : '') + '. Отменить удаление нельзя.</p>',
        columnClass: 'col-md-4 offset-md-4 col-sm-8 offset-sm-2 place-dialog staff-del-dialog',
        confirmButton: 'Удалить',
        cancelButton: 'Отмена',
        confirmButtonClass: 'btn-danger',
        confirm: function () {
            var dlg = this;
            $('.jconfirm .buttons button').prop('disabled', true);
            $.ajax({
                type: 'GET', url: apiUrl('staff', 'del', { uid: uid, sid: sid, key: key, id: s.id }), timeout: 6000,
                success: function (data) {
                    if (!data || data.error) { $('.jconfirm .content').append('<p class="pf-error is-open">Не удалось удалить. Попробуйте ещё раз.</p>'); return; }
                    staffDialogDone(dlg);
                    $c.addClass('is-leaving');
                    setTimeout(function () { $c.remove(); staffSync(); }, 260);
                },
                error: function () { $('.jconfirm .content').append('<p class="pf-error is-open">Нет связи с сервером.</p>'); },
                complete: function () { $('.jconfirm .buttons button').prop('disabled', false); }
            });
            return false;
        }
    });
    return false;
});

$(document).ready(function($) {
                  $('.edit_this_item').hide();
                  $('.layer').hide();
                  $('.layer.active').show();
                  $('.darkness').hide();
                  $('.top_alert').hide();
                 // $('.startup').hide();
                  $('#miniloader').hide();
                 // load("categiry");
                  $('#progress_stat').hide();
                  $('[data-toggle="tooltip"]').tooltip();
                  

                  
                  Date.prototype.format = function (mask, utc) {
                  return dateFormat(this, mask, utc);
                  };
				  
				  
				 $('.navigation a').click(function(){
					 console.log('start');
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
				  
				  
				  
                  $('body').on("keydown",".only_numbers",function(event) {
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
                  $('.datepicker').datepicker({'format':'yyyy-mm-dd'});
				  
                 function ajax_get(url, callback) {
                  $.ajax({
                      type: 'GET',
                      url: url,
                      dataType: 'json',
                      success: callback,
                      error: function(jqXHR, status) {
                          console.error('ajax_get error:', url, status);
                          toaster('Нет ответа от сервера');
                      }
                  });
                  }
                  
                ajax_get('http://mokkoapi.azurewebsites.net/ini/{"uid":"'+uid+'","sid":"'+sid+'","key":"'+key+'"}', function(data) {
                         
                         var answer = data;
                         
                         if(answer['error']){
                         window.location.href = "http://mokko-crm.ru/?do=exit";
                         }else{
                         var answer = data;
                    
                         var currency = answer.settings.currency;
                         ballance(answer.settings.wallet);
                         $('.your_name span').empty().append(answer.settings.name);
                         fillSettings(answer.settings);
                         
                         switch(answer.settings.status){
                         case "new":
                         //$('.top_alert').show().addClass('alert-info').empty().append('<center>У Вас нет активных подписок на сервисы</center>');
                         // $('.startup').show();
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
                   
                         ///
                         ajax_get('http://mokkoapi.azurewebsites.net/lager/load/{"uid":"'+uid+'","sid":"'+sid+'","key":"'+key+'"}', function(data) {
                                  console.log("lager: OK");
                                  window.lager_json = data;
                                       $('#pageloader').hide();
                                 
                         ajax_get('http://mokkoapi.azurewebsites.net/places/load/{"uid":"'+uid+'","key":"'+key+'","sid":"'+sid+'"}', function(data) {
                                  $('.places_lager .form-control').empty().append('<option disabled="" selected="">Выберите кофейню</option>');
                                  data_arr = data;
            
                                  $('#load_mainbar').remove();
                                  if(data_arr.length==0){
                                  
                                  $('#startup').show();
                                  }else{
                                  $('#statistic').show();
                                  $('#startup').remove();
                                  }
                                
                                  $.each(data_arr, function (i,place){
                                         $('.places_lager .form-control').append('<option value='+place.id+'>'+place.name+'</option>');
                                    });
                                  })
                               })
                          })

                  /////функции
                  
                  function ballance(summ){
                  $('.ballance span').empty().append(summ);
                  }
                  
                  function load(module){
                  $('#miniloader').show();
                  
                  ajax_get('http://mokkoapi.azurewebsites.net/'+module+'/load/{"uid":"'+uid+'","sid":"'+sid+'","key":"'+key+'"}', function(data) {
                           viewer(module,data);
                           $('#miniloader').hide();
                           if(data['error']){
                           toaster(data);
                           ///обработчик ошибок
                           }else{
                           return data;
                           }
                           })
                  }
                           
                  
             

                           
                  
                  $('.create_place').click(function(){
                                           if($('#places .panel-default').length >= 2){
                                               $.alert({ title: 'Лимит тарифа', content: 'На вашем тарифе можно добавить до 2 кофеен.', confirmButton: 'Понятно' });
                                               return;
                                           }
                                           $.confirm({
                                                     content: placeForm('new_place'),
                                                     title: 'Новая кофейня',
                                                     columnClass: 'col-md-6 offset-md-3 col-sm-10 offset-sm-1 place-dialog',
                                                     confirmButton: 'Создать кофейню',
                                                     cancelButton: 'Отмена',
                                                     confirm: function(){ return savePlace('new', this, $('#new_place')); }
                                                     });
                                           });

                  $('.show_stat').click(function(){
                                 
                                        
                                        var start_date = $('.start_date').val();
                                        var stop_date = $('.stop_date').val();
                                        var place = $('#home select[name=place]').val();
										
                                        if(stop_date=='' ||  start_date=='' ||  place == null){
                                        
										$.alert({
                                                title: 'Не верные параметры!',
                                                content: "Выберите дату начала и конца периода, а так же, кофейню",
                                                theme: 'black',
                                                confirmButton: 'Закрыть'
                                                });
										
                                        }else{
                                         $('#progress_stat').show("slow");
                                        $('#microloader').css('display','block');
                                        
                                        $('#progress_stat .progress-bar').css('width','25%');
                                        
                                        //check
    ajax_get('http://mokkoapi.azurewebsites.net/stat/check/{"uid":"'+uid+'","sid":"'+sid+'","key":"'+key+'","place":"'+place+'","start_date":"'+start_date+'","stop_date":"'+stop_date+'"}', function(data) {

                                               var answer = (typeof data === 'string') ? JSON.parse(data) : data;

                                               if(answer['error']){
                                                toaster(answer['error']);
                                               
                                               }else{
                                               
                                               $('#progress_stat .progress-bar').css('width','50%');
                                               
                                               viewer("stat_func",answer);
    ajax_get('http://mokkoapi.azurewebsites.net/stat/sales/{"uid":"'+uid+'","sid":"'+sid+'","key":"'+key+'","place":"'+place+'","start_date":"'+start_date+'","stop_date":"'+stop_date+'"}', function(data) {


                                                    var answer = (typeof data === 'string') ? JSON.parse(data) : data;
                                               if(answer['error']){
                                               alert(answer['error']);
                                               
                                               }else{
                                               $('#progress_stat .progress-bar').css('width','75%');
                                               viewer("stat_check",answer);
                                               

ajax_get('http://mokkoapi.azurewebsites.net/stat/staff/{"uid":"'+uid+'","sid":"'+sid+'","key":"'+key+'","place":"'+place+'","start_date":"'+start_date+'","stop_date":"'+stop_date+'"}', function(data) {


                                                      var answer = (typeof data === 'string') ? JSON.parse(data) : data;
                                                      if(answer['error']){
                                                       toaster(answer['error']);
                                                      
                                                      }else{
                                                      $('#progress_stat .progress-bar').css('width','85%');
                                                      viewer("stat_staff",answer);
         
         }
         })
             }
             })
             }
             })
										}
         })
                                   //////


                  $('body').on('click','.create_item',function(){
                                       $('.progress-bar').css('display','block');
                                       
                                       var id_category = $(this).parent().attr('category_id');
                               
                                       $.ajax({
                                              type: 'GET',
                                              url: 'http://mokkoapi.azurewebsites.net/item/new/{"uid":"'+uid+'","sid":"'+sid+'","key":"'+key+'", "category": "'+id_category+'"}',
                                              timeout:4000,
                                              success: function(data) {
                                  
                                              var answer = data;
                                              if(answer['error']){
                                              toaster(answer['error']);
                                              
                                              }else{
                                              $('#category'+id_category+' ul.list-group').append('<li id_item="'+answer.Id+'" mass_item="'+answer.mass+'" em_item="'+answer.em+'" class="list-group-item"><div class="for_blur"><div class="item_in_coll"><b>Название</b><span>0</span> р. <i class="items_edit pull-right glyphicon glyphicon-remove remove_item_cull"></i><i class="pull-right glyphicon glyphicon-pencil edit_item_tool"></i></div></div></li>');
                                              
                                              $('.progress-bar').css('display','none');
                                              
                                              
                                              }
                                              }
                                              });
                       
                                       return false;
                                       });
                  
                  
                  $('.create_category').click(function(){
                                            $.ajax({
                                                   type: 'GET',
                                                   url: 'http://mokkoapi.azurewebsites.net/category/new/{"uid":"'+uid+'","sid":"'+sid+'","key":"'+key+'"}',
                                                   timeout:3000,
                                                   success: function(data) {
                                                   var answer = JSON.parse(data);
                                                   if(answer['error']){
                                                    toaster(answer['error']);
                                                   }else{
                                                   
                                                   if(answer['name'] == ''){
                                                   answer['name'] = "Название";
                                                   }
         
                                                   $('#menu ul a:first').tab('show');
                                                   
                                                   }
                                                   }
                                                   });
                                            
                                            
                                            return false;
                                            });
                  

                  ////только после выполнения ini загружается остальное
                
                  

                  
                  $('main_affix').affix('checkPosition');
                  
                  
                  
                  $('.crm_action').click(function () {
                                       
                                       if($('#home').hasClass('active')){
                                       
                                       }else{
                                       $('.layer.active').hide().removeClass('active');
                                       $('#home').show().addClass('active');
                                       
                                       }
                                       
                                       if($('.startup').length){
                                       $('.startup').css('box-shadow', '0 0px 25px rgba(67, 159, 224, 0.53)');
                                       setTimeout( function(){
                                                  $('.startup').css('box-shadow', 'none');
                                                  },800)
                                       
                                       return false;
                                       }else{
                                       
                                       window.location.href = BASE_URL + "/?view=enter";
                                       }
                                       });
                  
                  
                  
   
                  
                  $('body').on('click', '.save_item', function(){
                    $('.progress-bar').css('display','block');
                    var em = $('.item_edit_open select[name="item_em"]').val();
                    var mass = $('.item_edit_open input[name="item_mass"]').val();
                    var price = $('.item_edit_open input[name="item_price"]').val();
                    var name = $('.item_edit_open input[name="item_name"]').val();
                               
                    var id = $('.item_edit_open input[name="item_id"]').val();
                    var category = $('.item_edit_open input[name="category"]').val();
                    
                    var ingredients = [];
                               $('.lager_tags p').each(function(i) {
                                   var id = $(this).attr('item_id');
                                   var mass = $(this).find('input[name="mass"]').val();
                                   ingredients.push({id,mass});
                                });
                               
                               ing = JSON.stringify(ingredients);
                               
                               //
                               console.log(ing);
                               
                               $.ajax({
                                      type: 'GET',
                                      url: 'http://mokkoapi.azurewebsites.net/item/edit/{"uid":"'+uid+'","sid":"'+sid+'","key":"'+key+'","id":"'+id+'","category":"'+category+'","name":"'+name+'","price":"'+price+'","mass":"'+mass+'","em":"'+em+'","ingredients":'+ing+'}',
                                      timeout:4000,
                                      success: function(data) {
                                      var answer = JSON.stringify(data);
                                      console.log(data.Id);
                                      $('.progress-bar').css('display','none');
                                      
                                      $('#menu .list-group li[id_item="'+data.Id+'"]').find('b').empty().append(data.name);
                                      $('#menu .list-group li[id_item="'+data.Id+'"]').find('span').empty().append(data.price);
                                      $('#menu .list-group li[id_item="'+data.Id+'"]').attr('mass_item',data.mass);
                                      $('#menu .list-group li[id_item="'+data.Id+'"]').attr('em_item',data.em);
                                      
                                      },error: function(data){
                                      console.log(data);
                                    //  $('.progress-bar').css('display','none');
                                      
                                    ///  $('#menu.list-group li[id_item="'+data.Id+'"]').find('b').empty().append(data.name);
                                    //  $('#menu .list-group li[id_item="'+data.Id+'"]').find('span').empty().append(data.price);
                                    //  $('#menu .list-group li[id_item="'+data.Id+'"]').attr('mass_item',data.mass);
                                    //  $('#menu .list-group li[id_item="'+data.Id+'"]').attr('em_item',data.em);
                                      }
                                      });
                               
                               
                               return false;
                               
                    });
                  
                $('body').on('keyup', 'input[name=name_category]',function(){
                               value_of_input = $(this).val();
                               if(value_of_input != ''){
                                $(this).parent().parent().find('button').prop( "disabled", false ).removeClass('disabled');
                                }else{
                                $(this).parent().parent().find('button').prop( "disabled", true ).addClass('disabled');
                               }
                               return false;
                 });
                  
                  
                  
                  
                  $('body').on('click', '.remove_item_cull', function(){
                               
                               if($(this).parent().parent().hasClass('del_alert')){
                               
                               }else{
                               id_mini_alerts = $(this).parent().parent().attr('item_id');
                               $(this).parent().addClass('del_alert');
                               $(this).parent().parent().append('<div class="alert_dialog" for_item ><button type="button" class="btn btn-link dell_this_item">Удалить</button><button type="button" class="btn btn-link close_this_alert_on_item">Отмена</button></div>');
                               $(this).parent().parent().parent().removeClass('active');
                               
                               }
                               return false;
                               });
                  
                  $('body').on('click','.dell_this_item',function(){
                               $('.progress-bar-items').css('display','block');
                               var id_item = $(this).parent().parent().parent().attr('id_item');
                               
                               $.ajax({
                                      type: 'GET',
                                      url: 'http://mokkoapi.azurewebsites.net/item/del/{"uid":"'+uid+'","sid":"'+sid+'","key":"'+key+'","id":"'+id_item+'"}',
                                      timeout:4000,
                                      complete: function(data) {
                                      var answer = data;
                                      console.log(answer);
                                      $('#menu li[id_item="'+id_item+'"]').remove();
                                      $('.progress-bar-items').css('display','none');
   
                                      }
                                      });
                               
                               return false;
                               });
                  
                  
                  $('body').on( 'click','.alert_dialog .close_this_alert_on_item', function(){
                               $(this).parent().parent().find('.for_blur').removeClass('del_alert');
                               $(this).parent().remove();
                               return false
                               });
                  
                  $('body').on('click','.edit_item_tool',function(){
	 
                               $('.darkness').show()
                               $('.edit_this_item').slideToggle('fast');
                               var id_item = $(this).parent().parent().parent().attr('id_item');
                               var mass_item = $(this).parent().parent().parent().attr('mass_item');
                               var em_item = $(this).parent().parent().parent().attr('em_item');
                               
                               var name = $(this).parent().parent().find('.item_in_coll b').text();
                               var price = $(this).parent().parent().find('.item_in_coll span').text();
                               
                               var category = $(this).parent().parent().parent().parent().parent().attr('category_id');
                    
                               edititem(id_item,name,price,mass_item,em_item,category);
                               return false;
                               });
                  
                  $('body').on('click','.edit_place',function(e){
                               e.preventDefault();
                               var $card = $(this).closest('.place-card, .panel');
                               var place = { id: $card.attr('id_place'), name: $card.find('.place-name').first().text(),
                                             address: $card.attr('address'), inn: $card.attr('inn'), kpp: $card.attr('kpp'),
                                             ltd: $card.attr('ltd'), phone: $card.attr('phone') };
                               $.confirm({
                                         content: placeForm('place_edit', place),
                                         columnClass: 'col-md-6 offset-md-3 col-sm-10 offset-sm-1 place-dialog',
                                         title: 'Изменить кофейню',
                                         confirmButton: 'Сохранить',
                                         cancelButton: 'Отмена',
                                         confirm: function(){ return savePlace('edit', this, $('#place_edit'), place.id); }
                                         });
                  });
                  
                  
                  $('body').on('click','.close_white', function(){
                               $('.edit_this_item').hide();
                               $('.darkness').hide();
                               return false;
                               });
                  
                  
                  function edititem(id,name,price,mss,em,category){
                  
                  var lager_block = '<div class="col-md-6 col-6 no_mobile"><input placeholder="Найти по названию" id="mini_lager_search" type="text" class="mini_lager_search validate form-control"><div class="mini_lager"><table id="mini_lager" class="table table-striped"><tbody></tbody></table></div></div><div class="col-md-6 col-6 lager_tags no_mobile"><center><br/>Загрузка...</center></div>';
                  $('.ingredients_place').empty().append(lager_block);
                  
                  $('.item_edit_open input[name="item_id"]').val(id);
                  $('.item_edit_open input[name="category"]').val(category);
                  
                  $('.item_edit_open input[name="item_name"]').val(name);
                  $('.item_edit_open input[name="item_price"]').val(price);
                  $('.item_edit_open input[name="item_mass"]').val(mss);
                  //$('.item_edit_open input[name="item_em"]').val(em);
                  $('.item_edit_open option[value="'+em+'"]').prop('selected', true);
                  
                  
                  console.log(window.lager_json);
                  var lager_items = window.lager_json;
                  ///
                  
                  $.each(lager_items, function (i,lager){
                         var number = i + 1;
                         
                         if(lager.em == '1'){
                         var em = 'Мл.';
                         }
                         if(lager.em == '2'){
                         var em = 'Гр.';
                         }
                         
                         if(lager.em == '3'){
                         var em = 'Шт.';
                         }
                         
                         if(typeof(lager.em) == 'undefined'){
                         var em = '';
                         }
                        if(lager.active!=='0'){
                           var display_me = '';
                        }else{
                           var display_me = 'class="delited_l"';
                        };
                         $('#mini_lager tbody').append('<tr '+display_me+' class="enable" lager_item="'+lager.id+'" lager_em="'+em+'" ><td>'+lager.name+'<span>'+lager.name.toLowerCase()+'</span> <i class="glyphicon glyphicon-plus" style="float: right;"></i></td></tr>');
                         });
                  
                  
                  ajax_get('http://mokkoapi.azurewebsites.net/item/get/{"uid":"'+uid+'","sid":"'+sid+'","key":"'+key+'", "id": "'+id+'"}', function(data) {
                           var array_data = data;
                           
                           console.log(array_data.ingredients);
                           var lager_data = JSON.parse(array_data.ingredients);
                           
                           
                           $('.lager_tags').empty();
						   
						
						   
                           $.each(lager_data, function (i,lager){
                                  console.log(lager.id);
                                  
                                  $('#mini_lager tbody tr[lager_item="'+lager.id+'"]').addClass('active').find('i').removeClass('glyphicon-plus').addClass('glyphicon-ok');
                                  if($('#mini_lager tbody tr[lager_item="'+lager.id+'"]').hasClass('delited_l')){
                                  var display_me = 'style="color: #de1414;background: rgba(255, 63, 63, 0.17);" data-toggle="tooltip" data-placement="top" title="Удален со склада. Удалите ингредиент!"';
                                  }else{
                                  var display_me = '';
                                  }
                                  var em = $('#mini_lager tbody tr[lager_item="'+lager.id+'"]').attr('lager_em');
                                  var name =  $('#mini_lager tbody tr[lager_item="'+lager.id+'"]').find('span').text().charAt(0).toUpperCase() + $('#mini_lager tbody tr[lager_item="'+lager.id+'"]').find('span').text().substr(1);
                                  
                                  
                                  $('.lager_tags').append('<p item_id="'+lager.id+'" class="lager_tag"><input '+display_me+' class="form-nocontrol lager_tag_name" type="text" name="" value="'+name+'" disabled/><input class="form-nocontrol lager_tag_mass" type="text" name="mass" value="'+lager.mass+'" /><span class="lager_tag_em">'+em+'</span><a href="#!" class="remove_minilager_item"><i class="glyphicon glyphicon-remove"></i></a></p>');
                                  
                                  });
						  
                           
                           if(typeof(window.lager_json) == 'undefined'){
                           
                           alert("Ошибка загрузки Склада! Обновите страницу");
                           
                           }
                           
                           
                           

                           })
                  
                  }
                  
 
                  var search_lager = $('#search_lager');
                  search_lager.keyup(function() {
                                     if(search_lager.val()==''){
                                     $('.lager_table tbody tr').addClass('enable');
                                     }else{
                                     $('.lager_table tbody tr.enable').removeClass('enable');
                                     $('.lager_table tbody td:contains("'+search_lager.val().toLowerCase()+'")').parent().addClass('enable');
                                     }
                                     });
                  
                  
                  

                  $('body').on('change', 'select.m_select', function(){
                               
                               var action = $(this).val();
                               if(action=='remove'){
                               $(this).parent().parent().find('.save_lager_item').css('display','block').removeClass('btn-primary').addClass('btn-danger').empty().append('Удалить безвозвратно');
                               $(this).parent().parent().find('input[name="diff"]').val(0).prop( "disabled", true );
                               
                               }else{
                               $(this).parent().parent().find('.save_lager_item').css('display','block').addClass('btn-primary').removeClass('btn-danger').empty().append('Применить');
                               $(this).parent().parent().find('input[name="diff"]').prop( "disabled", false );
                               }
                               });
                  
                  function viewer(module,data,parametr){
                  
                  switch(module){
                  
                  case "stat_staff":
                  var answer = data;
                  $('#staff_stat .table_stat').find('tbody').empty();
                  var total = 0;
                  if (!answer || !answer.length) {
                      $('#staff_stat .table_stat').find('tbody').append('<tr><td colspan="3" class="stat-muted">За этот период закрытых смен нет</td></tr>');
                  }
                  $.each(answer, function (i,user){
                         var secs = Number(user.secs) || Math.round(Number(user.h) * 3600) || 0;
                         var hh = Math.floor(secs / 3600), mm = Math.floor(secs / 60) % 60;
                         var dur = (hh ? hh + '&nbsp;ч ' : '') + mm + '&nbsp;мин';
                         var n = Number(user.shifts) || 0, n10 = n % 10, n100 = n % 100;
                         var word = (n100 > 10 && n100 < 20) ? 'смен' : n10 === 1 ? 'смена' : (n10 > 1 && n10 < 5) ? 'смены' : 'смен';
                         var earned = Number(user.rate) || 0;
                         $('#staff_stat .table_stat').find('tbody').append('<tr><th>' + mkEsc(user.name) + '</th>' +
                             '<td>' + dur + (n ? '<small class="stat-sub">' + n + '&nbsp;' + word + '</small>' : '') + '</td>' +
                             '<td>' + (earned ? Math.round(earned).toLocaleString('ru-RU') + '&nbsp;₽' : '<span class="stat-muted" title="Ставка не задана">—</span>') + '</td></tr>');
                         })
                   $('#progress_stat .progress-bar').css('width','100%');
                   $('#progress_stat').hide("slow");
                   //$('#progress_stat .progress-bar').css('width','0%');
                  break;
                  
                  case "stat_check":
                  var answer = data;
                  $('#sales .table_stat').find('tbody').empty();
                  var total = 0;
                  var labels_arr = [];
                  var series_arr = [];
                  window.final_crap = [];
                  $.each(answer, function (i,item){
                         total = total + Number(item.total);
						 console.log("total:"+total);
                         $('#sales .table_stat').find('tbody').append('<tr><th>'+item.name+'</th> <th>'+item.q+'</th> <th>'+item.total+'</th> </tr>');
                         labels_arr.push(item.name);
                         series_arr.push(item.q);
                         
                         var meta_arr = [];
                         meta_arr.push(item.name)
                         meta_arr.push(item.q);
                         
                         window.final_crap.push(meta_arr);
                         
                    })
                  $('#sales .total_price').find('b').empty().append(total);
                  $('.proceeds').find('center').empty().append(Math.round(total).toLocaleString('ru-RU')+"\u00a0₽");
                  $('#microloader').css('display','none');
                  $('#charts_and_tables').show();
                  
       
                  ///
                  var final_days = window.final_days;
                  var final_crap = window.final_crap;
                  
                  // Графики — свои SVG в стиле сайта (js/charts.js), вместо Google Charts
                  if (window.MokkoCharts) {
                      MokkoCharts.donut(document.getElementById('piechart'), final_crap);
                      MokkoCharts.days(document.getElementById('staff_chart'), final_days);
                  }
                  ///

                  break;
                  
                  

                  case "stat_func":
                  var answer = data;
                  var proceeds = new Array();
                  var a_b = new Array();
                  var labels = new Array();
                  var totala_b = 0;
                  var qu = 0;
                  
                  window.final_days = [];
                  $.each(answer, function (i,item){
                         var day = new Date(i);
                         
                         labels.push(day.format("ddd dd/mm"));
                         proceeds.push(item.proceeds);
                         a_b.push(item.a_b);
                         qu = qu + 1;
                         totala_b = Number(totala_b) + Number(item.a_b);
            
                         
                         
                         var meta_arr = [];
                         var day = new Date(i);
                         day = new Date(i);
                        meta_arr.push(day);
                         meta_arr.push("<div style='padding:10px;width:170px;'><p><b>"+day.format("dddd dd.mm")+"</b></p><p> <span style='color:#ff0000;'>●</span> Выручка: <b>"+item.proceeds+"</b></p><p><span style='color:#0000ff;'>●</span> Средний чек: <b>"+Number(item.a_b)+"</b></p>");
                         meta_arr.push(Number(item.a_b));
                         meta_arr.push(item.proceeds);
                         
                         
                         window.final_days.push(meta_arr);
                         
                         })
                  
                  // Если блок уже на экране (данные пришли позже продаж) — перерисовываем график
                  if (window.MokkoCharts && $('#charts_and_tables').is(':visible')) {
                      MokkoCharts.days(document.getElementById('staff_chart'), window.final_days);
                  }
                  if(totala_b!==0){
                  totala_b = totala_b / qu;
                  
                  
                  $('.a_b').find('center').empty().append(Math.round(totala_b).toLocaleString('ru-RU')+"\u00a0₽");
                  }
                  
                  
                  break;
                  
                  
                  
                  case "one_place":
                   $('#places .col-md-8').append(placeCard(data));
                  break;
                  
                  case "places":
                  
                  var data_arr = data;
                  //console.log(data);
                  $('#places .col-md-8').empty();
                  $.each(data_arr, function (i,place){
                  $('#places .col-md-8').append(placeCard(place));
                  });
                  break;
                  
                  
                  case "lager":
				  $('#lager select[name="place"]').val("Выберите кофейню").change();
                  window.lager_json = data;
                  
                  console.log(data);
                  
                  $('.lager_table').hide();

                  var data_arr = data;
				  
                  $('#lager tbody').empty();
                  $.each(data_arr, function (i,lager){
                                var number = i + 1;
                         
                                if(lager.em == '1'){
                                var em = 'Мл.';
                                }
                                if(lager.em == '2'){
                                var em = 'Гр.';
                                }
                                
                                if(lager.em == '3'){
                                var em = 'Шт.';
                                }
                                
                                if(typeof(lager.em) == 'undefined'){
                                var em = '';
                                }
                         var  mass = $.parseJSON(lager.mass);
                         var massa = '';
						
                         $.each(mass, function (i,m_ass){
							   var styles_red = '';
							   if(Number(m_ass.mass) <= 0){ 
								styles_red = 'style="color:red;"';	
								}
                                massa =  massa+'<div place_id="'+m_ass.place+'" '+styles_red+' >'+m_ass.mass+'</div>';
                            });
							//console.log(massa);[{"place":1,"mass":210}
							
 if(lager.active!=='0'){
$('#lager tbody').append('<tr class="enable" item_id="'+lager.id+'"><th scope="row"><i class="glyphicon glyphicon-pencil edit_lager_item"></i></th><td>'+lager.name+'<span>'+lager.name.toLowerCase()+'</span></td><td class="mass">'+massa+'</td><td>'+em+'</td><td><select class="browser-default m_select  form-control"><option value="" disabled selected>Выбрать действие</option><option value="add">Пополнить</option><option value="substract">Списать</option><option value="remove">Удалить</option></select></td><td><input value="" type="text" placeholder=" 0" name="diff" class="form-control only_numbers"></td><td><a class="btn  btn-primary col-md-12 col-12 save_lager_item">Сохранить</a></td></tr>');
 }
 });
                  break;
                  case "category":
				 
                  data_arr = data;
				   console.log(data_arr);
				   
				     $('#menu .special_tabs').empty();
                   $('#menu .tab-content').empty();

			if(data_arr.length == 0){
				
				 $('#menu .tab-content').append('<div role="tabpanel" class="tab-pane" category_id="" id="new_category"><div class="editor_item row"><div class="col-8 col-md-3"><input type="text" class="form-control" name="name_category" placeholder="Название новой категории" value=""></div><div class="col-4 col-md-6 switch"><input id="new_pakage" class="package-toggle toggle-round-flat" type="checkbox" name="lager" ><label for="new_pakage"></label><span class="switch-label">Показывать в кассе</span></div><div class="col-12 col-md-3"><button type="submit" style="float: none;" class="pull-right btn btn-primary col-12 no_mobile disabled save_category" href="#">Сохранить</button></div></div><ul class="list-group"></ul><div class="progress"><div class="progress-bar progress-bar-striped progress-bar-items active" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="display:none;width: 100%"><span class="sr-only"></span></div></div></div>');
                  $('#menu .special_tabs').append('<li role="presentation" class="active"><a href="#new_category" aria-controls="category" role="tab" data-toggle="tab"><i class="glyphicon glyphicon-plus"></i>Категория</a></li>');
                   $('#menu ul a:first').tab('show');
 $('#menu #new_category').css('display','block');
				
			}else{
				                  $.each(data_arr, function (i,menu){
                         if(menu.name==null){
                         menu.name = 'Название';
                         }
                         if(menu.status=='on'){
                         var status = 'checked';
                         }
                         if(menu.status=='off'){
                         var status = '';
                         }
                         
                         
                $('#menu .special_tabs').append('<li status="'+menu.status+'" role="presentation" category_id="'+menu.id+'" ><a href="#category'+menu.id+'" aria-controls="menu_items" role="tab" data-toggle="tab">'+menu.name+'</a><i class="cat_edit pull-right glyphicon glyphicon-remove"></i></li>');
                         $('#menu .tab-content').append('<div role="tabpanel" class="tab-pane" category_id="'+menu.id+'" id="category'+menu.id+'"><div class="editor_item row"><div class="col-8 col-md-3"><input type="text" class="form-control" placeholder="Название" name="name_category" value="'+menu.name+'"></div><div class="col-4 col-md-6 switch"><input id="package'+menu.id+'" class="package-toggle toggle-round-flat" type="checkbox" name="status"  '+status+'><label for="package'+menu.id+'"></label><span class="switch-label">Показывать в кассе</span></div><div class="col-12 col-md-3"><button type="submit" style="float: none;" class="pull-right btn btn-primary col-12 no_mobile disabled save_category" href="#">Сохранить</button></div></div><button type="submit" style="float: none;margin-top: 10px;margin-bottom: 10px;" class="create_item btn" href="#">Добавить позицию</button><ul class="list-group"></ul><div class="progress"><div class="progress-bar progress-bar-striped progress-bar-items active" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="display:none;width: 100%"><span class="sr-only"></span></div></div></div>');
                         
                         $('#menu .tab-content').append('<div role="tabpanel" class="tab-pane" category_id="" id="new_category"><div class="editor_item row"><div class="col-8 col-md-3"><input type="text" class="form-control" name="name_category" placeholder="Название новой категории" value=""></div><div class="col-4 col-md-6 switch"><input id="new_pakage" class="package-toggle toggle-round-flat" type="checkbox" name="lager" ><label for="new_pakage"></label><span class="switch-label">Показывать в кассе</span></div><div class="col-12 col-md-3"><button type="submit" style="float: none;" class="pull-right btn btn-primary col-12 no_mobile disabled save_category" href="#">Сохранить</button></div></div><ul class="list-group"></ul><div class="progress"><div class="progress-bar progress-bar-striped progress-bar-items active" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="display:none;width: 100%"><span class="sr-only"></span></div></div></div>');
                  });
                  $('#menu .special_tabs').append('<li role="presentation" class="active"><a href="#new_category" aria-controls="category" role="tab" data-toggle="tab"><i class="glyphicon glyphicon-plus"></i>Категория</a></li>');
                  
                  
                   $('#menu ul a:first').tab('show');
                   var id_first_category =  $('#menu ul.category_tabs li:first').attr('category_id');
                   $('.progress-bar-items').css('display','block');
                  $.ajax({
                         
                         type: 'GET',
                         url: 'http://mokkoapi.azurewebsites.net/items/load/{"uid":"'+uid+'","sid":"'+sid+'","key":"'+key+'","category":"'+id_first_category+'"}',
                         timeout:4000,
                         success: function(data) {
                         if(data['error']){
                         toaster(data);
                         }else{
                         viewer('items',data,id_first_category);
                         }
                         
                         }
                         });
				
			}


                  
                  
                  
                  break;
                  
                  
                  case "items":

                  var items = (typeof data === 'string') ? $.parseJSON(data) : data;
                                 //   console.log(data);
                  $('#category'+parametr+' ul.list-group').empty();

                  $.each(items, function (i,item){
                         if(item.name == null){
                         item.name = "Название";
                         };
                         if(item.price == null){
                         item.price = "0";
                         };
                         if(item.em == null){
                         item.em = "0";
                         };
                         if(item.mass == null){
                         item.mass= "1";
                         };
                         
                   $('#category'+parametr+' ul.list-group').append('<li id_item="'+item.id+'" mass_item="'+item.mass+'" em_item="'+item.em+'" class="list-group-item"><div class="for_blur"><div class="item_in_coll"><b>'+item.name+'</b><span>'+item.price+'</span> р. <i class="items_edit pull-right glyphicon glyphicon-remove remove_item_cull"></i><i class="pull-right glyphicon glyphicon-pencil edit_item_tool"></i></div></div></li>');
                          });
                  $('.progress-bar-items').css('display','none');
                  break;
                  
                  case "staff":
                  staffRender(data);
                  break;

                  case "person":
                  staffUpsert({ id: data.Id || data.id, name: data.name, lastname: data.lastname, login: data.login, phone: data.phone, rate: data.rate });
                  break;

                  }
                  
                  }
                  
                  $('.places_lager select').change(function() {
                    var place = $(this).val();
					console.log(place);
                    $('.lager_table').show();
                    $('.lager_table').find('td.mass').find('div').hide();
                    $('.lager_table').find('td.mass div[place_id="'+place+'"]').show();
                    });
                  
                  $('body').on('click', '.save_lager_item', function(){
                               var id = $(this).parent().parent().attr('item_id');
                               var place = $('#lager .places_lager select').val();

                               if($(this).hasClass('btn-danger')){
                               $.ajax({
                                      type: 'GET',
                                      url: 'http://mokkoapi.azurewebsites.net/lager/remove/{"id":"'+id+'","sid":"'+sid+'","uid":"'+uid+'","key":"'+key+'"}',
                                      success: function(data){

                                      }
                                      });
                               $(this).parent().parent().fadeOut();
                               
                               }else{
                               var this_placce = $('#lager .places_lager select').val()
                               var diff = $(this).parent().parent().find('input[name="diff"]').val();
                               var act = $(this).parent().parent().find('select.m_select').val();
                               $(this).attr('disabled',true);
                               ///add /substract
                               $.ajax({
                                      type: 'GET',
                                      url: 'http://mokkoapi.azurewebsites.net/lager/'+act+'/{"id":"'+id+'","sid":"'+sid+'","uid":"'+uid+'","key":"'+key+'","mass":"'+diff+'","place":"'+this_placce+'"}',
                                      success: function(data){
                                      console.log(data);
                                      var  mass = $.parseJSON(data.mass);
                                      var massa = '';
                                      $.each(mass, function (i,m_ass){
										  
									   if(Number(m_ass.mass) <= 0){
										  $('.lager_table tr[item_id="'+id+'"]').find('td.mass div[place_id="'+m_ass.place+'"]').empty().append(m_ass.mass).css('color','red');

									   }else{
										  $('.lager_table tr[item_id="'+id+'"]').find('td.mass div[place_id="'+m_ass.place+'"]').empty().append(m_ass.mass).css('color','#333');
									   }
                                        $('.lager_table tr[item_id="'+id+'"]').find('.save_lager_item').attr('disabled',false);
                                      });
                                      
                                     
                                      $('.lager_table tr[item_id="'+id+'"]').find('input[name="diff"]').val('');
                                      }
                                      });
                               
                               }
                               return false;
                               
                               });
                  
                  
                  $('body').on('keyup','input.mini_lager_search',function(){
                               
                               if($('input.mini_lager_search').val()==''){
                               $('.mini_lager tbody tr').addClass('enable');
                               }else{
                               $('.mini_lager tbody tr.enable').removeClass('enable');
                               $('.mini_lager tbody td:contains("'+$('input.mini_lager_search').val().toLowerCase()+'")').parent().addClass('enable');
                               }
                               
                               });
                  
                  $('body').on('click','#mini_lager tbody tr',function(){
                               $(this).addClass('active').find('i').removeClass('glyphicon-plus').addClass('glyphicon-ok');
                               var id =  $(this).attr('lager_item');
                               var em = $(this).attr('lager_em');
                               var name = $(this).find('span').text().charAt(0).toUpperCase() + $(this).find('span').text().substr(1);
                               if($('.lager_tags p[item_id="'+id+'"]').length == 0){
                               $('.lager_tags').append('<p item_id="'+id+'" class="lager_tag"><input class="form-nocontrol lager_tag_name" type="text" name="" value="'+name+'" disabled/><input class="form-nocontrol lager_tag_mass" type="text" name="mass" value="0" /><span class="lager_tag_em">'+em+'</span><a href="#!" class="remove_minilager_item"><i class="glyphicon glyphicon-remove"></i></a></p>');
                               }
                               return false;
                               });
                  
                  $('body').on('click','.new_lager_item', function(){
                            if($('#lager select[name="place"] option:selected').text() !==  "Выберите кофейню"){
							 $('#microloader').css('display','block');	
                               $.ajax({
                                      type: 'GET',
                                      url: 'http://mokkoapi.azurewebsites.net/lager/new/{"sid":"'+sid+'","uid":"'+uid+'","key":"'+key+'"}',
                                      success: function(data){
                                       $('#microloader').css('display','none');
                                      var place = $('#lager select[name="place"]').val();
									  
                                      var  mass = $.parseJSON(data.mass);
                                      
                                      var massa = '';
                                      $.each(mass, function (i,m_ass){
										  if(m_ass.place == place){
										var display = "";	  
										  }else{
										var display = "display:none;";		  
										  }
                                          massa =  massa+'<div place_id="'+m_ass.place+'" style="'+display+'color:red;" >'+m_ass.mass+'</div>';
                                        });
                                             
                                      
                                      $('.lager_table tbody').prepend('<tr class="enable" item_id="'+data.Id+'"><th scope="row"><i class="glyphicon glyphicon-pencil edit_lager_item"></i></th><td>Название<span>название</span></td><td class="mass">'+massa+'</td><td>Мл.</td><td><select class="browser-default m_select  form-control"><option value="" disabled="" selected="">Выбрать действие</option><option value="add">Пополнить</option><option value="substract">Списать</option><option value="remove">Удалить</option></select></td><td><input value="" type="text" placeholder=" 0" name="diff" class="form-control only_numbers"></td><td><a class="btn  btn-primary col-md-12 col-12 save_lager_item">Сохранить</a></td></tr>');
                                      
                                      }
                                      });
							}
                               
                               return false;
                               });
                  
                  $('body').on('click','.remove_minilager_item',function(){
                               var id = $(this).parent().attr('item_id');
                               $('#mini_lager tbody tr[lager_item="'+id+'"]').removeClass('active').find('i').removeClass('glyphicon-ok').addClass('glyphicon-plus');
                               $(this).parent().remove();
                               
                               });
                  
                  $('body').on('click','#mini_lager tbody tr.active',function(){
                               var id = $(this).attr('lager_item');
                               $('.lager_tags p[item_id="'+id+'"]').remove();
                               $(this).removeClass('active').find('i').removeClass('glyphicon-ok').addClass('glyphicon-plus');

                               });
                  
                  
                  
                  
                  $('body').on('click','.category_tabs li', function(){
                    var category =  $(this).attr('category_id');
                    $('.progress-bar-items').css('display','block');
                    ajax_get('http://mokkoapi.azurewebsites.net/items/load/{"uid":"'+uid+'","sid":"'+sid+'","key":"'+key+'","category":"'+category+'"}', function(data) {
                             if(data['error']){
                             toaster(data);
                             }else{
                             viewer('items',data,category);
                             }
                             })
               
                    });
                  
                  $('.today').on('click', function() {
                                 
                                       var now = moment();
                                       day = now.format('DD');
                                       month = now.format('MM'),
                                       year = now.format('YYYY');
                                       var start_date = year+'-'+month+'-'+day;
                                       $('.start_date').empty().val(start_date);
                                       $('.stop_date').empty().val(start_date);
                  });
                  $('.yesterday').on('click', function() {
                                 
                                     var now = moment().subtract('days', 1);
                                     day = now.format('DD');
                                     month = now.format('MM'),
                                     year = now.format('YYYY');
                                     var start_date = year+'-'+month+'-'+day;
                                     $('.start_date').empty().val(start_date);
                                     $('.stop_date').empty().val(start_date);
                    });
                  $('.week').on('click', function() {
                                var now = moment();
                                var now2 = moment().subtract('days', 7);
                                day = now.format('DD');
                                day2 = now2.format('DD');
                                month = now.format('MM'),
                                month2 = now2.format('MM'),
                                year = now.format('YYYY');
                                year2 = now2.format('YYYY');
                                var start_date = year2+'-'+month2+'-'+day2;
                                var stop_date = year+'-'+month+'-'+day;
                                $('.start_date').empty().val(start_date);
                                $('.stop_date').empty().val(stop_date);
                                });
                  
                  
                  $('.month').on('click', function() {
                                
                                 var now2 = moment().subtract('months', 1);
                                 var now = moment();
                                 day = now.format('DD');
                                 day2 = now2.format('DD');
                                 month = now.format('MM'),
                                 month2 = now2.format('MM'),
                                 year = now.format('YYYY');
                                 year2 = now2.format('YYYY');
                                 var start_date = year2+'-'+month2+'-'+day2;
                                 var stop_date = year+'-'+month+'-'+day;
                                 $('.start_date').empty().val(start_date);
                                 $('.stop_date').empty().val(stop_date);
                    });
                 
                  
                  $('body').on('click','.edit_lager_item', function(){
                               
                               var name = $(this).parent().parent().find('td:first span').text().charAt(0).toUpperCase() + $(this).parent().parent().find('td:first span').text().substr(1);
                               var em = $(this).parent().parent().find('td:eq(2)').text();
                               
                               if(em == 'Мл.'){
                               var em = '1';
                               }
                               if(em == 'Гр.'){
                               var em = '2';
                               }
                               
                               if(em == 'Шт.'){
                               var em = '3';
                               }
                               
                               if(typeof(em) == 'undefined'){
                               var em = '';
                               }
                               
                               //             var name = $(this).parent().parent().find('span').text();
                               $(this).parent().parent().find('td:first').empty().append('<input type="text" class="form-control"  name="name" value="'+name+'" palceholder="Название" />');
                               $(this).parent().parent().find('td:eq(2)').empty().append('<select class="form-control" name="em"><option value="1" selected="">Мл.</option><option value="2">Гр.</option><option value="3">Шт.</option></select>');
                               
                               
                               $(this).parent().parent().find('select option[value="'+em+'"]').prop('selected', true);
                               
                               $(this).removeClass('glyphicon-pencil').addClass('glyphicon-ok').removeClass('edit_lager_item').addClass('submit_lager_item');
                               return false;
                               
                               });

                  
                  $('body').on('click','.submit_lager_item', function(){
                               var id = $(this).parent().parent().attr('item_id');
                               var name = $(this).parent().parent().find('input[name="name"]').val();
                               var em = $(this).parent().parent().find('select[name="em"]').val();
                               /////ajax
                               $.ajax({
                                      type: 'GET',
                                      url: 'http://mokkoapi.azurewebsites.net/lager/edit/{"uid":"'+uid+'","sid":"'+sid+'","key":"'+key+'","id":"'+id+'","name":"'+name+'","em":"'+em+'"}',
                                      success: function(data){
                                      }
                                      });
                               //////posle ajax
                               
                               $(this).parent().parent().find('td:first').empty().append(name+'<span>'+name.toLowerCase()+'</span>');
                               
                               
                               
                               if(em == '1'){
                               var em = 'Мл.';
                               }
                               if(em == '2'){
                               var em = 'Гр.';
                               }
                               
                               if(em == '3'){
                               var em = 'Шт.';
                               }
                               
                               if(typeof(em) == 'undefined'){
                               var em = '';
                               }
                               
                               $(this).parent().parent().find('td:eq(2)').empty().append(em);
                               
                               $(this).removeClass('glyphicon-ok').addClass('glyphicon-pencil').removeClass('submit_lager_item').addClass('edit_lager_item');

                               return false;
                               
                        });
                  
                  $('body').on('click','.editor_item label',function(){
                              
                        //$(this).parent().parent().find('button').prop( "disabled", false ).removeClass('disabled');
                              var category = $(this).parent().parent().parent().attr('category_id');
                              $('.progress-bar-items').css('display','block');
                               
                               
                              // $(this).parent().parent().find('input[name="status"]').css('visibility','visible');
                               if($(this).parent().parent().find('input[name="status"]').is(":checked")){
                               var status = 'off';
                               }else{
                               var status = 'on';
                               }
                               
                               $.ajax({
                                      
                                      type: 'GET',
                                      url: 'http://mokkoapi.azurewebsites.net/category/status/{"uid":"'+uid+'","sid":"'+sid+'", "key":"'+key+'", "category":"'+category+'", "status":"'+status+'"}',
                                      timeout:4000,
                                      success: function(data) {
                                      if(data['error']){
                                      toaster(data);
                                      $('.progress-bar-items').css('display','none');
                                      }else{
                                      $('.progress-bar-items').css('display','none');
                                      return true;
                                      }
                                      
                                      }
                                      });
                               
                    });
                  
                $('body').on('click','.save_category', function(){
                             $('.progress-bar-items').css('display','block');
                             var category = $(this).parent().parent().parent().attr('category_id');
                            
                             var name_category = $(this).parent().parent().find('input[name="name_category"]').val();
                             if($(this).parent().parent().find('input[name="status"]').is(":checked")){
                             var status = 'on';
                             }else{
                             var status = 'off';
                             }
                            
                             if(category == ''){
                             
                               if($('ul.category_tabs li').length < 6){
                             
                             $.ajax({
                                    
                                    type: 'GET',
                                    url: 'http://mokkoapi.azurewebsites.net/category/new/{"uid":"'+uid+'","sid":"'+sid+'", "key":"'+key+'",  "name":"'+name_category+'", "status":"'+status+'"}',
                                    timeout:4000,
                                    success: function(data) {
                                    if(data['error']){
                                    toaster(data);
                                    }else{
                                    console.log(data);
                                    $('.category_tabs li.active').removeClass('active');
                                    
                                    $('#new_category').remove();
                                    
                                    $('a[href="#new_category"]').empty().append(data.name).parent().append('<i class="cat_edit pull-right glyphicon glyphicon-remove"></i>');
                                    $('a[href="#new_category"]').attr('href','#category'+data.Id);
                                    $('a[href="#category'+data.Id+'"]').parent().attr('category_id',data.Id);
                                    
                                    $('#menu .tab-content').append('<div role="tabpanel" class="tab-pane" category_id="'+data.Id+'" id="category'+data.Id+'"><div class="editor_item row"><div class="col-8 col-md-3"><input type="text" class="form-control" placeholder="Название" name="name_category" value="'+data.name+'"></div><div class="col-4 col-md-6 switch"><input id="package'+data.Id+'" class="package-toggle toggle-round-flat" type="checkbox" name="status"  '+status+'><label for="package'+data.Id+'"></label><span class="switch-label">Показывать в кассе</span></div><div class="col-12 col-md-3"><button type="submit" style="float: none;" class="pull-right btn btn-primary col-12 no_mobile disabled save_category" href="#">Сохранить</button></div></div><button type="submit" style="float: none;margin-top: 10px;margin-bottom: 10px;" class="create_item btn" href="#">Добавить позицию</button><ul class="list-group"></ul><div class="progress"><div class="progress-bar progress-bar-striped progress-bar-items active" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="display:none;width: 100%"><span class="sr-only"></span></div></div></div>');
                                    
                                    
                                    $('#menu .special_tabs').append('<li role="presentation" class="active"><a href="#new_category" aria-controls="category" role="tab" data-toggle="tab"><i class="glyphicon glyphicon-plus"></i>Категория</a></li>');
                                    
                                    $('#menu a[href="#category'+data.Id+'"]').tab('show');
                                    
                                    $('#menu .tab-content').append('<div role="tabpanel" class="tab-pane" category_id="" id="new_category"><div class="editor_item row"><div class="col-8 col-md-3"><input type="text" class="form-control" name="name_category" placeholder="Название новой категории" value=""></div><div class="col-4 col-md-6 switch"><input id="new_pakage" class="package-toggle toggle-round-flat" type="checkbox" name="lager"><label for="new_pakage"></label><span class="switch-label">Показывать в кассе</span></div><div class="col-12 col-md-3"><button type="submit" style="float: none;" class="pull-right btn btn-primary col-12 no_mobile disabled save_category" href="#">Сохранить</button></div></div><ul class="list-group"></ul><div class="progress"><div class="progress-bar progress-bar-striped progress-bar-items active" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="display:none;width: 100%"><span class="sr-only"></span></div></div></div>');
                                    
                                    }
                             
                   

                             
         
                                    }
                                    
                                    })
                             }else{
                             $.alert({
                                     title:"Увы",
                                     content: "Максимальное число категорий достигнуто",
                                     confirmButton: 'Ок'
                                     });
                             }
                            
                  
                             }else{
                             
                             
                             $.ajax({
                             
                             type: 'GET',
                             url: 'http://mokkoapi.azurewebsites.net/category/edit/{"uid":"'+uid+'","sid":"'+sid+'", "key":"'+key+'", "category":"'+category+'", "name":"'+name_category+'", "status":"'+status+'"}',
                             timeout:4000,
                             success: function(data) {
                             if(data['error']){
                             toaster(data);
                             }else{
                                    $('.category_tabs li[category_id="'+category+'"]').find('a').empty().append(data.name);
                                    $('.tab-content div[category_id="'+category+'"').find('input[name="name_category"]').val(data.name);
                                    $('.progress-bar-items').css('display','none');
                             }
                             
                             }
                             });
                             }
      
                             return false;
                });
                  
                  
                      $('body').on('click','.password_option', function(){
                                   $.confirm({
                                             content: '<div class="form-group settings-form" id="old_pass">' +
                                                      '<div class="pf"><label for="pw-old">Текущий пароль</label><input id="pw-old" type="password" name="old_pass" class="form-control" autocomplete="current-password"></div>' +
                                                      '<div class="pf"><label for="pw-new">Новый пароль <span class="pf-opt">от 8 символов</span></label><input id="pw-new" type="password" name="new_pass" class="form-control" autocomplete="new-password"></div>' +
                                                      '<div class="pf"><label for="pw-new2">Повторите новый пароль</label><input id="pw-new2" type="password" name="new_pass_two" class="form-control" autocomplete="new-password"></div>' +
                                                      '<p class="pf-error" role="alert"></p></div>',
                                             title: 'Сменить пароль',
                                             columnClass: 'col-md-4 offset-md-4 col-sm-8 offset-sm-2 place-dialog',
                                             confirmButton: 'Сменить пароль',
                                             cancelButton: 'Отмена',
                                             confirm: function(){
                                             var $f = $('#old_pass');
                                             if ($f.hasClass('is-done')) { return true; }
                                             var $old = $f.find('input[name="old_pass"]'), $new = $f.find('input[name="new_pass"]'), $two = $f.find('input[name="new_pass_two"]');
                                             var old_pass = $old.val(), new_pass = $new.val(), new_pass_two = $two.val();
                                             $f.find('.form-control').removeClass('is-invalid');
                                             if (!old_pass) { $old.addClass('is-invalid').focus(); dialogError($f, 'Введите текущий пароль.'); return false; }
                                             if (new_pass.length < 8) { $new.addClass('is-invalid').focus(); dialogError($f, 'Новый пароль — минимум 8 символов.'); return false; }
                                             if (new_pass !== new_pass_two) { $two.addClass('is-invalid').focus(); dialogError($f, 'Пароли не совпадают.'); return false; }
                                             $('#microloader').css('display','block');
                                             $.ajax({
                                                    type: 'GET',
                                                    url: apiUrl('user', 'newpassword', { uid: uid, key: key, old_password: old_pass, password: new_pass }),
                                                    timeout: 4000,
                                                    success: function(data) {
                                                    $('#microloader').css('display','none');
                                                    if (data && data['error']) {
                                                        if (data['error'] === 'wrong password') { $old.addClass('is-invalid').focus(); dialogError($f, 'Текущий пароль указан неверно.'); }
                                                        else if (data['error'].indexOf('8-72') > -1) { $new.addClass('is-invalid').focus(); dialogError($f, 'Пароль должен быть от 8 до 72 символов.'); }
                                                        else { dialogError($f, 'Не удалось сменить пароль. Попробуйте ещё раз.'); }
                                                        return;
                                                    }
                                                    $f.addClass('is-done').html('<div class="dialog-done"><svg viewBox="0 0 64 64" aria-hidden="true"><circle cx="32" cy="32" r="29"/><path d="M20 33.5l8 8 16-17"/></svg><b>Пароль изменён</b><span>При следующем входе используйте новый пароль.</span></div>');
                                                    $('.jconfirm .buttons button').first().text('Готово').end().slice(1).hide();
                                                    },
                                                    error: function(){ $('#microloader').css('display','none'); dialogError($f, 'Нет связи с сервером. Попробуйте ещё раз.'); }
                                                    });
                                             return false;
                                             }
                                             });
                      });
                  
                  $('body').on('click','.name_option', function(){
                               var my_name = $.trim($('.name_value').first().text());
                               if (my_name === '—') { my_name = ''; }
                               $.confirm({
                                         content: '<div class="form-group settings-form" id="newname"><div class="pf"><label for="nm-new">Как к вам обращаться</label><input id="nm-new" type="text" name="new_name" class="form-control" maxlength="60" value="' + mkEsc(my_name) + '"></div><p class="pf-error" role="alert"></p></div>',
                                         title: 'Изменить имя',
                                         columnClass: 'col-md-4 offset-md-4 col-sm-8 offset-sm-2 place-dialog',
                                         confirmButton: 'Сохранить',
                                         cancelButton: 'Отмена',
                                         confirm: function(){
                                         var dlg = this;
                                         var $f = $('#newname');
                                         var $in = $f.find('input[name="new_name"]');
                                         var newname = $.trim($in.val());
                                         if (newname.length < 2) { $in.addClass('is-invalid').focus(); dialogError($f, 'Имя — минимум 2 буквы.'); return false; }
                                         $('#microloader').css('display','block');
                                         $.ajax({
                                                type: 'GET',
                                                url: apiUrl('user', 'newname', { uid: uid, key: key, newname: newname }),
                                                timeout: 4000,
                                                success: function(data) {
                                                $('#microloader').css('display','none');
                                                if (data && data['error']) { dialogError($f, 'Не удалось сохранить имя. Попробуйте ещё раз.'); return; }
                                                $('.your_name span').text(newname);
                                                $('.set_name').text(newname);
                                                $('.set-avatar').text(newname.charAt(0).toUpperCase());
                                                if (dlg && typeof dlg.close === 'function') { dlg.close(); } else { $('.jconfirm').remove(); }
                                                },
                                                error: function(){ $('#microloader').css('display','none'); dialogError($f, 'Нет связи с сервером. Попробуйте ещё раз.'); }
                                                });
                                         return false;
                                         }
                                         });
                  });
                  
                  $('body').on('click','.cat_edit.glyphicon-remove', function(){
                               var id = $(this).parent().attr('category_id');
                               $('.progress-bar-items').css('display','block');
                               var cat_name = $(this).parent().find('a').text();
                               
                               $.ajax({
                                      
                                      type: 'GET',
                                      url: 'http://mokkoapi.azurewebsites.net/items/load/{"uid":"'+uid+'","sid":"'+sid+'","key":"'+key+'","category":"'+id+'"}',
                                      timeout:4000,
                                      success: function(data) {
                                      if(data['error']){
                                      toaster(data);
                                      }else{
                                      var leight_li = data.length;
                                      if(leight_li == 2){
                                      
                                      ///////
                                      $.confirm({
                                                content: '',
                                                title: '<h3>Удалить "'+cat_name+'"?<h3>',
                                                confirmButton: 'Удалить',
                                                cancelButton: 'Отмена',
                                                confirm: function(){
                                                
                                                $.ajax({
                                                       
                                                       type: 'GET',
                                                       url: 'http://mokkoapi.azurewebsites.net/category/del/{"uid":"'+uid+'","sid":"'+sid+'", "key":"'+key+'", "category":"'+id+'"}',
                                                       timeout:4000,
                                                       success: function(data) {
                                                       if(data['error']){
                                                       toaster(data);
                                                       }else{
                                                       $('.category_tabs').find('li[category_id="'+id+'"]').remove();
                                                       $('.tab-content').find('div[category_id="'+id+'"]').remove();
                                                       $('.progress-bar-items').css('display','none');
                                                       }
                                                       return true;
                                                       }
                                                       });
                                                
                                                
                                                
                                                
                                                }
                                                });
                                      //////
                                      
                               
                                      //////
                                      

                                      }else{
                                      ////
                                      $.alert({
                                              title: false,
                                              content: 'Можно удалить только пустую категорию!',
                                              autoClose: 'cancel|1000',
                                              confirmButton: 'Закрыть'
                                        });
                                      ////
                                      $('.progress-bar-items').css('display','none');

                                      }
                                      
                                      }
                                      
                                      }
                                      });
                               
                               
                               
                               ////загружаем items выбранного Menus и смотрим количество элементов
                               ////
                            
                               return false;
                               });
            //////errors
                  
                  function toaster(msg){
                  if(msg == "invalid token"){
$('body').empty().append(msg);
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
                  
    
                  /////
                  
                

                  //////
                  
                  });
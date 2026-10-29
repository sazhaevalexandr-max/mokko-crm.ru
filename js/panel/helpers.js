


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
    return 'api:///' + module + '/' + action + '/' + JSON.stringify(payload).replace(/%/g, '%25');
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
var STAFF_LIMIT = (window.MOKKO_PLAN && Number(window.MOKKO_PLAN.staff)) || 4;
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
            f('login', 'Логин', { wide: true, ph: 'bari_ilya', ac: 'off' }) +
            (s.id ? '' : staffPassField(formId + '-pass', 'Пароль')) +
        '</div></div>' +
        '<p class="pf-error" role="alert"></p>' +
    '</div>';
}
function staffDialogDone(dlg) { if (dlg && typeof dlg.close === 'function') { dlg.close(); } else { $('.jconfirm').remove(); } }
var staffServerErrors = {
    'password must be 8-72 characters': ['pass', 'Пароль — от 8 до 72 символов.'],
    'invalid login': ['login', 'Логин: без пробелов, до 60 символов.'],
    'login already used': ['login', 'Этот логин уже занят другим аккаунтом.']
};
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
    if (d.login.length > 60) { return bad('login', 'Логин — до 60 символов.'); }
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


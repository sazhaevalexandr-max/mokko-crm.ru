<?php
    if(!defined('MOKKO'))die;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#0f0d0b">
<title>Создание аккаунта — MOKKO CRM</title>
<link rel="icon" type="image/svg+xml" href="<?= BASE_PATH ?>/images/brand/favicon.svg?v=2">
<link href="<?= BASE_PATH ?>/css/bootstrap-4.6.2.min.css" rel="stylesheet">
<link href="<?= BASE_PATH ?>/css/theme.css?v=54" rel="stylesheet">
<script>var BASE_URL = <?= mokko_js(BASE_URL) ?>;</script>
</head>

<body class="auth-body auth-body--center signup-page">

<header class="auth-brandbar">
    <a class="brand" href="<?= BASE_URL ?>/login" aria-label="MOKKO CRM">
        <span class="brand-text"><img class="brand-word" src="<?= BASE_PATH ?>/images/brand/wordmark-sm.svg?v=1" alt="" width="240" height="50"><span class="brand-crm">CRM</span></span>
    </a>
</header>

<main class="auth-card signup-card">

    <section class="signup-step is-active" id="signup-form-step">
        <div class="auth-card-head">
            <h1 class="auth-title">Создание аккаунта</h1>
            <p class="auth-sub">Займёт меньше минуты. Кофейни и сотрудников добавите после входа.</p>
        </div>

        <div class="form-alert" role="alert" aria-live="assertive"><div><p></p></div></div>

        <form id="registraion" novalidate>

            <div class="field" data-field="name">
                <label for="Name">Имя</label>
                <div class="field-control">
                    <input id="Name" name="name" type="text" class="form-control" placeholder="Как к вам обращаться" autocomplete="name" maxlength="60" aria-describedby="Name-msg">
                    <span class="field-status" aria-hidden="true">
                        <svg class="st-ok" viewBox="0 0 24 24"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
                        <svg class="st-err" viewBox="0 0 24 24"><path d="M12 7.5v5.5"/><path d="M12 16.6v.4"/></svg>
                        <span class="st-spin"></span>
                    </span>
                </div>
                <div class="field-msg" id="Name-msg"><div><span></span></div></div>
            </div>

            <div class="field" data-field="email">
                <label for="Email">E-mail</label>
                <div class="field-control">
                    <input id="Email" name="email" type="email" class="form-control" placeholder="you@example.com" autocomplete="email" inputmode="email" maxlength="254" aria-describedby="Email-msg">
                    <span class="field-status" aria-hidden="true">
                        <svg class="st-ok" viewBox="0 0 24 24"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
                        <svg class="st-err" viewBox="0 0 24 24"><path d="M12 7.5v5.5"/><path d="M12 16.6v.4"/></svg>
                        <span class="st-spin"></span>
                    </span>
                </div>
                <div class="field-msg" id="Email-msg"><div><span></span></div></div>
            </div>

            <div class="field field--password" data-field="password">
                <label for="Password">Пароль</label>
                <div class="field-control">
                    <input id="Password" name="password" type="password" class="form-control" placeholder="Минимум 8 символов" autocomplete="new-password" maxlength="72" aria-describedby="Password-msg Password-meter">
                    <span class="field-status" aria-hidden="true">
                        <svg class="st-ok" viewBox="0 0 24 24"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
                        <svg class="st-err" viewBox="0 0 24 24"><path d="M12 7.5v5.5"/><path d="M12 16.6v.4"/></svg>
                        <span class="st-spin"></span>
                    </span>
                    <button type="button" class="pass-toggle" aria-label="Показать пароль" aria-pressed="false">
                        <svg class="eye-open" viewBox="0 0 24 24"><path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="eye-closed" viewBox="0 0 24 24"><path d="M3 3l18 18"/><path d="M10.6 5.6c.5-.1.9-.1 1.4-.1 6 0 9.5 6.5 9.5 6.5a17 17 0 0 1-2.9 3.7M6.6 6.6C4.1 8.3 2.5 12 2.5 12S6 18.5 12 18.5c1.8 0 3.3-.5 4.6-1.3"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                    </button>
                </div>
                <div class="pass-meter" id="Password-meter" data-score="0" aria-live="polite">
                    <span class="bars"><i></i><i></i><i></i><i></i></span>
                    <span class="pass-meter-label">Минимум 8 символов</span>
                </div>
                <div class="field-msg" id="Password-msg"><div><span></span></div></div>
            </div>

            <button type="submit" class="btn btn-primary btn-block auth-submit signup-submit">
                <span class="btn-label">Создать аккаунт</span>
                <span class="btn-spin" aria-hidden="true"></span>
            </button>

            <p class="signup-terms">Нажимая «Создать аккаунт», вы соглашаетесь на обработку персональных данных.</p>
        </form>

        <div class="auth-links signup-links">
            <span>Уже есть аккаунт? <a href="<?= BASE_URL ?>/login">Войти</a></span>
        </div>
    </section>

    <section class="signup-step signup-done" id="signup-done-step" aria-live="polite" hidden>
        <svg class="done-mark" viewBox="0 0 64 64" aria-hidden="true">
            <circle class="done-ring" cx="32" cy="32" r="29"/>
            <path class="done-check" d="M20 33.5l8 8 16-17"/>
        </svg>
        <h1 class="auth-title">Аккаунт создан</h1>
        <p class="auth-sub">Войдите с адресом <b class="done-email"></b> и паролем, который вы только что задали.</p>
        <a class="btn btn-primary btn-block auth-submit" href="<?= BASE_URL ?>/login">Перейти ко входу</a>
    </section>

</main>

<?php require_once __DIR__ . '/copyright.php'; ?>
<p class="auth-copy signup-copy"><?= mokko_copyright() ?></p>

<script>
(function () {
    'use strict';

    var form = document.getElementById('registraion');
    var fields = {
        name: form.querySelector('[data-field="name"]'),
        email: form.querySelector('[data-field="email"]'),
        password: form.querySelector('[data-field="password"]')
    };
    var alertBox = document.querySelector('.form-alert');
    var submitBtn = form.querySelector('.signup-submit');
    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Те же правила, что проверяет сервер (api/auth.php): e-mail, пароль 8–72 символа, непустое имя.
    var nameRe;
    try { nameRe = new RegExp("^[\\p{L}][\\p{L}\\p{M} '’.-]*$", 'u'); }
    catch (e) { nameRe = /^[A-Za-zА-Яа-яЁё][A-Za-zА-Яа-яЁё '’.-]*$/; }
    var emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

    var rules = {
        name: function (v) {
            v = v.trim();
            if (!v) { return 'Введите имя'; }
            if (v.length < 2) { return 'Минимум 2 буквы'; }
            if (!nameRe.test(v)) { return 'Только буквы, пробел и дефис'; }
            return '';
        },
        email: function (v) {
            v = v.trim();
            if (!v) { return 'Введите e-mail'; }
            if (!emailRe.test(v)) { return 'Проверьте адрес — например, name@mail.ru'; }
            return '';
        },
        password: function (v) {
            if (!v) { return 'Придумайте пароль'; }
            if (v.length < 8) { return 'Минимум 8 символов — ещё ' + (8 - v.length); }
            var bytes = window.TextEncoder ? new TextEncoder().encode(v).length : v.length;
            if (bytes > 72) { return 'Слишком длинный пароль: сократите до 72 латинских или 36 русских символов'; }
            return '';
        }
    };

    function input(key) { return fields[key].querySelector('input'); }

    function setMsg(field, text) {
        field.querySelector('.field-msg span').textContent = text || '';
    }

    function shake(el) {
        if (reduceMotion) { return; }
        el.classList.remove('is-shaking');
        void el.offsetWidth;
        el.classList.add('is-shaking');
    }

    function setState(key, state, msg) {
        var f = fields[key];
        var inp = input(key);
        var wasInvalid = f.classList.contains('is-invalid');
        f.classList.remove('is-valid', 'is-invalid', 'is-checking');
        if (state) { f.classList.add('is-' + state); }
        inp.setAttribute('aria-invalid', state === 'invalid' ? 'true' : 'false');
        if (state === 'invalid') {
            setMsg(f, msg);
            if (!wasInvalid) { shake(f.querySelector('.field-control')); }
        } else if (state === 'valid') {
            setMsg(f, '');
            if (key === 'email') { lastEmailOk = inp.value.trim(); }
        }
    }

    var emailTimer = null;
    var lastEmailOk = null;
    function validate(key, opts) {
        opts = opts || {};
        var v = input(key).value;
        var err = rules[key](v);
        if (key === 'email' && !err && opts.animateCheck && v.trim() !== lastEmailOk) {
            // короткая «проверка» адреса — тот же приём, что был в старой форме
            fields.email.classList.remove('is-valid', 'is-invalid');
            fields.email.classList.add('is-checking');
            clearTimeout(emailTimer);
            emailTimer = setTimeout(function () { setState('email', 'valid'); }, reduceMotion ? 0 : 380);
            return true;
        }
        if (err) {
            if (opts.quiet && !fields[key].classList.contains('is-invalid')) {
                fields[key].classList.remove('is-valid');
                return false;
            }
            setState(key, 'invalid', err);
            return false;
        }
        setState(key, 'valid');
        return true;
    }

    Object.keys(fields).forEach(function (key) {
        var inp = input(key);
        var f = fields[key];
        inp.addEventListener('blur', function () {
            if (inp.value === '' && !f.dataset.touched) { return; }
            f.dataset.touched = '1';
            validate(key, { animateCheck: key === 'email' });
        });
        inp.addEventListener('input', function () {
            hideAlert();
            if (!f.dataset.touched) {
                // до первого ухода с поля не ругаемся, но сразу подтверждаем верное значение
                if (!rules[key](inp.value) && key !== 'email') { setState(key, 'valid'); }
                else { f.classList.remove('is-valid'); }
                return;
            }
            clearTimeout(emailTimer);
            validate(key, { quiet: false });
        });
        f.querySelector('.field-control').addEventListener('animationend', function (e) {
            if (e.animationName === 'mk-shake') { this.classList.remove('is-shaking'); }
        });
    });

    // Показать / скрыть пароль
    var toggle = form.querySelector('.pass-toggle');
    toggle.addEventListener('click', function () {
        var inp = input('password');
        var show = inp.type === 'password';
        inp.type = show ? 'text' : 'password';
        toggle.setAttribute('aria-pressed', show ? 'true' : 'false');
        toggle.setAttribute('aria-label', show ? 'Скрыть пароль' : 'Показать пароль');
        fields.password.classList.toggle('is-revealed', show);
        inp.focus();
        try { inp.setSelectionRange(inp.value.length, inp.value.length); } catch (e) {}
    });

    // Надёжность пароля
    var meter = form.querySelector('.pass-meter');
    var meterLabel = meter.querySelector('.pass-meter-label');
    var labels = ['Минимум 8 символов', 'Слабый пароль', 'Средний пароль', 'Хороший пароль', 'Надёжный пароль'];
    input('password').addEventListener('input', function () {
        var v = this.value, score = 0;
        if (v.length >= 8) {
            score = 1;
            if (v.length >= 12) { score++; }
            if (/[a-zа-яё]/.test(v) && /[A-ZА-ЯЁ]/.test(v)) { score++; }
            if (/\d/.test(v) && /[^A-Za-zА-Яа-яЁё0-9]/.test(v)) { score++; }
            else if (/\d/.test(v) || /[^A-Za-zА-Яа-яЁё0-9]/.test(v)) { score = Math.max(score, 2); }
        }
        score = Math.min(score, 4);
        meter.setAttribute('data-score', String(score));
        meterLabel.textContent = v.length && v.length < 8 ? 'Ещё ' + (8 - v.length) + ' ' + plural(8 - v.length, 'символ', 'символа', 'символов') : labels[score];
    });

    function plural(n, one, few, many) {
        var m10 = n % 10, m100 = n % 100;
        if (m10 === 1 && m100 !== 11) { return one; }
        if (m10 >= 2 && m10 <= 4 && (m100 < 12 || m100 > 14)) { return few; }
        return many;
    }

    function showAlert(text) {
        alertBox.querySelector('p').textContent = text;
        alertBox.classList.add('is-open');
    }
    function hideAlert() { alertBox.classList.remove('is-open'); }

    var serverErrors = {
        'limit': 'Слишком много попыток регистрации. Попробуйте снова через час.',
        'invalid email': 'Сервер не принял этот e-mail. Проверьте адрес.',
        'password must be 8-72 characters': 'Пароль должен быть от 8 до 72 символов.',
        'invalid data': 'Заполните все поля.',
        'server': 'Не удалось создать аккаунт: ошибка на сервере. Попробуйте ещё раз через минуту.'
    };

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        hideAlert();
        var firstBad = null;
        ['name', 'email', 'password'].forEach(function (key) {
            fields[key].dataset.touched = '1';
            var ok = validate(key);
            if (!ok && !firstBad) { firstBad = key; }
        });
        if (firstBad) { input(firstBad).focus(); return; }

        submitBtn.disabled = true;
        submitBtn.classList.add('is-loading');

        var payload = JSON.stringify({
            login: input('email').value.trim(),
            password: input('password').value,
            name: input('name').value.trim()
        });

        fetch(BASE_URL + '/api/auth.php?action=register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: payload
        }).then(function (r) { return r.json(); }).then(function (data) {
            if (data && data.error) {
                showAlert(serverErrors[data.error] || ('Не удалось создать аккаунт: ' + data.error));
                if (data.error === 'invalid email') { setState('email', 'invalid', 'Проверьте адрес'); }
                if (data.error === 'password must be 8-72 characters') { setState('password', 'invalid', 'От 8 до 72 символов'); }
                return;
            }
            showDone(input('email').value.trim());
        }).catch(function () {
            showAlert('Нет связи с сервером. Проверьте подключение и попробуйте ещё раз.');
        }).then(function () {
            submitBtn.disabled = false;
            submitBtn.classList.remove('is-loading');
        });
    });

    function showDone(email) {
        var formStep = document.getElementById('signup-form-step');
        var doneStep = document.getElementById('signup-done-step');
        doneStep.querySelector('.done-email').textContent = email;
        formStep.classList.add('is-leaving');
        setTimeout(function () {
            formStep.hidden = true;
            doneStep.hidden = false;
            void doneStep.offsetWidth;
            doneStep.classList.add('is-active');
            var link = doneStep.querySelector('a');
            if (link) { link.focus({ preventScroll: true }); }
        }, reduceMotion ? 0 : 220);
    }
})();
</script>
</body>
</html>

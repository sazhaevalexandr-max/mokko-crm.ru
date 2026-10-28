/**
 * MOKKO CRM — раздел «Обновления и модули».
 *
 * Загрузка ZIP (multipart + XHR progress), предпросмотр, установка, откат,
 * управление модулями. Серверные проверки — в api/updates.php и lib/updater.php;
 * здесь только интерфейс.
 */
(function () {
    'use strict';

    var U = window.UPD || {};
    var state = U.state || {};
    var api = U.api || '';
    var csrf = U.csrf || '';
    var pkg = null;
    var busy = false;

    function $(id) {
        return document.getElementById(id);
    }

    function esc(value) {
        return String(value === null || value === undefined ? '' : value).replace(/[&<>"']/g, function (ch) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
        });
    }

    function fmtBytes(bytes) {
        bytes = Number(bytes) || 0;
        if (bytes >= 1048576) {
            return (Math.round(bytes / 1048576 * 10) / 10) + ' МБ';
        }
        if (bytes >= 1024) {
            return Math.round(bytes / 1024) + ' КБ';
        }
        return bytes + ' Б';
    }

    function fmtDate(value) {
        if (!value) {
            return '';
        }
        var date = new Date(value);
        if (isNaN(date.getTime())) {
            return String(value);
        }
        try {
            return date.toLocaleString('ru-RU', {
                day: '2-digit', month: '2-digit', year: 'numeric',
                hour: '2-digit', minute: '2-digit'
            });
        } catch (e) {
            return String(value);
        }
    }

    /* ── Toast ───────────────────────────────────────────────────────────── */

    var toastTimer = null;

    function toast(message, kind, ms) {
        var box = $('toast');
        if (!box) {
            return;
        }
        box.textContent = String(message);
        box.className = 'upd-toast is-visible' + (kind ? ' upd-toast--' + kind : '');
        box.hidden = false;

        if (toastTimer) {
            clearTimeout(toastTimer);
        }
        if (ms !== 0) {
            toastTimer = setTimeout(function () {
                box.classList.remove('is-visible');
            }, ms || 5000);
        }
    }

    /* ── HTTP ────────────────────────────────────────────────────────────── */

    function explainError(data, status) {
        if (data && data.message) {
            return data.message;
        }
        if (data && data.error === 'limit') {
            return 'Слишком много попыток. Попробуйте позже.';
        }
        if (data && data.error === 'invalid csrf token') {
            return 'Сессия устарела. Обновите страницу и повторите.';
        }
        if (data && data.error === 'maintenance') {
            return 'Включён режим обслуживания.';
        }
        if (data && data.error) {
            return data.error;
        }
        return 'Ошибка сервера (HTTP ' + status + ').';
    }

    function request(action, body) {
        return fetch(api + '?action=' + encodeURIComponent(action), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrf,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(body || {})
        }).then(function (response) {
            return response.text().then(function (text) {
                var data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    throw new Error('Сервер вернул не JSON (HTTP ' + response.status + ').');
                }
                if (!response.ok || data.ok === false) {
                    throw new Error(explainError(data, response.status));
                }
                return data;
            });
        });
    }

    /* ── Состояние ───────────────────────────────────────────────────────── */

    function syncState(next) {
        if (next) {
            state = next;
        }

        var version = state.version || '';
        if ($('top-version')) {
            $('top-version').textContent = 'v' + version;
        }
        if ($('fact-version')) {
            $('fact-version').textContent = version;
        }

        var modules = state.modules || [];
        var enabled = modules.filter(function (m) { return m.enabled; }).length;
        if ($('fact-modules')) {
            $('fact-modules').textContent = enabled + ' из ' + modules.length;
        }
        if ($('fact-signature')) {
            $('fact-signature').textContent = state.secret ? 'HMAC-ключ задан' : 'не настроена';
        }

        renderModules(modules);
        renderHistory(state.history || []);
    }

    function setVersion(version) {
        state.version = version;
        if ($('top-version')) {
            $('top-version').textContent = 'v' + version;
        }
        if ($('fact-version')) {
            $('fact-version').textContent = version;
        }
    }

    /* ── Модули ──────────────────────────────────────────────────────────── */

    function renderModules(modules) {
        var list = $('modules-list');
        if (!list) {
            return;
        }
        if ($('modules-count')) {
            $('modules-count').textContent = String(modules.length);
        }

        if (!modules.length) {
            list.innerHTML = '<div class="upd-empty">Модулей пока нет. Установите пакет с типом <code>module</code>.</div>';
            return;
        }

        list.innerHTML = modules.map(function (m) {
            var badge = m.error
                ? '<span class="upd-badge upd-badge--err">ошибка</span>'
                : (m.enabled
                    ? '<span class="upd-badge upd-badge--ok">включён</span>'
                    : '<span class="upd-badge">выключен</span>');

            var toggle = '<button type="button" class="upd-btn upd-btn--sm" data-act="toggle" data-slug="' + esc(m.slug) +
                '" data-enabled="' + (m.enabled ? '0' : '1') + '">' +
                (m.enabled ? 'Выключить' : 'Включить') + '</button>';

            return '<div class="upd-row">' +
                '<div class="upd-row-main">' +
                    '<div class="upd-row-title"><b>' + esc(m.title) + '</b>' +
                        '<span class="upd-badge">v' + esc(m.version) + '</span>' + badge +
                    '</div>' +
                    '<div class="upd-row-sub"><code>modules/' + esc(m.slug) + '</code>' +
                        (m.description ? ' — ' + esc(m.description) : '') +
                        (m.error ? ' — ' + esc(m.error) : '') +
                    '</div>' +
                '</div>' +
                '<div class="upd-row-actions">' + toggle +
                    '<button type="button" class="upd-btn upd-btn--sm upd-btn--danger" data-act="remove" data-slug="' + esc(m.slug) + '">Удалить</button>' +
                '</div>' +
            '</div>';
        }).join('');
    }

    /* ── История ─────────────────────────────────────────────────────────── */

    function statusBadge(status) {
        if (status === 'applied') {
            return '<span class="upd-badge upd-badge--ok">установлено</span>';
        }
        if (status === 'rolled_back') {
            return '<span class="upd-badge upd-badge--warn">откатано</span>';
        }
        if (status === 'failed') {
            return '<span class="upd-badge upd-badge--err">ошибка</span>';
        }
        return '<span class="upd-badge">' + esc(status) + '</span>';
    }

    function renderHistory(history) {
        var list = $('history-list');
        if (!list) {
            return;
        }
        if (!history.length) {
            list.innerHTML = '<div class="upd-empty">Операций пока не было.</div>';
            return;
        }

        list.innerHTML = history.map(function (item) {
            var kind = item.type === 'module' ? 'модуль' : 'обновление';
            var actions = '';

            if (item.can_rollback && item.token) {
                actions = '<button type="button" class="upd-btn upd-btn--sm" data-act="rollback" data-token="' + esc(item.token) +
                    '" data-title="' + esc(item.title) + '">Откатить</button>';
            }

            return '<div class="upd-row">' +
                '<div class="upd-row-main">' +
                    '<div class="upd-row-title"><b>' + esc(item.title || item.id) + '</b>' +
                        '<span class="upd-badge">v' + esc(item.version) + '</span>' +
                        statusBadge(item.status) +
                    '</div>' +
                    '<div class="upd-row-sub">' + esc(kind) + ' · ' +
                        esc(fmtDate(item.time)) + ' · файлов: ' + (Number(item.files) || 0) +
                        (item.error ? ' · ' + esc(item.error) : '') +
                    '</div>' +
                '</div>' +
                '<div class="upd-row-actions">' + actions + '</div>' +
            '</div>';
        }).join('');
    }

    /* ── Превью пакета ───────────────────────────────────────────────────── */

    function signatureBadge(signature, hasSecret) {
        if (signature === 'valid') {
            return '<span class="upd-badge upd-badge--ok">подпись верна</span>';
        }
        if (signature === 'unverified') {
            return '<span class="upd-badge upd-badge--warn">ключ не задан</span>';
        }
        if (signature === 'none' && hasSecret) {
            return '<span class="upd-badge upd-badge--warn">без подписи</span>';
        }
        return '<span class="upd-badge">без подписи</span>';
    }

    function renderPreview(data) {
        pkg = data;
        var m = data.manifest || {};
        var box = $('preview');
        if (!box) {
            return;
        }

        var isModule = m.type === 'module';
        var installedModule = null;
        if (isModule) {
            (state.modules || []).forEach(function (item) {
                if (item.slug === m.module) {
                    installedModule = item;
                }
            });
        }
        var sameVersion = isModule
            ? !!(installedModule && String(installedModule.version) === String(m.version))
            : String(m.version) === String(state.version);
        var target = isModule ? ('модуль ' + (m.module || '')) : 'ядро';
        var versionLine = isModule
            ? (installedModule ? installedModule.version + ' → ' + m.version : 'новая установка · ' + m.version)
            : state.version + ' → ' + m.version;

        var warnings = (data.warnings || []).map(function (text) {
            return '<li>' + esc(text) + '</li>';
        }).join('');

        var files = (data.files || []).map(function (file) {
            return '<div>' + esc(file.path) + ' <span style="opacity:.55">' + fmtBytes(file.size) + '</span></div>';
        }).join('');
        if (data.files_total > (data.files || []).length) {
            files += '<div style="opacity:.55">… ещё ' + (data.files_total - data.files.length) + ' файлов</div>';
        }

        box.innerHTML =
            '<div class="upd-preview-head">' +
                '<div>' +
                    '<h2 class="upd-preview-title">' + esc(m.title) + '</h2>' +
                    '<div class="upd-preview-sub">' + esc(m.id) + ' · ' + esc(target) + '</div>' +
                '</div>' +
                '<button type="button" class="upd-btn upd-btn--ghost upd-btn--sm" id="preview-cancel">Отменить</button>' +
            '</div>' +

            '<div class="upd-preview-grid">' +
                '<div class="upd-fact"><span>Версия</span><b>' + esc(versionLine) + '</b></div>' +
                '<div class="upd-fact"><span>Файлов</span><b>' + (Number(data.files_total) || 0) + '</b></div>' +
                '<div class="upd-fact"><span>Распаковано</span><b>' + fmtBytes(data.total) + '</b></div>' +
                '<div class="upd-fact"><span>SQL</span><b>' + (m.has_sql ? 'есть миграция' : 'нет') + '</b></div>' +
                '<div class="upd-fact"><span>Подпись</span><b>' + signatureBadge(data.signature, state.secret) + '</b></div>' +
            '</div>' +

            (warnings ? '<ul class="upd-warnings">' + warnings + '</ul>' : '') +
            '<div class="upd-filebox">' + files + '</div>' +

            '<div class="upd-options">' +
                '<label class="upd-check"><input type="checkbox" id="opt-maint" checked> Включить режим обслуживания на время установки</label>' +
                '<label class="upd-check"' + (m.has_sql ? '' : ' hidden') + '><input type="checkbox" id="opt-db" checked> Сделать дамп БД перед SQL-миграцией</label>' +
                '<label class="upd-check"' + (sameVersion ? '' : ' hidden') + '><input type="checkbox" id="opt-reinstall"> Переустановить ту же версию</label>' +
                '<label class="upd-check"' + (m.has_sql ? '' : ' hidden') + '><input type="checkbox" id="opt-force"> Продолжить без дампа БД, если mysqldump недоступен</label>' +
                '<label class="upd-check"><input type="checkbox" id="confirm-install"> Понимаю, что пакет изменит файлы на сервере</label>' +
            '</div>' +

            '<div class="upd-actions">' +
                '<button type="button" class="upd-btn upd-btn--primary" id="install-btn" disabled>Установить</button>' +
                '<span class="upd-hint" style="margin:0">Перед изменением файлов создаётся резервная копия.</span>' +
            '</div>';

        box.hidden = false;
        box.scrollIntoView({ behavior: 'smooth', block: 'start' });

        $('preview-cancel').addEventListener('click', clearPreview);
        $('confirm-install').addEventListener('change', function () {
            $('install-btn').disabled = !this.checked || busy;
        });
        $('install-btn').addEventListener('click', install);
    }

    function clearPreview() {
        pkg = null;
        var box = $('preview');
        if (box) {
            box.hidden = true;
            box.innerHTML = '';
        }
    }

    function install() {
        if (!pkg || busy) {
            return;
        }

        var btn = $('install-btn');
        busy = true;
        btn.disabled = true;
        btn.textContent = 'Установка…';
        toast('Устанавливаем пакет. Не закрывайте страницу — это может занять до минуты.', '', 0);

        request('apply', {
            token: pkg.token,
            maintenance: !!(($('opt-maint') || {}).checked),
            db_backup: !!(($('opt-db') || {}).checked),
            allow_reinstall: !!(($('opt-reinstall') || {}).checked),
            force_db: !!(($('opt-force') || {}).checked)
        }).then(function (data) {
            busy = false;
            syncState(data.state);

            var r = data.result || {};
            if (r.type === 'update' && r.version) {
                setVersion(r.version);
            }
            var box = $('preview');
            box.innerHTML =
                '<div class="upd-preview-head">' +
                    '<div>' +
                        '<h2 class="upd-preview-title">Готово: ' + esc(r.title || '') + '</h2>' +
                        '<div class="upd-preview-sub">версия ' + esc(r.version || '') + ' · файлов: ' + (Number(r.files) || 0) +
                            (r.sql ? ' · SQL-операторов: ' + r.sql : '') +
                            ' · ' + (Number(r.duration) || 0) + ' с</div>' +
                    '</div>' +
                '</div>' +
                '<p class="upd-hint">Резервная копия: <code>' + esc(r.backup || '') + '</code>. ' +
                    'Откат доступен в истории ниже.' +
                    (r.warning ? ' <br><b>Внимание:</b> ' + esc(r.warning) : '') + '</p>' +
                '<div class="upd-actions">' +
                    '<button type="button" class="upd-btn upd-btn--primary" id="reload-page">Обновить страницу</button>' +
                    '<button type="button" class="upd-btn" id="close-preview">Остаться</button>' +
                '</div>';

            $('reload-page').addEventListener('click', function () { window.location.reload(); });
            $('close-preview').addEventListener('click', clearPreview);

            toast('Пакет установлен: ' + (r.title || '') + ' v' + (r.version || ''), 'ok', 8000);
        }).catch(function (error) {
            busy = false;
            btn.disabled = false;
            btn.textContent = 'Установить';
            toast(error.message || String(error), 'err', 15000);
        });
    }

    /* ── Загрузка ────────────────────────────────────────────────────────── */

    function upload(file) {
        if (!file || busy) {
            return;
        }
        if (!/\.zip$/i.test(file.name)) {
            toast('Нужен файл с расширением .zip.', 'err');
            return;
        }
        if (U.maxUpload && file.size > U.maxUpload) {
            toast('Архив больше ' + fmtBytes(U.maxUpload) + '.', 'err');
            return;
        }

        busy = true;
        var progress = $('upload');
        var bar = $('upload-bar');
        var pct = $('upload-pct');

        progress.hidden = false;
        bar.style.width = '0%';
        pct.textContent = '0%';
        $('upload-name').textContent = file.name + ' · ' + fmtBytes(file.size);

        var form = new FormData();
        form.append('package', file);
        form.append('csrf', csrf);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', api + '?action=inspect', true);
        xhr.withCredentials = true;
        xhr.setRequestHeader('X-CSRF-Token', csrf);

        xhr.upload.addEventListener('progress', function (event) {
            if (!event.lengthComputable) {
                return;
            }
            var value = Math.min(99, Math.round(event.loaded / event.total * 100));
            bar.style.width = value + '%';
            pct.textContent = value + '%';
        });

        xhr.addEventListener('load', function () {
            busy = false;
            bar.style.width = '100%';
            pct.textContent = '100%';

            var data = null;
            try {
                data = JSON.parse(xhr.responseText);
            } catch (e) {
                data = null;
            }

            if (xhr.status >= 200 && xhr.status < 300 && data && data.ok) {
                setTimeout(function () {
                    progress.hidden = true;
                    $('package').value = '';
                    renderPreview(data.package);
                }, 250);
                return;
            }

            setTimeout(function () { progress.hidden = true; }, 600);
            toast(explainError(data, xhr.status), 'err', 12000);
        });

        xhr.addEventListener('error', function () {
            busy = false;
            progress.hidden = true;
            toast('Не удалось загрузить файл. Проверьте соединение.', 'err', 10000);
        });

        xhr.send(form);
    }

    /* ── Модальное окно ──────────────────────────────────────────────────── */

    var modalResolve = null;

    function openModal(options) {
        return new Promise(function (resolve) {
            modalResolve = resolve;

            $('modal-title').textContent = options.title || '';
            $('modal-text').innerHTML = options.html || ('<p>' + esc(options.text || '') + '</p>');

            var wrap = $('modal-check-wrap');
            var check = $('modal-check');
            if (options.checkLabel) {
                wrap.hidden = false;
                check.checked = !!options.checkChecked;
                $('modal-check-label').textContent = options.checkLabel;
            } else {
                wrap.hidden = true;
                check.checked = false;
            }

            var ok = $('modal-ok');
            ok.textContent = options.okLabel || 'Подтвердить';
            ok.className = 'upd-btn ' + (options.danger === false ? 'upd-btn--primary' : 'upd-btn--danger');

            $('modal').hidden = false;
            document.body.style.overflow = 'hidden';
            ok.focus();
        });
    }

    function closeModal(value) {
        $('modal').hidden = true;
        document.body.style.overflow = '';
        if (modalResolve) {
            var resolve = modalResolve;
            modalResolve = null;
            resolve(value);
        }
    }

    function modalValue() {
        var checked = $('modal-check-wrap').hidden ? false : $('modal-check').checked;
        return { checked: checked };
    }

    /* ── Действия с историей и модулями ──────────────────────────────────── */

    function rollback(token, title) {
        openModal({
            title: 'Откатить «' + title + '»?',
            html: '<p>Файлы будут возвращены к состоянию до установки. Это действие нельзя отменить.</p>' +
                '<p>Если после обновления менялись файлы вручную, откат будет отклонён.</p>',
            checkLabel: 'Восстановить БД из дампа (отменит данные, созданные после обновления)',
            okLabel: 'Откатить',
            danger: true
        }).then(function (result) {
            if (!result) {
                return;
            }
            request('rollback', { token: token, restore_db: result.checked, force: false })
                .then(function (data) {
                    syncState(data.state);
                    if (data.result && data.result.version) {
                        setVersion(data.result.version);
                    }
                    toast((data.result && data.result.message) || 'Откат выполнен.', 'ok', 9000);
                })
                .catch(function (error) {
                    if (/изменены файлы/i.test(error.message || '')) {
                        return openModal({
                            title: 'Файлы изменены после обновления',
                            html: '<p>' + esc(error.message) + '</p><p>Принудительный откат перезапишет эти правки.</p>',
                            okLabel: 'Всё равно откатить',
                            danger: true
                        }).then(function (again) {
                            if (!again) {
                                return;
                            }
                            return request('rollback', { token: token, restore_db: false, force: true })
                                .then(function (data) {
                                    syncState(data.state);
                                    toast('Откат выполнен принудительно.', 'ok', 9000);
                                });
                        });
                    }
                    throw error;
                })
                .catch(function (error) {
                    toast(error.message || String(error), 'err', 12000);
                });
        });
    }

    /* ── Инициализация ───────────────────────────────────────────────────── */

    document.addEventListener('DOMContentLoaded', function () {
        syncState(state);

        var drop = $('drop');
        var input = $('package');

        if (drop && input) {
            drop.addEventListener('click', function () {
                if (U.canUpload && !busy) {
                    input.click();
                }
            });
            drop.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    drop.click();
                }
            });

            input.addEventListener('change', function () {
                if (this.files && this.files[0]) {
                    upload(this.files[0]);
                }
            });

            ['dragenter', 'dragover'].forEach(function (name) {
                drop.addEventListener(name, function (event) {
                    event.preventDefault();
                    drop.classList.add('is-over');
                });
            });
            ['dragleave', 'drop'].forEach(function (name) {
                drop.addEventListener(name, function (event) {
                    event.preventDefault();
                    drop.classList.remove('is-over');
                });
            });
            drop.addEventListener('drop', function (event) {
                if (event.dataTransfer && event.dataTransfer.files && event.dataTransfer.files[0]) {
                    upload(event.dataTransfer.files[0]);
                }
            });
        }

        var modulesList = $('modules-list');
        if (modulesList) {
            modulesList.addEventListener('click', function (event) {
                var button = event.target.closest('button[data-act]');
                if (!button) {
                    return;
                }
                var slug = button.getAttribute('data-slug');

                if (button.getAttribute('data-act') === 'toggle') {
                    var enabled = button.getAttribute('data-enabled') === '1';
                    button.disabled = true;
                    request('module_state', { slug: slug, enabled: enabled })
                        .then(function (data) {
                            syncState(data.state);
                            toast(enabled ? 'Модуль включён.' : 'Модуль выключен.', 'ok');
                        })
                        .catch(function (error) {
                            button.disabled = false;
                            toast(error.message || String(error), 'err', 10000);
                        });
                    return;
                }

                if (button.getAttribute('data-act') === 'remove') {
                    openModal({
                        title: 'Удалить модуль?',
                        html: '<p>Каталог <code>modules/' + esc(slug) + '</code> будет перемещён в ' +
                            '<code>storage/updates/removed</code>. Данные модуля в БД останутся.</p>',
                        okLabel: 'Удалить',
                        danger: true
                    }).then(function (result) {
                        if (!result) {
                            return;
                        }
                        request('module_remove', { slug: slug })
                            .then(function (data) {
                                syncState(data.state);
                                toast(data.message || 'Модуль удалён.', 'ok', 8000);
                            })
                            .catch(function (error) {
                                toast(error.message || String(error), 'err', 10000);
                            });
                    });
                }
            });
        }

        var historyList = $('history-list');
        if (historyList) {
            historyList.addEventListener('click', function (event) {
                var button = event.target.closest('button[data-act="rollback"]');
                if (!button) {
                    return;
                }
                rollback(button.getAttribute('data-token'), button.getAttribute('data-title') || 'обновление');
            });
        }

        var maintenanceOff = $('maintenance-off');
        if (maintenanceOff) {
            maintenanceOff.addEventListener('click', function () {
                request('maintenance_end', {})
                    .then(function (data) {
                        syncState(data.state);
                        toast('Режим обслуживания отключён.', 'ok');
                        setTimeout(function () { window.location.reload(); }, 600);
                    })
                    .catch(function (error) {
                        toast(error.message || String(error), 'err', 10000);
                    });
            });
        }

        var secretCreate = $('secret-create');
        if (secretCreate) {
            secretCreate.addEventListener('click', function () {
                openModal({
                    title: 'Создать ключ подписи?',
                    html: '<p>Будет создан <code>config/update.secret</code>. Передайте его сборщику ' +
                        'пакетов: <code>php tools/build-update.php src dist.zip --secret=...</code>.</p>' +
                        '<p>Храните ключ в тайне: с ним можно подписать любой пакет.</p>',
                    okLabel: 'Создать',
                    danger: false
                }).then(function (result) {
                    if (!result) {
                        return;
                    }
                    request('secret_create', {})
                        .then(function (data) {
                            syncState(data.state);
                            return openModal({
                                title: 'Ключ создан',
                                html: '<p>Сохраните ключ в надёжном месте — он показывается один раз. ' +
                                    'Передайте его сборщику:</p>' +
                                    '<div class="upd-filebox" style="max-height:none;white-space:pre-wrap">' + esc(data.secret) + '</div>' +
                                    '<p>С ним можно подписать любой пакет, поэтому не публикуйте его.</p>',
                                okLabel: 'Я сохранил ключ',
                                danger: false
                            }).then(function () {
                                window.location.reload();
                            });
                        })
                        .catch(function (error) {
                            toast(error.message || String(error), 'err', 10000);
                        });
                });
            });
        }

        $('modal-cancel').addEventListener('click', function () { closeModal(null); });
        $('modal-ok').addEventListener('click', function () { closeModal(modalValue()); });
        $('modal').addEventListener('click', function (event) {
            if (event.target === $('modal')) {
                closeModal(null);
            }
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !$('modal').hidden) {
                closeModal(null);
            }
        });
    });
})();

/**
 * MOKKO CRM — единый клиент CRM API.
 *
 * Новый код использует MokkoApi.request(module, action, payload).
 * Старые вызовы панели пока приходят в формате api://module/action/{json}:
 * этот формат перехватывается jQuery-адаптером и уходит на локальный
 * /api/crm.php POST-запросом. Внешнего mokkoapi.azurewebsites.net больше нет.
 */
(function (window, $) {
    'use strict';

    var BASE = typeof window.BASE_URL === 'string' ? window.BASE_URL : '';
    var PROTOCOL = 'api://';

    function parseLegacyUrl(url) {
        var rest = url.slice(PROTOCOL.length).replace(/^\/+/, '');

        // JSON-полезная нагрузка начинается с первого '{' (или '%7B'); всё до неё —
        // это путь module[/action]. Так корректно разбираются и вызовы без action
        // (api://startshift/{...}, api://shift/{...}): раньше JSON по счёту слэшей
        // ошибочно попадал в action, а payload оставался пустым, и сервер отвечал
        // «invalid place». Поиск по '{' заодно устойчив к слэшам внутри JSON.
        var braceIdx = rest.search(/\{|%7B/i);
        var pathPart = (braceIdx < 0 ? rest : rest.slice(0, braceIdx)).replace(/\/+$/, '');
        var json = braceIdx < 0 ? '' : rest.slice(braceIdx);

        var segs = pathPart.split('/').filter(function (s) { return s !== ''; });
        var module = segs.length > 0 ? segs[0] : '';
        var action = segs.length > 1 ? segs[1] : 'get';

        var payload = {};
        if (json !== '') {
            try {
                payload = JSON.parse(decodeURIComponent(json));
            } catch (e) {
                payload = {};
            }
        }

        return {
            module: module,
            action: action || 'get',
            payload: payload
        };
    }

    function endpoint(module, action) {
        return BASE + '/api/crm.php?module=' + encodeURIComponent(module) + '&action=' + encodeURIComponent(action);
    }

    function request(module, action, payload) {
        return fetch(endpoint(module, action), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload || {})
        }).then(function (response) {
            return response.text().then(function (text) {
                var data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    throw new Error('Сервер вернул не JSON (HTTP ' + response.status + ').');
                }
                if (!response.ok || (data && data.error)) {
                    var message = data && data.error ? String(data.error) : 'HTTP ' + response.status;
                    throw new Error(message);
                }
                return data;
            });
        });
    }

    // Старый код панели использует $.ajax с api://-адресами.
    if ($ && $.ajaxPrefilter) {
        $.ajaxPrefilter(function (options) {
            var url = options.url || '';
            if (url.indexOf(PROTOCOL) !== 0) {
                return;
            }

            var parsed = parseLegacyUrl(url);
            options.type = 'POST';
            options.url = endpoint(parsed.module, parsed.action);
            options.contentType = 'application/json';
            options.data = JSON.stringify(parsed.payload);
            options.dataType = 'json';
        });
    }

    window.MokkoApi = {
        request: request,
        parseLegacyUrl: parseLegacyUrl,
        endpoint: endpoint
    };
})(window, window.jQuery);

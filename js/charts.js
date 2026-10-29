/* MOKKO CRM — графики статистики.
   Свои SVG-графики: в стиле сайта, на русском,
   без внешней загрузки. */
(function (window) {
    'use strict';

    var NS = 'http://www.w3.org/2000/svg';
    var MONTHS = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
    var MONTHS_SHORT = ['янв', 'фев', 'мар', 'апр', 'мая', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'];
    var DAYS = ['воскресенье', 'понедельник', 'вторник', 'среда', 'четверг', 'пятница', 'суббота'];
    // Тёплая палитра под тёмную тему: от акцента к приглушённым тонам
    var PALETTE = ['#d9a46a', '#7fb8a4', '#c9775a', '#8fa6c9', '#e3cfa8', '#a98bb8', '#6b625a'];

    function esc(v) {
        return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    function num(v) { var n = Number(v); return isFinite(n) ? n : 0; }
    function money(v) { return Math.round(num(v)).toLocaleString('ru-RU') + ' ₽'; }
    function short(v) {
        v = num(v);
        if (v >= 1e6) { return (v / 1e6).toFixed(v >= 1e7 ? 0 : 1).replace('.', ',') + ' млн'; }
        if (v >= 1e3) { return (v / 1e3).toFixed(v >= 1e4 ? 0 : 1).replace('.', ',').replace(',0', '') + ' тыс'; }
        return String(Math.round(v));
    }
    function plural(n, one, few, many) {
        n = Math.abs(n) % 100; var n1 = n % 10;
        if (n > 10 && n < 20) { return many; }
        if (n1 > 1 && n1 < 5) { return few; }
        return n1 === 1 ? one : many;
    }
    function el(tag, attrs) {
        var e = document.createElementNS(NS, tag);
        for (var k in attrs) { if (attrs.hasOwnProperty(k)) { e.setAttribute(k, attrs[k]); } }
        return e;
    }
    function reduced() {
        return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }
    function toDate(v) {
        if (v instanceof Date) { return v; }
        // «2026-10-27» — локальная дата, без сдвига часового пояса
        var m = /^(\d{4})-(\d{2})-(\d{2})/.exec(String(v));
        return m ? new Date(+m[1], +m[2] - 1, +m[3]) : new Date(v);
    }
    function empty(box, text) {
        box.innerHTML = '<div class="mk-chart-empty">' + esc(text) + '</div>';
    }

    // ── Кольцо: доля продаж по позициям ─────────────────────────────────────
    function donut(box, rows) {
        if (!box) { return; }
        var items = [];
        (rows || []).forEach(function (r) {
            var q = num(r[1]);
            if (q > 0) { items.push({ name: r[0] ? String(r[0]) : 'Без названия', q: q }); }
        });
        if (!items.length) { empty(box, 'За этот период продаж нет'); return; }
        items.sort(function (a, b) { return b.q - a.q; });
        if (items.length > 6) {
            var rest = items.splice(5);
            items.push({ name: 'Остальное · ' + rest.length + ' ' + plural(rest.length, 'позиция', 'позиции', 'позиций'), q: rest.reduce(function (s, i) { return s + i.q; }, 0), other: true });
        }
        var total = items.reduce(function (s, i) { return s + i.q; }, 0);

        var R = 15.9155, C = 100; // окружность длиной 100 — доли сразу в процентах
        var svg = el('svg', { viewBox: '0 0 42 42', 'class': 'mk-donut-svg', role: 'img', 'aria-label': 'Доля продаж по позициям' });
        svg.appendChild(el('circle', { cx: 21, cy: 21, r: R, 'class': 'mk-donut-track' }));
        var offset = 0, gap = items.length > 1 ? 0.6 : 0;
        items.forEach(function (it, i) {
            it.pct = it.q / total * 100;
            it.color = it.other ? PALETTE[6] : PALETTE[i % 6];
            var len = Math.max(0.01, it.pct - gap);
            var c = el('circle', {
                cx: 21, cy: 21, r: R, 'class': 'mk-donut-seg', 'data-i': i,
                stroke: it.color,
                'stroke-dasharray': (reduced() ? len : 0) + ' ' + C,
                'stroke-dashoffset': String(-offset)
            });
            c.style.transitionDelay = (i * 70) + 'ms';
            c._len = len;
            svg.appendChild(c);
            offset += it.pct;
        });

        var legend = items.map(function (it, i) {
            var pct = it.pct < 1 ? '<1' : Math.round(it.pct);
            return '<li data-i="' + i + '"><i style="background:' + it.color + '"></i>' +
                '<span class="mk-l-name">' + esc(it.name) + '</span>' +
                '<span class="mk-l-q">' + it.q.toLocaleString('ru-RU') + ' шт.</span>' +
                '<b class="mk-l-pct">' + pct + '%</b></li>';
        }).join('');

        box.innerHTML =
            '<div class="mk-donut">' +
              '<div class="mk-donut-figure"><div class="mk-donut-center"><b>' + total.toLocaleString('ru-RU') + '</b><span>' + plural(total, 'продажа', 'продажи', 'продаж') + '</span></div></div>' +
              '<ul class="mk-donut-legend">' + legend + '</ul>' +
            '</div>';
        box.querySelector('.mk-donut-figure').insertBefore(svg, box.querySelector('.mk-donut-center'));

        if (!reduced()) {
            requestAnimationFrame(function () {
                requestAnimationFrame(function () {
                    [].forEach.call(svg.querySelectorAll('.mk-donut-seg'), function (c) {
                        c.setAttribute('stroke-dasharray', c._len + ' ' + C);
                    });
                });
            });
        }

        // Наведение на строку легенды подсвечивает сегмент, и наоборот
        var wrap = box.querySelector('.mk-donut');
        function focus(i) {
            wrap.classList.toggle('has-focus', i !== null);
            [].forEach.call(wrap.querySelectorAll('[data-i]'), function (n) {
                n.classList.toggle('is-focus', i !== null && n.getAttribute('data-i') === String(i));
            });
        }
        wrap.addEventListener('mouseover', function (e) {
            var t = e.target.closest ? e.target.closest('[data-i]') : null;
            focus(t ? t.getAttribute('data-i') : null);
        });
        wrap.addEventListener('mouseleave', function () { focus(null); });
    }

    // ── Линии: выручка и средний чек по дням ───────────────────────────────
    // Монотонная кривая (Fritsch–Carlson): плавная, но не уходит ниже нуля
    // и не рисует пиков, которых не было, — в отличие от сглаженного сплайна.
    function monotone(pts) {
        var n = pts.length;
        if (n < 2) { return ''; }
        if (n === 2) { return 'M' + pts[0][0] + ',' + pts[0][1] + 'L' + pts[1][0] + ',' + pts[1][1]; }
        var dx = [], m = [], t = [];
        for (var i = 0; i < n - 1; i++) {
            dx[i] = pts[i + 1][0] - pts[i][0];
            m[i] = (pts[i + 1][1] - pts[i][1]) / dx[i];
        }
        t[0] = m[0]; t[n - 1] = m[n - 2];
        for (i = 1; i < n - 1; i++) { t[i] = m[i - 1] * m[i] <= 0 ? 0 : (m[i - 1] + m[i]) / 2; }
        for (i = 0; i < n - 1; i++) {
            if (m[i] === 0) { t[i] = t[i + 1] = 0; continue; }
            var a = t[i] / m[i], b = t[i + 1] / m[i], s = a * a + b * b;
            if (s > 9) { var k = 3 / Math.sqrt(s); t[i] = k * a * m[i]; t[i + 1] = k * b * m[i]; }
        }
        var d = 'M' + pts[0][0].toFixed(1) + ',' + pts[0][1].toFixed(1);
        for (i = 0; i < n - 1; i++) {
            var h = dx[i] / 3;
            d += 'C' + (pts[i][0] + h).toFixed(1) + ',' + (pts[i][1] + h * t[i]).toFixed(1) + ' ' +
                (pts[i + 1][0] - h).toFixed(1) + ',' + (pts[i + 1][1] - h * t[i + 1]).toFixed(1) + ' ' +
                pts[i + 1][0].toFixed(1) + ',' + pts[i + 1][1].toFixed(1);
        }
        return d;
    }
    function niceMax(v) {
        if (v <= 0) { return 1; }
        var p = Math.pow(10, Math.floor(Math.log(v) / Math.LN10)), f = v / p;
        return (f <= 1 ? 1 : f <= 2 ? 2 : f <= 2.5 ? 2.5 : f <= 5 ? 5 : 10) * p;
    }

    var lastDays = null;
    function days(box, rows) {
        if (!box) { return; }
        lastDays = { box: box, rows: rows };
        var data = (rows || []).map(function (r) {
            return { d: toDate(r[0]), avg: num(r[2]), sum: num(r[3]) };
        }).filter(function (r) { return !isNaN(r.d); });
        data.sort(function (a, b) { return a.d - b.d; });
        if (!data.length) { empty(box, 'За этот период выручки нет'); return; }

        var W = Math.max(280, box.clientWidth || 600);
        var H = W < 560 ? 240 : 300;
        var pad = { t: 16, r: 14, b: 30, l: 52 };
        var iw = W - pad.l - pad.r, ih = H - pad.t - pad.b;
        var n = data.length;
        var maxSum;
        // Одна шкала на обе линии: средний чек честно виден относительно выручки
        var maxAvg = maxSum = niceMax(Math.max.apply(null, data.map(function (r) { return Math.max(r.sum, r.avg); })));
        var X = function (i) { return pad.l + (n === 1 ? iw / 2 : iw * i / (n - 1)); };
        var Ys = function (v) { return pad.t + ih - v / maxSum * ih; };
        var Ya = function (v) { return pad.t + ih - v / maxAvg * ih; };

        var svg = el('svg', { viewBox: '0 0 ' + W + ' ' + H, width: W, height: H, 'class': 'mk-line-svg', role: 'img', 'aria-label': 'Выручка и средний чек по дням' });
        var defs = el('defs', {});
        var grad = el('linearGradient', { id: 'mkArea', x1: 0, y1: 0, x2: 0, y2: 1 });
        grad.appendChild(el('stop', { offset: '0', 'stop-color': PALETTE[0], 'stop-opacity': '.28' }));
        grad.appendChild(el('stop', { offset: '1', 'stop-color': PALETTE[0], 'stop-opacity': '0' }));
        defs.appendChild(grad); svg.appendChild(defs);

        // Сетка и подписи оси выручки
        for (var g = 0; g <= 4; g++) {
            var v = maxSum * g / 4, y = Ys(v);
            svg.appendChild(el('line', { x1: pad.l, x2: W - pad.r, y1: y, y2: y, 'class': g ? 'mk-grid' : 'mk-axis' }));
            var lab = el('text', { x: pad.l - 10, y: y + 4, 'class': 'mk-tick', 'text-anchor': 'end' });
            lab.textContent = g ? short(v) : '0';
            svg.appendChild(lab);
        }
        // Подписи дат: не больше, чем помещается
        var step = Math.max(1, Math.ceil(n / Math.max(2, Math.floor(iw / 72))));
        data.forEach(function (r, i) {
            if (i % step && i !== n - 1) { return; }
            if (i === n - 1 && i % step && n - 1 - (i - i % step) < step / 2) { return; }
            var t = el('text', { x: X(i), y: H - 8, 'class': 'mk-tick', 'text-anchor': n === 1 ? 'middle' : i === 0 ? 'start' : i === n - 1 ? 'end' : 'middle' });
            t.textContent = r.d.getDate() + ' ' + MONTHS_SHORT[r.d.getMonth()];
            svg.appendChild(t);
        });

        var ps = data.map(function (r, i) { return [X(i), Ys(r.sum)]; });
        var pa = data.map(function (r, i) { return [X(i), Ya(r.avg)]; });
        if (n > 1) {
            var line = monotone(ps);
            svg.appendChild(el('path', { d: line + 'L' + ps[n - 1][0] + ',' + Ys(0) + 'L' + ps[0][0] + ',' + Ys(0) + 'Z', 'class': 'mk-area', fill: 'url(#mkArea)' }));
            svg.appendChild(el('path', { d: monotone(pa), 'class': 'mk-l mk-l-avg', stroke: PALETTE[1], pathLength: 1 }));
            svg.appendChild(el('path', { d: line, 'class': 'mk-l mk-l-sum', stroke: PALETTE[0], pathLength: 1 }));
        }
        // Точки видны при малом числе дней, иначе — только под курсором
        var dotsAlways = n <= 14;
        var dots = data.map(function (r, i) {
            var a = el('circle', { cx: pa[i][0], cy: pa[i][1], r: 3.5, 'class': 'mk-dot' + (dotsAlways ? ' is-on' : ''), stroke: PALETTE[1] });
            var s = el('circle', { cx: ps[i][0], cy: ps[i][1], r: 4, 'class': 'mk-dot' + (dotsAlways ? ' is-on' : ''), stroke: PALETTE[0] });
            svg.appendChild(a); svg.appendChild(s);
            return [a, s];
        });
        var guide = el('line', { y1: pad.t, y2: pad.t + ih, 'class': 'mk-guide' });
        svg.insertBefore(guide, svg.querySelector('.mk-area') || null);

        box.innerHTML = '';
        var stage = document.createElement('div');
        stage.className = 'mk-line';
        stage.appendChild(svg);
        var tip = document.createElement('div');
        tip.className = 'mk-tip';
        stage.appendChild(tip);
        box.appendChild(stage);

        var active = -1;
        function show(i) {
            if (i === active) { return; }
            if (active > -1) { dots[active][0].classList.remove('is-hover'); dots[active][1].classList.remove('is-hover'); }
            active = i;
            if (i < 0) { stage.classList.remove('is-hover'); return; }
            var r = data[i];
            dots[i][0].classList.add('is-hover'); dots[i][1].classList.add('is-hover');
            guide.setAttribute('x1', X(i)); guide.setAttribute('x2', X(i));
            tip.innerHTML = '<b>' + DAYS[r.d.getDay()] + ', ' + r.d.getDate() + ' ' + MONTHS[r.d.getMonth()] + '</b>' +
                '<span><i style="background:' + PALETTE[0] + '"></i>Выручка<em>' + money(r.sum) + '</em></span>' +
                '<span><i style="background:' + PALETTE[1] + '"></i>Средний чек<em>' + money(r.avg) + '</em></span>';
            var left = X(i) / W * 100;
            tip.style.left = left + '%';
            tip.style.top = (Math.min(ps[i][1], pa[i][1]) / H * 100) + '%';
            tip.classList.toggle('is-left', left > 55);
            tip.classList.toggle('is-right', left <= 55);
            stage.classList.add('is-hover');
        }
        stage.addEventListener('mousemove', function (e) {
            var rect = svg.getBoundingClientRect();
            var x = (e.clientX - rect.left) / rect.width * W;
            var i = n === 1 ? 0 : Math.round((x - pad.l) / iw * (n - 1));
            show(Math.max(0, Math.min(n - 1, i)));
        });
        stage.addEventListener('mouseleave', function () { show(-1); });
        stage.addEventListener('touchstart', function (e) {
            var rect = svg.getBoundingClientRect(), tch = e.touches[0];
            var x = (tch.clientX - rect.left) / rect.width * W;
            show(Math.max(0, Math.min(n - 1, n === 1 ? 0 : Math.round((x - pad.l) / iw * (n - 1)))));
        }, { passive: true });
    }

    // Перерисовка при изменении ширины (поворот телефона, сворачивание меню)
    var rt;
    window.addEventListener('resize', function () {
        clearTimeout(rt);
        rt = setTimeout(function () {
            if (lastDays && lastDays.box.offsetParent !== null) {
                var w = lastDays.box.clientWidth, svg = lastDays.box.querySelector('svg');
                if (!svg || Math.abs(svg.getAttribute('width') - w) > 4) {
                    lastDays.box.classList.add('no-anim');
                    days(lastDays.box, lastDays.rows);
                }
            }
        }, 150);
    });

    window.MokkoCharts = { donut: donut, days: function (box, rows) { if (box) { box.classList.remove('no-anim'); } days(box, rows); } };
})(window);

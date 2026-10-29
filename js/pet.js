/**
 * MOKKO CRM — тамагочи-кот кассы.
 *
 * Изолированный классический модуль (window.MokkoPet). Касса о нём не знает:
 * вызывается по клику на мордочку в бургер-меню (.pet-face-btn), рисует
 * плавающую панель с состоянием, кнопками и анимированным котом.
 *
 * Анимации — спрайты в images/:
 *   pet-content/joy/sad/dirty.png — состояния (1 кадр, оживляются CSS-движением);
 *   pet-eat.png, pet-sleep.png    — покадровые листы 4×134px (CSS steps(4)).
 * Состояние — в localStorage станции; спад — по РЕАЛЬНОМУ времени.
 */
(function (window, $) {
    'use strict';

    var KEY = 'mokko_pet_v1';
    var BASE = typeof window.BASE_URL === 'string' ? window.BASE_URL : '';
    var H = 3600000;
    var SPV = '?v=1';

    var DECAY = { food: 10, mood: 7, clean: 8 };

    function now() { return Date.now(); }
    function clamp(v) { return v < 0 ? 0 : (v > 100 ? 100 : v); }
    function round(v) { return Math.round(v); }

    function fresh() { return { food: 80, mood: 80, clean: 90, ts: now(), name: 'Кот', born: now() }; }

    function load() {
        var s = null;
        try { var raw = window.localStorage.getItem(KEY); if (raw) { s = JSON.parse(raw); } } catch (e) { s = null; }
        if (!s || typeof s !== 'object') { s = fresh(); }
        ['food', 'mood', 'clean'].forEach(function (k) { if (typeof s[k] !== 'number' || isNaN(s[k])) { s[k] = 60; } });
        if (typeof s.ts !== 'number') { s.ts = now(); }
        if (!s.name) { s.name = 'Кот'; }
        if (!s.born) { s.born = now(); }
        return s;
    }
    function save(s) { try { window.localStorage.setItem(KEY, JSON.stringify(s)); } catch (e) {} }

    function tick(s) {
        var dtH = (now() - s.ts) / H;
        if (dtH <= 0) { s.ts = now(); return s; }
        var hungry = s.food < 25, dirty = s.clean < 25;
        s.food = clamp(s.food - DECAY.food * dtH);
        s.clean = clamp(s.clean - DECAY.clean * dtH);
        s.mood = clamp(s.mood - (DECAY.mood + (hungry ? 6 : 0) + (dirty ? 5 : 0)) * dtH);
        s.ts = now();
        return s;
    }

    function poopCount(s) { return s.clean >= 66 ? 0 : (s.clean >= 33 ? 1 : 2); }
    function needsAttention(s) { return Math.min(s.food, s.mood, s.clean) < 30; }

    function statusOf(s) {
        if (s.clean < 30) { return { t: 'Надо убрать', cls: 'bad' }; }
        if (s.food < 25) { return { t: 'Голодный', cls: 'bad' }; }
        if (s.mood < 25) { return { t: 'Скучает', cls: 'warn' }; }
        if (s.food > 70 && s.mood > 70 && s.clean > 70) { return { t: 'Мурлычет', cls: 'good' }; }
        return { t: 'Всё хорошо', cls: 'ok' };
    }
    function moodClass(s) {
        var m = Math.min(s.food, s.mood, s.clean);
        if (m < 25) { return 'pet-sad'; }
        if (m > 70) { return 'pet-happy'; }
        return 'pet-ok';
    }
    function displayState(s) {
        if (s.clean < 30) { return 'dirty'; }
        if (Math.min(s.food, s.mood, s.clean) < 25) { return 'sad'; }
        if (s.food > 70 && s.mood > 70 && s.clean > 70) { return 'joy'; }
        return 'content';
    }

    function injectCss() {
        if (document.getElementById('mokko-pet-css')) { return; }
        var css =
        // ── мордочка в бургер-меню ──
        '.pet-face-btn .pet-ico{position:relative;display:inline-flex;vertical-align:middle;margin-right:9px}' +
        '.pet-face-btn .pet-ico img{image-rendering:auto;display:block}' +
        '.pet-face-btn .pet-dot{position:absolute;top:-3px;right:-3px;width:9px;height:9px;border-radius:50%;' +
        'background:#e5634d;border:2px solid var(--surface,#171310);opacity:0;transform:scale(.4);transition:opacity .2s,transform .2s}' +
        '.pet-face-btn.needs .pet-dot{opacity:1;transform:scale(1);animation:petPulse 1.6s ease-in-out infinite}' +
        '@keyframes petPulse{0%,100%{box-shadow:0 0 0 0 rgba(229,99,77,.5)}50%{box-shadow:0 0 0 5px rgba(229,99,77,0)}}' +

        // ── панель ──
        '.pet-panel{position:fixed;left:16px;bottom:16px;z-index:4000;width:260px;max-width:calc(100vw - 32px);' +
        'background:var(--surface,#171310);color:var(--text,#f2ece4);border:1px solid var(--border,#2a231d);' +
        'border-radius:var(--radius-sm,14px);box-shadow:0 18px 50px rgba(0,0,0,.55);padding:14px 14px 12px;' +
        'font-family:var(--font,Manrope,-apple-system,Segoe UI,Roboto,Arial,sans-serif);' +
        'opacity:0;transform:translateY(8px) scale(.98);transition:opacity .16s ease,transform .16s ease}' +
        '.pet-panel.open{opacity:1;transform:none}' +
        '.pet-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px}' +
        '.pet-name{font-weight:700;font-size:15px}' +
        '.pet-status{font-size:12px;padding:2px 8px;border-radius:999px;background:rgba(255,255,255,.06);color:var(--text-dim,#bdb2a6)}' +
        '.pet-status.good{color:#8fd19e;background:rgba(143,209,158,.12)}' +
        '.pet-status.ok{color:var(--text-dim,#bdb2a6)}' +
        '.pet-status.warn{color:#e3b341;background:rgba(227,179,65,.12)}' +
        '.pet-status.bad{color:#e5634d;background:rgba(229,99,77,.14)}' +
        '.pet-close{background:transparent;border:0;color:var(--text-mute,#8a7f73);font-size:18px;line-height:1;cursor:pointer;padding:2px 4px}' +
        '.pet-close:hover{color:var(--text,#f2ece4)}' +
        '.pet-stage{position:relative;height:158px;display:flex;align-items:flex-end;justify-content:center;' +
        'background:radial-gradient(120% 90% at 50% 10%,rgba(217,164,106,.10),transparent 70%);border-radius:10px;overflow:hidden;margin-bottom:10px}' +

        // ── спрайт кота ──
        '.pet-cat-sprite{width:134px;height:150px;background-repeat:no-repeat;transform-origin:50% 100%;' +
        'filter:drop-shadow(0 3px 6px rgba(0,0,0,.4))}' +
        '.pet-cat-sprite.is-static{background-size:contain;background-position:center bottom}' +
        '.pet-cat-sprite.bob{animation:petBob 3.2s ease-in-out infinite}' +
        '.pet-cat-sprite.jump{animation:petJump 1s ease-in-out infinite}' +
        '.pet-cat-sprite.droop{animation:petDroop 4s ease-in-out infinite}' +
        '.pet-cat-sprite.wob{animation:petWob 2.4s ease-in-out infinite}' +
        '.pet-cat-sprite.is-anim{background-size:536px 150px;background-position:0 0}' +
        '.pet-cat-sprite.is-anim.eat{animation:petStep .85s steps(4) infinite}' +
        '.pet-cat-sprite.is-anim.sleep{animation:petStep 1.7s steps(4) infinite}' +
        '@keyframes petStep{to{background-position-x:-536px}}' +
        '@keyframes petBob{0%,100%{transform:translateY(0)}50%{transform:translateY(-5px)}}' +
        '@keyframes petJump{0%,100%{transform:translateY(0)}28%{transform:translateY(-13px)}55%{transform:translateY(0)}}' +
        '@keyframes petDroop{0%,100%{transform:translateY(0)}50%{transform:translateY(2px)}}' +
        '@keyframes petWob{0%,100%{transform:rotate(-1.5deg)}50%{transform:rotate(1.5deg)}}' +

        '.pet-poop{position:absolute;bottom:6px;font-size:18px;filter:drop-shadow(0 1px 1px rgba(0,0,0,.5))}' +
        '.pet-fx{position:absolute;pointer-events:none;font-size:18px;animation:petFx 1s ease-out forwards;z-index:2}' +
        '@keyframes petFx{0%{opacity:0;transform:translateY(0) scale(.6)}20%{opacity:1}100%{opacity:0;transform:translateY(-40px) scale(1.1)}}' +

        '.pet-bars{display:flex;flex-direction:column;gap:7px;margin-bottom:11px}' +
        '.pet-bar{display:grid;grid-template-columns:58px 1fr;align-items:center;gap:8px;font-size:12px}' +
        '.pet-bar span{color:var(--text-dim,#bdb2a6)}' +
        '.pet-track{height:8px;border-radius:999px;background:rgba(255,255,255,.08);overflow:hidden}' +
        '.pet-fill{height:100%;border-radius:999px;transition:width .35s ease,background .35s ease}' +
        '.pet-actions{display:grid;grid-template-columns:1fr 1fr 1fr;gap:7px}' +
        '.pet-btn{border:1px solid var(--border,#2a231d);background:var(--surface-2,#201a15);color:var(--text,#f2ece4);' +
        'border-radius:10px;padding:8px 4px;font-size:12px;font-weight:600;cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:3px;' +
        'transition:background .14s ease,transform .1s ease,border-color .14s ease}' +
        '.pet-btn:hover{background:var(--hover,#2a211a);border-color:var(--accent,#d9a46a)}' +
        '.pet-btn:active{transform:scale(.95)}' +
        '.pet-btn b{font-size:16px;font-weight:400;line-height:1}' +
        '@media (prefers-reduced-motion: reduce){' +
        '.pet-panel{transition:none}' +
        '.pet-cat-sprite{animation:none!important}' +
        '.pet-fx{display:none}' +
        '}' +
        '@media (max-width:600px){.pet-panel{left:10px;right:10px;bottom:10px;width:auto}}';
        var el = document.createElement('style');
        el.id = 'mokko-pet-css';
        el.textContent = css;
        document.head.appendChild(el);
    }

    function fillColor(v) {
        if (v < 25) { return '#e5634d'; }
        if (v < 55) { return '#e3b341'; }
        return 'var(--accent,#d9a46a)';
    }

    var $panel = null, timer = null, actionUntil = 0, lastInteract = 0;

    // kind: content|joy|sad|dirty|eat|sleep
    var SPRITE = {
        content: ['content', 'is-static bob'], joy: ['joy', 'is-static jump'],
        sad: ['sad', 'is-static droop'], dirty: ['dirty', 'is-static wob'],
        eat: ['eat', 'is-anim eat'], sleep: ['sleep', 'is-anim sleep']
    };
    function setSprite(kind) {
        if (!$panel) { return; }
        var m = SPRITE[kind] || SPRITE.content;
        var $c = $panel.find('.pet-cat-sprite');
        if ($c.attr('data-k') === kind) { return; }
        $c.attr('data-k', kind).attr('class', 'pet-cat-sprite ' + m[1])
          .css('background-image', 'url(' + BASE + '/images/pet-' + m[0] + '.png' + SPV + ')');
    }

    function updateSprite(s) {
        if (now() < actionUntil) { return; } // проигрывается действие
        var st = displayState(s);
        if (st === 'content' && (now() - lastInteract > 12000)) { setSprite('sleep'); }
        else { setSprite(st); }
    }

    function panelHtml() {
        return '' +
        '<div class="pet-head">' +
            '<span class="pet-name"></span>' +
            '<span style="display:flex;align-items:center;gap:6px">' +
                '<span class="pet-status"></span>' +
                '<button type="button" class="pet-close" aria-label="Закрыть">×</button>' +
            '</span>' +
        '</div>' +
        '<div class="pet-stage"><div class="pet-cat-sprite"></div></div>' +
        '<div class="pet-bars">' +
            bar('Сытость', 'food') + bar('Настроение', 'mood') + bar('Чистота', 'clean') +
        '</div>' +
        '<div class="pet-actions">' +
            '<button type="button" class="pet-btn" data-act="feed"><b>🍖</b>Покормить</button>' +
            '<button type="button" class="pet-btn" data-act="play"><b>🪶</b>Поиграть</button>' +
            '<button type="button" class="pet-btn" data-act="clean"><b>🧹</b>Убрать</button>' +
        '</div>';
    }
    function bar(label, k) {
        return '<div class="pet-bar"><span>' + label + '</span>' +
            '<div class="pet-track"><div class="pet-fill" data-k="' + k + '"></div></div></div>';
    }

    function renderStats(s) {
        if (!$panel) { return; }
        $panel.find('.pet-name').text(s.name);
        var st = statusOf(s);
        $panel.find('.pet-status').text(st.t).attr('class', 'pet-status ' + st.cls);
        ['food', 'mood', 'clean'].forEach(function (k) {
            $panel.find('.pet-fill[data-k="' + k + '"]').css({ width: round(s[k]) + '%', background: fillColor(s[k]) });
        });
        var $stage = $panel.find('.pet-stage');
        $stage.find('.pet-poop').remove();
        var p = poopCount(s);
        for (var i = 0; i < p; i++) {
            $('<span class="pet-poop">💩</span>').css('left', (14 + i * 22) + 'px').appendTo($stage);
        }
        var cls = 'pet-panel' + ($panel.hasClass('open') ? ' open' : '') + ' ' + moodClass(s);
        $panel.attr('class', cls);
    }
    function render(s) { renderStats(s); updateSprite(s); }

    function fx(emoji) {
        if (!$panel) { return; }
        var $s = $panel.find('.pet-stage');
        var x = 40 + Math.random() * (($s.width() || 220) - 80);
        $('<span class="pet-fx">' + emoji + '</span>').css('left', x + 'px').appendTo($s)
            .on('animationend', function () { $(this).remove(); });
    }

    // проиграть действие-анимацию на ms, затем вернуться к состоянию
    function playAction(kind, ms) {
        lastInteract = now();
        actionUntil = now() + ms;
        setSprite(kind);
        setTimeout(function () {
            actionUntil = 0;
            if ($panel) { updateSprite(tick(load())); }
        }, ms);
    }

    function act(kind) {
        var s = tick(load());
        lastInteract = now();
        if (kind === 'feed') {
            if (s.food >= 96) { fx('😾'); setSprite('content'); }
            else { s.food = clamp(s.food + 30); s.mood = clamp(s.mood + 4); save(s); renderStats(s); playAction('eat', 2600); refreshBadge(s); return; }
        } else if (kind === 'play') {
            if (s.food < 10) { fx('😿'); }
            else { s.mood = clamp(s.mood + 25); s.food = clamp(s.food - 6); fx('❤️'); save(s); renderStats(s); playAction('joy', 1300); refreshBadge(s); return; }
        } else if (kind === 'clean') {
            s.clean = 100; s.mood = clamp(s.mood + 5); fx('✨');
        }
        save(s); render(s); refreshBadge(s);
    }

    function closeDropdown() {
        try {
            $('.user-dropdown').removeClass('show open');
            $('.user-dropdown .dropdown-menu').removeClass('show');
            $('.user-dropdown .dropdown-toggle').attr('aria-expanded', 'false');
        } catch (e) {}
    }

    function openPanel() {
        injectCss();
        closeDropdown();
        lastInteract = now();
        actionUntil = 0;
        var s = tick(load()); save(s);
        if (!$panel) {
            $panel = $('<div class="pet-panel"></div>').html(panelHtml()).appendTo('body');
            $panel.on('click', '.pet-close', closePanel);
            $panel.on('click', '.pet-btn', function () { act($(this).attr('data-act')); });
        }
        render(s);
        if ($panel && $panel[0]) {
            void $panel[0].offsetWidth; // фиксируем стартовое состояние до открытия
        }
        window.requestAnimationFrame(function () { if ($panel) { $panel.addClass('open'); } });
        clearInterval(timer);
        timer = setInterval(function () {
            if (document.hidden) { return; }
            var cur = tick(load()); save(cur); render(cur); refreshBadge(cur);
        }, 6000);
    }

    function closePanel() {
        clearInterval(timer); timer = null;
        if ($panel) { var p = $panel; p.removeClass('open'); setTimeout(function () { p.remove(); }, 180); $panel = null; }
    }

    function toggle() { if ($panel) { closePanel(); } else { openPanel(); } }

    function refreshBadge(s) {
        s = s || tick(load());
        $('.pet-face-btn').toggleClass('needs', needsAttention(s) || poopCount(s) > 0);
    }

    $(function () {
        injectCss();
        $(document).on('click', '.pet-face-btn', function (e) { e.preventDefault(); toggle(); });
        var init = tick(load()); save(init); refreshBadge(init);
        setInterval(function () { if (!document.hidden) { refreshBadge(); } }, 60000);
    });

    window.MokkoPet = { open: openPanel, close: closePanel, toggle: toggle, state: function () { return load(); } };
})(window, window.jQuery);

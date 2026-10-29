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
    var HAPPY_MS = 3200;
    var SLEEP_MS = 30000;
    var GRUMPY_MS = 18000;
    var CRY_MS = 2600;
    var PEEK_CHANCE = 0.07;
    var PEEK_COOLDOWN = 8 * 60 * 1000;

    function now() { return Date.now(); }
    function clamp(v) { return v < 0 ? 0 : (v > 100 ? 100 : v); }
    function round(v) { return Math.round(v); }

    function canPet(s) {
        if ((s.grumpyUntil || 0) > now()) { return false; }
        if ((s.cryingUntil || 0) > now()) { return false; }
        return s.clean >= 60 && s.food >= 50 && s.mood >= 50;
    }

    function markHappy(s, kind, delay) {
        if (s.clean < 60) { return s; }
        var start = now() + (delay || 0);
        s.happyUntil = start + HAPPY_MS;
        s.sleepUntil = start + SLEEP_MS;
        s.happyKind = kind === 'play' ? 'play' : 'feed';
        return s;
    }

    function makeAngry(s) {
        s.grumpyUntil = now() + GRUMPY_MS;
        s.happyUntil = 0;
        s.sleepUntil = 0;
        s.cryingUntil = 0;
        s.mood = clamp(s.mood - 8);
        s.petCount = 0;
        s.petSince = 0;
        s.petLimit = 0;
        s.feedCount = 0;
        s.feedSince = 0;
        lastInteract = now();
        pettingUntil = 0;
        clearTimeout(petTimer);
        petTimer = null;
        save(s);
        if ($panel) {
            renderStats(s);
            setSprite('angry');
            fx('😾');
            scheduleMoodRefresh(s);
        }
        refreshBadge(s);
        return s;
    }

    function isSleeping(s) {
        var st = displayState(s);
        return st === 'sleep' || (st === 'content' && (now() - lastInteract > 12000));
    }

    function wakeCry(s) {
        s.cryingUntil = now() + CRY_MS;
        s.happyUntil = 0;
        s.sleepUntil = 0;
        s.mood = clamp(s.mood - 1);
        lastInteract = now();
        pettingUntil = 0;
        clearTimeout(petTimer);
        petTimer = null;
        save(s);
        if ($panel) {
            renderStats(s);
            setSprite('crying');
            fx('😿');
            scheduleMoodRefresh(s);
        }
        refreshBadge(s);
        return s;
    }

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
        if ((s.grumpyUntil || 0) > now()) { return { t: 'Злится', cls: 'bad' }; }
        if ((s.cryingUntil || 0) > now()) { return { t: 'Плачет', cls: 'bad' }; }
        if (s.clean < 30) { return { t: 'Надо убрать', cls: 'bad' }; }
        if ((s.sleepUntil || 0) > now() && s.clean >= 60) { return { t: 'Спит', cls: 'ok' }; }
        if (s.food < 25) { return { t: 'Голодный', cls: 'bad' }; }
        if (s.mood < 25) { return { t: 'Скучает', cls: 'warn' }; }
        if ((s.happyUntil || 0) > now() && s.clean >= 60) { return { t: 'Счастлив', cls: 'good' }; }
        if (s.food > 70 && s.mood > 70 && s.clean > 70) { return { t: 'Мурлычет', cls: 'good' }; }
        return { t: 'Всё хорошо', cls: 'ok' };
    }
    function moodClass(s) {
        if ((s.grumpyUntil || 0) > now()) { return 'pet-sad'; }
        var m = Math.min(s.food, s.mood, s.clean);
        if (m < 25) { return 'pet-sad'; }
        if (m > 70) { return 'pet-happy'; }
        return 'pet-ok';
    }
    function displayState(s) {
        if ((s.grumpyUntil || 0) > now()) { return 'angry'; }
        if ((s.cryingUntil || 0) > now()) { return 'crying'; }
        if ((s.happyUntil || 0) > now() && s.clean >= 60) {
            return s.happyKind === 'play' ? 'happyWalk' : 'happy';
        }
        if ((s.sleepUntil || 0) > now() && s.clean >= 60) { return 'sleep'; }
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
        '.pet-cat-sprite.is-anim.sleep{background-position:-134px 0;animation:petSleepStep 1.7s linear infinite}' +
        // ── новые анимации: счастье, прогулка после игры, глажка ──
        '.pet-cat-sprite.is-happy,.pet-cat-sprite.is-happy-walk,.pet-cat-sprite.is-happy-pet{background-size:400% 100%;background-repeat:no-repeat;background-position:0 0;image-rendering:pixelated}' +
        '.pet-cat-sprite.is-happy{width:94px;height:150px}' +
        '.pet-cat-sprite.is-happy-walk{width:123px;height:150px}' +
        '.pet-cat-sprite.is-happy-pet{width:124px;height:150px}' +
        '.pet-cat-sprite.is-happy,.pet-cat-sprite.is-happy-walk,.pet-cat-sprite.is-happy-pet{animation:petHappyFrames .8s linear infinite}' +
        '.pet-cat-sprite.can-pet{cursor:pointer}' +
        '@keyframes petHappyFrames{0%,24.99%{background-position:0 0}25%,49.99%{background-position:33.333% 0}50%,74.99%{background-position:66.666% 0}75%,100%{background-position:100% 0}}' +
        '@keyframes petSleepStep{0%,33.33%{background-position:-134px 0}33.34%,66.66%{background-position:-268px 0}66.67%,100%{background-position:-402px 0}}' +
        '.pet-cat-sprite.angry{filter:drop-shadow(0 0 9px rgba(229,99,77,.55)) saturate(1.15);animation:petAngry .32s ease-in-out infinite alternate}' +
        '@keyframes petAngry{from{transform:translateY(0) rotate(-1deg)}to{transform:translateY(-2px) rotate(1deg)}}' +
        '.pet-cat-sprite.crying{filter:drop-shadow(0 0 8px rgba(120,180,255,.45)) saturate(.9);animation:petCry 1s ease-in-out infinite}' +
        '@keyframes petCry{0%,100%{transform:translateY(0)}50%{transform:translateY(2px)}}' +
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
        // ── игра «Клубок» ──
        '.pet-game{display:flex;flex-direction:column;gap:8px}' +
        '.pet-game-head{display:flex;align-items:center;gap:8px}' +
        '.pet-game-title{font-weight:700;font-size:15px}' +
        '.pet-game-best{margin-left:auto;font-size:12px;color:var(--text-dim,#bdb2a6)}' +
        '.pet-game-exit{background:transparent;border:0;color:var(--text-mute,#8a7f73);font-size:18px;line-height:1;cursor:pointer;padding:2px 4px}' +
        '.pet-game-exit:hover{color:var(--text,#f2ece4)}' +
        '.pet-game-canvas{display:block;width:100%;height:236px;border-radius:10px;background:#141018;cursor:pointer;touch-action:manipulation;-webkit-tap-highlight-color:transparent}' +
        // ── редкий кот выглядывает у бургера ──
        '.pet-peek{position:fixed;right:-12px;z-index:4500;width:58px;height:85px;pointer-events:none;transform:translateX(115%);animation:petPeekFace 3.8s cubic-bezier(.22,.9,.3,1) forwards}' +
        '.pet-peek img{display:block;width:100%;height:100%;object-fit:contain;filter:drop-shadow(0 3px 8px rgba(0,0,0,.5))}' +
        '@keyframes petPeekFace{0%{transform:translateX(115%)}16%,76%{transform:translateX(0)}100%{transform:translateX(115%)}}' +
        '@media (prefers-reduced-motion: reduce){.pet-peek{animation:none!important;transform:none!important}}' +
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
    var gameOn = false, gRAF = 0, G = null, catImg = {}, lastGameReward = false;
    var pettingUntil = 0, petTimer = null, moodTimer = null;

    // kind: content|joy|sad|dirty|eat|sleep
    var SPRITE = {
        content: ['content', 'is-static bob'], joy: ['joy', 'is-static jump'],
        sad: ['sad', 'is-static droop'], dirty: ['dirty', 'is-static wob'],
        eat: ['eat', 'is-anim eat'], sleep: ['sleep', 'is-anim sleep'],
        happy: ['happy-hearts', 'is-happy'],
        happyWalk: ['happy-walk', 'is-happy-walk'],
        pet: ['happy-pet', 'is-happy-pet'],
        angry: ['angry', 'is-static angry'],
        crying: ['sad', 'is-static droop crying']
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
        var $sprite = $panel.find('.pet-cat-sprite');
        $sprite.toggleClass('can-pet', canPet(s));
        if (now() < pettingUntil) { setSprite('pet'); return; }
        if (now() < actionUntil) { return; } // проигрывается действие
        var st = displayState(s);
        if (st === 'content' && (now() - lastInteract > 12000)) { setSprite('sleep'); }
        else { setSprite(st); }
        $panel.find('.pet-cat-sprite').toggleClass('can-pet', canPet(s));
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
        $panel.find('.pet-cat-sprite').toggleClass('can-pet', canPet(s));
    }
    function scheduleMoodRefresh(s) {
        clearTimeout(moodTimer);
        moodTimer = null;
        var next = 0;
        if ((s.grumpyUntil || 0) > now()) {
            next = s.grumpyUntil - now() + 30;
        } else if ((s.cryingUntil || 0) > now()) {
            next = s.cryingUntil - now() + 30;
        } else if ((s.happyUntil || 0) > now()) {
            next = s.happyUntil - now() + 30;
        } else if ((s.sleepUntil || 0) > now() && s.clean >= 60) {
            next = s.sleepUntil - now() + 30;
        }
        if (next > 0) {
            moodTimer = setTimeout(function () {
                if (!$panel || gameOn) { return; }
                var cur = tick(load());
                save(cur);
                renderStats(cur);
                updateSprite(cur);
                refreshBadge(cur);
                scheduleMoodRefresh(cur);
            }, Math.min(next, 2147483647));
        }
    }
    function render(s) { renderStats(s); updateSprite(s); scheduleMoodRefresh(s); }

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
            if (!s.feedSince || now() - s.feedSince > 60000) { s.feedCount = 0; }
            s.feedCount = (s.feedCount || 0) + 1;
            s.feedSince = now();
            if (s.food >= 96 || s.feedCount > 3) {
                makeAngry(s);
                return;
            }
            s.food = clamp(s.food + 30);
            s.mood = clamp(s.mood + 4);
            markHappy(s, 'feed', 2600);
            save(s);
            renderStats(s);
            scheduleMoodRefresh(s);
            playAction('eat', 2600);
            refreshBadge(s);
            return;
        } else if (kind === 'play') {
            if (s.food < 10) { fx('😿'); setSprite('sad'); save(s); render(s); refreshBadge(s); return; }
            save(s); openGame(); return;
        } else if (kind === 'clean') {
            s.clean = 100; s.mood = clamp(s.mood + 5); fx('✨');
        }
        save(s); render(s); refreshBadge(s);
    }

    function petCat() {
        if (gameOn || !$panel) { return; }
        var s = tick(load());
        if (isSleeping(s) && s.mood < 35) {
            wakeCry(s);
            return;
        }
        if (!canPet(s)) {
            fx('😾');
            return;
        }
        if (!s.petSince || now() - s.petSince > 8000) {
            s.petCount = 0;
            // Настроение меняется: сегодня кот терпит 5–9 поглаживаний.
            s.petLimit = 5 + Math.floor(Math.random() * 5);
        }
        s.petCount = (s.petCount || 0) + 1;
        s.petSince = now();
        if (s.petCount > (s.petLimit || 6)) {
            makeAngry(s);
            return;
        }
        lastInteract = now();
        s.mood = clamp(s.mood + 1);
        save(s);
        pettingUntil = now() + 1800;
        renderStats(s);
        setSprite('pet');
        refreshBadge(s);
        scheduleMoodRefresh(s);
        clearTimeout(petTimer);
        petTimer = setTimeout(function () {
            pettingUntil = 0;
            if ($panel && !gameOn) { updateSprite(tick(load())); }
        }, 1800);
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
            $panel.on('click', '.pet-cat-sprite', function (e) { e.preventDefault(); petCat(); });
            $panel.on('click', '.pet-game-exit', exitGame);
            $panel.on('pointerdown', '.pet-game-canvas', function (e) {
                e.preventDefault();
                if (!G) { return; }
                var rect = this.getBoundingClientRect();
                if (!rect.width) { return; }
                var x = (e.clientX - rect.left) * (G.W / rect.width);
                gameTap(x);
            });
        }
        render(s);
        if ($panel && $panel[0]) {
            void $panel[0].offsetWidth; // фиксируем стартовое состояние до открытия
        }
        window.requestAnimationFrame(function () { if ($panel) { $panel.addClass('open'); } });
        clearInterval(timer);
        timer = setInterval(function () {
            if (document.hidden || gameOn) { return; }
            var cur = tick(load()); save(cur); render(cur); refreshBadge(cur);
        }, 6000);
    }

    function closePanel() {
        if (gameOn) {
            stopGame();
            var closedState = applyGameReward();
            refreshBadge(closedState);
        }
        clearInterval(timer); timer = null;
        clearTimeout(petTimer); petTimer = null; pettingUntil = 0;
        clearTimeout(moodTimer); moodTimer = null;
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

    // ─────────────────────────── Игра «Клубок» ───────────────────────────
    // Однокнопочный keepie-uppie: тапом отбиваем падающий клубок, не давая ему
    // упасть. Отбивать можно, только когда клубок в зоне досягаемости лапы; тап
    // «впустую» (слишком высоко) даёт короткую оглушку, чтобы мэш не спасал.
    // Очки → рекорд (bestYarn) и настроение кота. Всё на canvas, без ассетов,
    // кроме уже готовых спрайтов кота (content/joy).
    function preloadGameImgs() {
        ['content', 'joy'].forEach(function (k) {
            if (catImg[k]) { return; }
            var im = new Image(); im.src = BASE + '/images/pet-' + k + '.png' + SPV; catImg[k] = im;
        });
    }

    function gameHtml(best) {
        return '' +
        '<div class="pet-game">' +
            '<div class="pet-game-head">' +
                '<span class="pet-game-title">Клубок</span>' +
                '<span class="pet-game-best">рекорд ' + (best | 0) + '</span>' +
                '<button type="button" class="pet-game-exit" aria-label="К коту">×</button>' +
            '</div>' +
            '<canvas class="pet-game-canvas"></canvas>' +
        '</div>';
    }

    function openGame() {
        if (!$panel) { return; }
        preloadGameImgs();
        var best = (load().bestYarn | 0);
        $panel.html(gameHtml(best));
        var canvas = $panel.find('.pet-game-canvas')[0];
        var dpr = window.devicePixelRatio || 1;
        var W = canvas.clientWidth || 232, Hh = 236;
        canvas.width = Math.round(W * dpr); canvas.height = Math.round(Hh * dpr);
        var ctx = canvas.getContext('2d'); ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        G = {
            ctx: ctx, W: W, Hh: Hh, floorY: Hh - 16,
            ball: { x: W / 2, y: 54, vy: 0, vx: 0, r: 13 },
            g: 0.20, jump: 5.2, reachH: 96,
            score: 0, runBest: 0, best: best,
            state: 'ready', batUntil: 0, stunUntil: 0, last: 0,
            catX: W / 2, catTarget: W / 2, catW: 56
        };
        // Стартовый клубок сразу в зоне удара: первый тап — это уже удар,
        // а не только запуск раунда.
        G.ball.y = G.floorY - G.reachH - G.ball.r + 2;
        gameOn = true;
        gRAF = window.requestAnimationFrame(gameLoop);
    }

    function resetRound() {
        if (!G) { return; }
        G.g = 0.20; G.reachH = 96; G.score = 0; G.state = 'playing'; G.stunUntil = 0;
        var reachTop = G.floorY - G.reachH;
        G.ball = { x: G.W / 2, y: reachTop - 11, vy: 1.2, vx: 0, r: 13 };
        G.batUntil = 0; G.last = 0;
        G.catX = G.W / 2; G.catTarget = G.W / 2;
        if (gameOn && !gRAF) { gRAF = window.requestAnimationFrame(gameLoop); }
    }

    function gameTap(x) {
        if (!G) { return; }
        if (typeof x !== 'number' || isNaN(x)) { x = G.W / 2; }
        x = Math.max(0, Math.min(G.W, x));
        G.catTarget = x;
        if (G.state === 'ready' || G.state === 'gameover') {
            resetRound();
            G.catTarget = x;
        }
        if (G.state !== 'playing') { return; }
        if (now() < G.stunUntil || now() < G.batUntil) { return; }
        var b = G.ball, reachTop = G.floorY - G.reachH;
        // Засчитываем только удар по падающему клубку, если тап рядом с ним,
        // и не чаще одного раза за короткий интервал.
        if (b.vy > 0 && b.y + b.r >= reachTop && Math.abs(b.x - x) <= 44) {
            b.vy = -G.jump;
            b.vx += (Math.random() * 2.6 - 1.3);
            if (b.vx > 2.6) { b.vx = 2.6; } if (b.vx < -2.6) { b.vx = -2.6; }
            G.score++;
            if (G.score > G.runBest) { G.runBest = G.score; }
            G.batUntil = now() + 200;
            if (G.score % 5 === 0) { G.g = Math.min(0.34, G.g * 1.06); G.reachH = Math.max(58, G.reachH - 5); }
        } else {
            G.stunUntil = now() + 260; // мимо — короткая оглушка
        }
    }

    function gameLoop(ts) {
        if (!gameOn || !G) { return; }
        if (G.state === 'gameover') {
            G.last = ts;
            gameDraw();
            gRAF = 0;
            return;
        }
        var k = G.last ? Math.max(0.5, Math.min(2, (ts - G.last) / 16.67)) : 1;
        G.last = ts;
        if (G.state === 'playing') {
            var b = G.ball;
            b.vy += G.g * k; b.y += b.vy * k; b.x += b.vx * k;
            if (b.x < b.r) { b.x = b.r; b.vx = -b.vx * 0.88; }
            if (b.x > G.W - b.r) { b.x = G.W - b.r; b.vx = -b.vx * 0.88; }
            if (b.y < b.r) { b.y = b.r; b.vy = Math.abs(b.vy) * 0.5; }
            if (b.y + b.r >= G.floorY) {
                G.state = 'gameover';
                if (G.score > G.best) {
                    G.best = G.score;
                    var s = tick(load());
                    if (G.score > (s.bestYarn | 0)) {
                        s.bestYarn = G.score;
                        save(s);
                    }
                    if ($panel) { $panel.find('.pet-game-best').text('рекорд ' + G.best); }
                }
            }
        }
        G.catX += (G.catTarget - G.catX) * Math.min(0.4, 0.22 * k);
        gameDraw();
        if (gameOn) { gRAF = window.requestAnimationFrame(gameLoop); }
    }

    function drawYarn(ctx, x, y, r) {
        ctx.save();
        ctx.beginPath(); ctx.arc(x, y, r, 0, Math.PI * 2); ctx.fillStyle = '#e08bb0'; ctx.fill();
        ctx.save();
        ctx.beginPath(); ctx.arc(x, y, r - 0.5, 0, Math.PI * 2); ctx.clip();
        ctx.strokeStyle = 'rgba(140,58,98,.55)'; ctx.lineWidth = 1.2;
        for (var i = -1; i <= 1; i++) {
            ctx.beginPath();
            ctx.ellipse(x, y, r - 3, r * 0.38, i * 0.58, 0, Math.PI * 2);
            ctx.stroke();
        }
        ctx.restore();
        ctx.beginPath(); ctx.arc(x, y, r, 0, Math.PI * 2);
        ctx.strokeStyle = 'rgba(95,35,68,.45)'; ctx.lineWidth = 1; ctx.stroke();
        ctx.fillStyle = 'rgba(255,255,255,.42)';
        ctx.beginPath(); ctx.arc(x - r * 0.32, y - r * 0.34, r * 0.2, 0, Math.PI * 2); ctx.fill();
        ctx.restore();
    }

    function gameText(ctx, W, Hh, big, small) {
        ctx.fillStyle = 'rgba(10,8,12,.55)'; ctx.fillRect(0, 0, W, Hh);
        ctx.textAlign = 'center'; ctx.fillStyle = '#f2ece4';
        ctx.font = '700 20px Manrope, system-ui, sans-serif'; ctx.fillText(big, W / 2, Hh / 2 - 4);
        ctx.font = '600 13px Manrope, system-ui, sans-serif'; ctx.fillStyle = '#bdb2a6';
        ctx.fillText(small, W / 2, Hh / 2 + 18);
    }

    function gameDraw() {
        var ctx = G.ctx, W = G.W, Hh = G.Hh;
        ctx.clearRect(0, 0, W, Hh);
        var bg = ctx.createLinearGradient(0, 0, 0, Hh);
        bg.addColorStop(0, '#201a24'); bg.addColorStop(1, '#141018');
        ctx.fillStyle = bg; ctx.fillRect(0, 0, W, Hh);
        // зона досягаемости
        var reachTop = G.floorY - G.reachH;
        ctx.fillStyle = 'rgba(217,164,106,.07)';
        ctx.fillRect(0, reachTop, W, G.floorY - reachTop);
        // пол
        ctx.strokeStyle = 'rgba(255,255,255,.12)'; ctx.lineWidth = 2;
        ctx.beginPath(); ctx.moveTo(0, G.floorY); ctx.lineTo(W, G.floorY); ctx.stroke();
        // кот (joy во время отбивания)
        var img = catImg[(now() < G.batUntil) ? 'joy' : 'content'];
        var ch = 66;
        if (img && img.complete && img.naturalWidth) {
            var cw = ch * img.naturalWidth / img.naturalHeight;
            G.catW = cw;
            var cx = Math.max(cw / 2, Math.min(W - cw / 2, G.catX));
            ctx.drawImage(img, cx - cw / 2, G.floorY - ch + 2, cw, ch);
        }
        // клубок
        drawYarn(ctx, G.ball.x, G.ball.y, G.ball.r);
        // счёт
        ctx.fillStyle = '#f2ece4'; ctx.textAlign = 'center';
        ctx.font = '700 22px Manrope, system-ui, sans-serif';
        ctx.fillText(String(G.score), W / 2, 34);
        // оглушка
        if (now() < G.stunUntil) {
            ctx.strokeStyle = 'rgba(229,99,77,.6)'; ctx.lineWidth = 3;
            ctx.strokeRect(1.5, 1.5, W - 3, Hh - 3);
        }
        if (G.state === 'ready') { gameText(ctx, W, Hh, 'Клубок', 'Тапни по клубку'); }
        else if (G.state === 'gameover') { gameText(ctx, W, Hh, 'Счёт ' + G.score, 'рекорд ' + G.best + ' · тапни ещё раз'); }
    }

    function stopGame() { gameOn = false; if (gRAF) { window.cancelAnimationFrame(gRAF); gRAF = 0; } }

    function applyGameReward() {
        var run = G ? Math.max(G.runBest, G.score || 0) : 0;
        var s = tick(load());
        // Без результата игра не должна давать бесплатное настроение.
        if (run > 0) {
            s.mood = clamp(s.mood + Math.min(35, 10 + run * 1.3));
            s.food = clamp(s.food - 6);
            markHappy(s, 'play', 0);
            lastGameReward = true;
        } else {
            lastGameReward = false;
        }
        if (run > (s.bestYarn | 0)) { s.bestYarn = run; }
        save(s);
        lastInteract = now();
        G = null;
        return s;
    }

    function exitGame() {
        stopGame();
        var s = applyGameReward();
        if ($panel) {
            $panel.html(panelHtml());
            render(s); refreshBadge(s);
            if (lastGameReward) { fx('❤️'); }
        }
    }

    function showRarePeek() {
        if (document.getElementById('pet-peek')) { return; }
        injectCss();
        var burger = document.querySelector('.usertop .menu-toggle');
        var top = 60;
        if (burger) {
            var rect = burger.getBoundingClientRect();
            top = Math.max(8, Math.round(rect.bottom + 5));
        }
        var el = document.createElement('div');
        el.id = 'pet-peek';
        el.className = 'pet-peek';
        el.style.top = top + 'px';
        el.setAttribute('aria-hidden', 'true');
        var img = document.createElement('img');
        img.src = BASE + '/images/pet-peek.png?v=3';
        img.alt = '';
        el.appendChild(img);
        document.body.appendChild(el);
        setTimeout(function () {
            if (el && el.parentNode) { el.parentNode.removeChild(el); }
        }, 4000);
    }

    function checkRarePeek() {
        if (document.hidden || $panel || gameOn || document.getElementById('pet-peek')) { return; }
        var s = tick(load());
        var allBad = s.food < 30 && s.mood < 30 && s.clean < 30;
        if (!allBad || now() - (s.lastPeek || 0) < PEEK_COOLDOWN) {
            save(s);
            return;
        }
        if (Math.random() > PEEK_CHANCE) {
            save(s);
            return;
        }
        s.lastPeek = now();
        save(s);
        showRarePeek();
    }

    setTimeout(checkRarePeek, 30000);
    setInterval(checkRarePeek, 60000);

    window.MokkoPet = { open: openPanel, close: closePanel, toggle: toggle, state: function () { return load(); } };
})(window, window.jQuery);

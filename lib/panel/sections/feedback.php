<?php if (!defined('MOKKO')) { die; } ?>
<div class="layer" id="feedback">
<style>
#feedback .support-shell{display:grid;grid-template-columns:minmax(240px,.82fr) minmax(0,1.35fr);gap:24px;align-items:stretch;min-height:calc(100vh - 220px);padding:24px 16px 32px;}
#feedback .support-cat-card{position:relative;display:flex;flex-direction:column;justify-content:flex-end;min-height:420px;padding:0;border:0;border-radius:18px;background:var(--surface-2);overflow:hidden;box-shadow:var(--shadow-sm);}
#feedback .support-cat-card video{position:absolute;inset:0;width:100%;height:100%;max-width:none;max-height:none;object-fit:cover;object-position:center;border-radius:0;display:block;background:var(--surface-2);}
#feedback .support-cat-overlay{position:relative;z-index:1;width:100%;margin-top:auto;padding:24px 20px 20px;background:linear-gradient(180deg,transparent,rgba(12,9,7,.92));}
#feedback .support-cat-card h3{margin:0;font-size:20px;font-weight:700;color:#fff;text-align:left;text-shadow:0 1px 8px rgba(0,0,0,.55);}
#feedback .support-cat-card p{margin:6px 0 0;font-size:13px;line-height:1.45;color:rgba(255,255,255,.78);text-align:left;max-width:none;text-shadow:0 1px 6px rgba(0,0,0,.5);}
#feedback .support-cat-card p a{color:var(--accent-2);font-weight:600;text-decoration:underline;text-underline-offset:2px;}
#feedback .support-cat-card p a:hover{color:var(--accent);}
#feedback .support-chat{display:flex;flex-direction:column;min-height:540px;border:1px solid var(--border);border-radius:18px;background:var(--surface);overflow:hidden;box-shadow:var(--shadow-sm);}
#feedback .support-chat-head{display:flex;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid var(--border-soft);background:var(--surface-2);}
#feedback .support-chat-dot{width:8px;height:8px;border-radius:50%;background:var(--success);box-shadow:0 0 0 4px rgba(126,201,164,.12);flex:0 0 auto;}
#feedback .support-chat-head b{font-size:14px;font-weight:650;color:var(--text);}
#feedback .support-chat-head small{margin-left:auto;font-size:12px;color:var(--text-mute);}
#feedback .support-log{flex:1 1 auto;min-height:0;overflow-y:auto;padding:18px;display:flex;flex-direction:column;gap:10px;scroll-behavior:smooth;}
#feedback .support-msg{max-width:78%;padding:10px 14px;border-radius:14px;font-size:14.5px;line-height:1.45;overflow-wrap:anywhere;}
#feedback .support-msg--user{align-self:flex-end;background:var(--accent);color:var(--accent-ink);border-bottom-right-radius:5px;font-weight:550;}
#feedback .support-msg--cat{align-self:flex-start;background:var(--surface-2);border:1px solid var(--border);color:var(--text);border-bottom-left-radius:5px;}
#feedback .support-typing{align-self:flex-start;display:inline-flex;align-items:center;gap:5px;margin:0 18px 10px;padding:12px 14px;border:1px solid var(--border);border-radius:14px;border-bottom-left-radius:5px;background:var(--surface-2);}
#feedback .support-typing[hidden]{display:none;}
#feedback .support-typing i{width:6px;height:6px;border-radius:50%;background:var(--text-mute);display:block;animation:supportType 1.1s ease-in-out infinite;}
#feedback .support-typing i:nth-child(2){animation-delay:.15s;}
#feedback .support-typing i:nth-child(3){animation-delay:.3s;}
@keyframes supportType{0%,60%,100%{transform:translateY(0);opacity:.45;}30%{transform:translateY(-4px);opacity:1;}}
#feedback .support-form{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:center;gap:8px;margin:0;padding:8px 12px;border-top:1px solid var(--border-soft);background:var(--surface-2);}
#feedback .support-form input{flex:0 0 auto;min-width:0;height:40px;margin:0;padding:0 14px;border:1px solid var(--border);border-radius:10px;background:var(--surface);color:var(--text);font-size:14px;outline:none;box-sizing:border-box;}
#feedback .support-form input:focus{border-color:var(--accent-line);box-shadow:var(--ring);}
#feedback .support-form input:disabled{opacity:.6;cursor:wait;}
#feedback .support-form button{flex:0 0 auto;height:40px;min-height:40px;margin:0;padding:0 16px;border:0;border-radius:10px;background:var(--accent);color:var(--accent-ink);font-size:14px;font-weight:650;line-height:1;cursor:pointer;box-sizing:border-box;align-self:center;transition:background-color .15s ease,opacity .15s ease;}
#feedback .support-form button:hover{background:var(--accent-2);}
#feedback .support-form button:disabled{opacity:.55;cursor:wait;}
@media (max-width:767.98px){
    #feedback .support-shell{grid-template-columns:1fr;gap:14px;min-height:0;padding:14px 0 24px;}
    #feedback .support-cat-card{padding:0;min-height:280px;height:280px;}
    #feedback .support-cat-card video{max-height:none;}
    #feedback .support-cat-overlay{padding:18px 16px 16px;}
    #feedback .support-cat-card h3{font-size:19px;}
    #feedback .support-cat-card p{font-size:12.5px;}
    #feedback .support-chat{min-height:390px;}
    #feedback .support-log{padding:14px;}
    #feedback .support-msg{max-width:86%;font-size:14px;}
    #feedback .support-form{padding:8px 10px;}
}
</style>

<div class="support-shell">
    <div class="support-cat-card">
        <video src="<?= BASE_PATH ?>/images/support-cat.mp4?v=1" poster="<?= BASE_PATH ?>/images/support-cat.jpg?v=1" autoplay muted loop playsinline preload="metadata" aria-label="Грустный кот техподдержки"></video>
        <div class="support-cat-overlay">
            <h3>Кот техподдержки</h3>
            <p>Он грустит, но внимательно читает. Напишите вопрос — кот подумает и ответит.</p>
            <p>Почта для связи: <a href="mailto:mokkocrm@yahoo.com">mokkocrm@yahoo.com</a></p>
        </div>
    </div>

    <div class="support-chat">
        <div class="support-chat-head">
            <span class="support-chat-dot" aria-hidden="true"></span>
            <b>Чат техподдержки</b>
            <small>кот на связи</small>
        </div>

        <div class="support-log" id="support-log" role="log" aria-live="polite" aria-relevant="additions">
            <div class="support-msg support-msg--cat">Мяу. Кот поддержки слушает.</div>
        </div>

        <div class="support-typing" id="support-typing" hidden aria-label="Кот печатает">
            <i></i><i></i><i></i>
        </div>

        <form class="support-form" id="support-form" autocomplete="off">
            <input type="text" id="support-input" name="message" placeholder="Напишите вопрос коту…" maxlength="500" aria-label="Сообщение коту техподдержки">
            <button type="submit" id="support-send">Отправить</button>
        </form>
    </div>
</div>

<script>
(function () {
    var form = document.getElementById('support-form');
    var input = document.getElementById('support-input');
    var button = document.getElementById('support-send');
    var log = document.getElementById('support-log');
    var typing = document.getElementById('support-typing');
    if (!form || !input || !button || !log || !typing) { return; }

    var phrases = ['мяу', 'мур', 'миу', 'фррр', 'мрр', 'мяу-мяу', 'мур-мур', 'фрр', 'мя', 'мр', 'мррр', 'мууур'];
    var endings = ['…', '.', '!', '?', ''];

    function scrollLog() {
        log.scrollTop = log.scrollHeight;
    }

    function appendMessage(text, kind) {
        var el = document.createElement('div');
        el.className = 'support-msg support-msg--' + kind;
        el.textContent = text;
        log.appendChild(el);
        scrollLog();
        return el;
    }

    function catReply() {
        var count = 1 + Math.floor(Math.random() * 4);
        var pool = phrases.slice();
        var parts = [];
        for (var i = 0; i < count && pool.length; i++) {
            var index = Math.floor(Math.random() * pool.length);
            parts.push(pool.splice(index, 1)[0]);
        }
        var separator = Math.random() < 0.5 ? ', ' : ' ';
        return parts.join(separator) + endings[Math.floor(Math.random() * endings.length)];
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (form.dataset.busy === '1') { return; }

        var text = input.value.trim();
        if (!text) { input.focus(); return; }

        appendMessage(text, 'user');
        input.value = '';
        form.dataset.busy = '1';
        input.disabled = true;
        button.disabled = true;
        typing.hidden = false;
        scrollLog();

        var delay = 1200 + Math.random() * 1800 + Math.min(text.length * 12, 600);
        window.setTimeout(function () {
            typing.hidden = true;
            appendMessage(catReply(), 'cat');
            form.dataset.busy = '';
            input.disabled = false;
            button.disabled = false;
            input.focus();
            scrollLog();
        }, delay);
    });
})();
</script>
</div>

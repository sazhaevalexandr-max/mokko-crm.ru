/* MOKKO CRM — небольшие дополнения к Bootstrap 4.
   Старый код местами вызывает плагины Bootstrap 3, которых в 4-й версии нет. */
(function ($) {
    'use strict';

    if (!$) { return; }

    // .affix() удалён в Bootstrap 4 — оставляем безопасную заглушку,
    // иначе panel.js/crm.js падают на вызове.
    if (!$.fn.affix) {
        $.fn.affix = function () { return this; };
    }

    // Bootstrap 4 не подсвечивает активный пункт в старом .nav-tabs без .nav-link,
    // но jQuery-плагин табов работает — дополнительно синхронизируем .active на <li>.
    $(document).on('shown.bs.tab', '.nav-tabs a[data-toggle="tab"]', function () {
        var $link = $(this);
        $link.closest('.nav-tabs').find('li').removeClass('active');
        $link.parent('li').addClass('active');
    });

    // Старые дропдауны получают .show вместо .open — дублируем класс для своего CSS.
    $(document).on('show.bs.dropdown hidden.bs.dropdown', '.dropdown', function (e) {
        $(this).toggleClass('open', e.type === 'show');
    });

    // Микроанимация: счётчик позиции и сумма заказа коротко «отзываются»,
    // когда меняются, — видно, что нажатие засчитано.
    function bump(el) {
        el.classList.remove('is-bumping');
        void el.offsetWidth; // перезапуск CSS-анимации
        el.classList.add('is-bumping');
    }
    $(function () {
        if (!window.MutationObserver) { return; }
        var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (reduce) { return; }
        var targets = document.querySelectorAll('#dash, .total_summ');
        if (!targets.length) { return; }
        var observer = new MutationObserver(function (records) {
            for (var i = 0; i < records.length; i++) {
                var node = records[i].target;
                if (node.nodeType === 3) { node = node.parentNode; }
                if (!node || !node.classList) { continue; }
                if (node.classList.contains('kolvo') || node.classList.contains('sum')) {
                    bump(node);
                }
            }
        });
        for (var j = 0; j < targets.length; j++) {
            observer.observe(targets[j], { childList: true, characterData: true, subtree: true });
        }
        $(document).on('animationend', '.is-bumping', function () {
            this.classList.remove('is-bumping');
        });
    });

    // Клик по пункту меню в мобильной версии закрывает дропдаун.
    $(document).on('click', '.dropdown-menu > li > a', function () {
        var $menu = $(this).closest('.dropdown');
        if ($menu.length && $menu.hasClass('open') && window.innerWidth < 992) {
            $menu.removeClass('open');
            $menu.find('.dropdown-menu').removeClass('show');
        }
    });
})(window.jQuery);

<?php if (!defined('MOKKO')) { die; } ?>
<div class="layer" id="options">

<div class="section-head">
    <h2>Настройки</h2>
    <p>Профиль владельца аккаунта и вход.</p>
</div>

<div class="settings-grid">

    <section class="settings-card">
        <header class="settings-card-head"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 20.5c1.6-3.6 4.6-5.5 8-5.5s6.4 1.9 8 5.5"/></svg><h3>Профиль</h3></header>
        <div class="set-row set-row--profile">
            <span class="set-avatar" aria-hidden="true"></span>
            <div class="set-main">
                <span class="set-label">Имя</span>
                <b class="set-value set_name name_value">—</b>
            </div>
            <button type="button" class="btn btn-default btn-sm name_option">Изменить</button>
        </div>
        <div class="set-row">
            <div class="set-main">
                <span class="set-label">E-mail для входа</span>
                <span class="set-value set_login">—</span>
            </div>
            <span class="set-note">Меняется через поддержку</span>
        </div>
        <div class="set-row">
            <div class="set-main">
                <span class="set-label">Почта для связи</span>
                <a class="set-value settings-mail" href="mailto:mokkocrm@yahoo.com">mokkocrm@yahoo.com</a>
            </div>
            <span class="set-note">Поддержка проекта</span>
        </div>
        <div class="set-row">
            <div class="set-main">
                <span class="set-label">Телефон</span>
                <span class="set-value set_phone is-empty">Не указан</span>
            </div>
            <button type="button" class="btn btn-default btn-sm phone_option">Добавить</button>
        </div>
    </section>

    <section class="settings-card">
        <header class="settings-card-head"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l7.5 3v5.5c0 4.6-3.1 8.4-7.5 9.5-4.4-1.1-7.5-4.9-7.5-9.5V6L12 3z"/><path d="M9 12l2.2 2.2L15.5 10"/></svg><h3>Безопасность</h3></header>
        <div class="set-row">
            <div class="set-main">
                <span class="set-label">Пароль</span>
                <span class="set-value set-dots" aria-label="Пароль скрыт">••••••••••</span>
            </div>
            <button type="button" class="btn btn-default btn-sm password_option">Сменить пароль</button>
        </div>
        <p class="set-hint">После 10 неудачных попыток входа вход блокируется на 5 минут — это защищает аккаунт от подбора пароля.</p>
    </section>

    <section class="settings-card">
        <header class="settings-card-head"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.8 8.6c0 5.2-8.8 10.4-8.8 10.4S3.2 13.8 3.2 8.6A4.6 4.6 0 0 1 12 7a4.6 4.6 0 0 1 8.8 1.6z"/></svg><h3>Поддержать проект</h3></header>
        <p class="set-support-copy">MOKKO CRM — некоммерческий проект. Донаты помогают окупать разработку в свободное время и выпускать новые модули и обновления. Любая поддержка важна.</p>
        <a class="btn btn-primary set-support-btn" href="https://boosty.to/alexandr220/donate" target="_blank" rel="noopener noreferrer">Поддержать на Boosty</a>
        <p class="set-support-note">Это не оплата подписки — просто поддержка автора.</p>
    </section>

    <section class="settings-card settings-card--wide">
        <header class="settings-card-head"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 10.5 12 4l8 6.5V20H4z"/><path d="M9.5 20v-5.5h5V20"/></svg><h3>Кофейни и реквизиты</h3></header>
        <p class="set-hint set-hint--top">Название, адрес и данные юрлица печатаются в чеке. ИНН и КПП можно оставить пустыми, если чеки не печатаете.</p>
        <div class="settings-places"><p class="set-empty">Загрузка…</p></div>
    </section>

</div>

<div class="settings-build">
    <span>Версия сборки</span>
    <b>RC <?= mokko_h(MOKKO_VERSION) ?></b>
</div>

</div>


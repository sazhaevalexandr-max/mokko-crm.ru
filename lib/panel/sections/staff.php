<?php if (!defined('MOKKO')) { die; } ?>
<div class="layer" id="staff">
<div class="section-head section-head--row">
    <div>
        <h2>Сотрудники <span class="staff-count">0 из 4</span></h2>
        <p>Бариста входит на обычной странице входа со своим логином и паролем и сразу попадает в кассу — без доступа к панели. Ставка нужна для расчёта выработки.</p>
    </div>
</div>
<div class="staff-grid">
    <button type="button" class="place-add staff-add add_staff"><span class="place-add-plus" aria-hidden="true">+</span><span class="place-add-title">Добавить сотрудника</span><small>Логин и пароль для входа в кассу</small></button>
</div>
<p class="staff-empty" style="display:none;">Пока никого нет. Создайте аккаунт — сотрудник войдёт в кассу под своим логином, а его смены попадут в статистику.</p>
<div class="progress"><div class="progress-bar progress-bar-striped progress-bar-items active" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="display: none; width: 100%;"><span class="sr-only"></span></div></div>
</div>


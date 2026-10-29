<?php if (!defined('MOKKO')) { die; } ?>
<div class="layer active" id="dashboard">
    <div class="col-md-12 bill-bar">
        <!-- Текущий счёт: название + переименование на месте -->
        <div class="bill-current edit_g guest is-empty" aria-live="polite">
            <span class="bill-label">Текущий счёт</span>
            <div class="bill-name-row">
                <span class="bill-dot" aria-hidden="true"></span>
                <a class="name_guest" phone="" href="#" tabindex="-1">Не открыт</a>
                <a class="edit_guest" href="#" aria-label="Переименовать счёт" title="Переименовать"><svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4L19 9a2.8 2.8 0 0 0-4-4L4 16v4z"/><path d="M13.5 6.5l4 4"/></svg></a>
                <div class="input_guest"><form id="edit_g" onsubmit="return false;">
                    <input type="text" class="new_name form-control" placeholder="Название счёта" name="name_guest" maxlength="40" autocomplete="off" aria-label="Новое название счёта">
                    <!---<input type='text' maxlength='11' name='phone_guest' class='new_phone' placeholder='+7' />--->
                </form></div>
                <button style="display:none;" type="button" class="btn btn-primary btn-sm save_guest">Сохранить</button>
            </div>
        </div>

        <!-- Новый счёт: поле и кнопка одной группой -->
        <form id="guest_form" class="bill-new" role="form" onsubmit="return false;">
            <input type="hidden" name="type_guest" value="new">
            <input type="hidden" name="id_guest">
            <div class="bill-new-group">
                <div class="ui-widget">
                    <input type="text" class="form-control" id="guest" name="guest" placeholder="Например, Стол 4" maxlength="40" autocomplete="off" aria-label="Название нового счёта">
                </div>
                <button class="btn btn-primary add_guest" type="button"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg><span>Открыть счёт</span></button>
            </div>
            <small class="bill-hint">Без названия счёт получит номер <b class="bill-next">—</b></small>
        </form>
    </div>
    <div class="col-md-12">
        <div class="btn-group btn-group-sm filter_items">

        </div>
        <br/><br/><br/>
    </div>
    
    <div id="dash">
<center><h1 class="nope">Загружаю меню <div class="loader-menu fa fa-spin colored-border"></div></h1></center>
           </div>
</div>

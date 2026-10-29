<?php if (!defined('MOKKO')) { die; } ?>
<div class="layer" id="lager">

    <div class="lager-toolbar">
        <div class="lager-search">
            <input placeholder="Поиск по списку" id="search_lager" type="text" class="form-control" autocomplete="off">
        </div>
        <div class="lager-place places_lager">
            <select class="form-control" name="place" aria-label="Кофейня">
                <option disabled selected>Загрузка…</option>
                <option></option>
            </select>
        </div>
        <button type="button" class="btn btn-primary new_lager_item">+ Добавить наименование</button>
    </div>

    <div class="lager-table-wrap">
        <table class="lager_table">
            <thead>
                <tr>
                    <th>Наименование</th>
                    <th>На складе</th>
                    <th>Ед.</th>
                    <th>Действие</th>
                    <th>Кол-во</th>
                    <th></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

</div>

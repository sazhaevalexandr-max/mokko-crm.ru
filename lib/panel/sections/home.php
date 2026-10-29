<?php if (!defined('MOKKO')) { die; } ?>
<div class="layer active" id="home">
<!---HOME-->

<center id="load_mainbar"><img src="<?= BASE_PATH ?>/images/30.gif"></center>


<div class="alert top_alert" role="alert">
</div>

<div class="grid-row startup" id="startup">
<div class="grid-col l-pulse-grid-1-1">
<div id="ember4412" class="widget--static ember-view widget square_no">    <div class="widget--heading">
<h3>С чего начать?</h3>
</div>


<div class="widget--body">
<div class="widget-inner">
<div class="table-widget l-checklist-table navigation">


<div class="l-checklist-row">
<div class="l-checklist-cell l-checklist-icon">
<img src="<?= BASE_PATH ?>/images/mokko_icons/house.png" style="width: 50px;border-radius: 100px;border: 1px solid #439fe0;margin: auto;">

</div>
<div class="l-checklist-cell false false">
<h4>
<a href="#places" class="ember-view">
Добавьте кофейню
</a>
</h4>

<p class="l-checklist-hint l-checklist-hint-text hide-when-mobile">
Создайте новую кофейню, расскажите  о ней, чтобы  оптимально настроить CRM.
</p>
</div>
</div>

<div class="l-checklist-row">
<div class="l-checklist-cell l-checklist-icon">
<img src="<?= BASE_PATH ?>/images/mokko_icons/cup.png" style="width: 50px;border-radius: 100px;border: 1px solid #439fe0;margin: auto;">
</div>
<div class="l-checklist-cell false false">
<h4>
<a href="#menu" class="ember-view">
Заполните меню
</a>
</h4>

<p class="l-checklist-hint l-checklist-hint-text hide-when-mobile">
Добавьте категории и сформируйте своё меню.
</p>
</div>
</div>

<div class="l-checklist-row">
<div class="l-checklist-cell l-checklist-icon">
<img src="<?= BASE_PATH ?>/images/mokko_icons/tap.png" style="width: 50px;border-radius: 100px;border: 1px solid #439fe0;margin: auto;">
</div>
<div class="l-checklist-cell false false">
<h4>
<a href="#home" class="ember-view crm_action">
Начните работать в CRM
</a>
</h4>

<p class="l-checklist-hint l-checklist-hint-text hide-when-mobile">
Как только вы выполните минимальные настройки, вы сможете начать работу прямо сейчас.
</p>
</div>
</div>

</div>
</div>

</div>
</div>
</div>
</div>
<!---END HOME-->




<div id="statistic">
    
<div class="row" style="padding-bottom:0px">
<div class="col-md-5">
        <span>Показать статистику на период с</span>
        <form class="form-inline">
            <div class="form-group jalendar-input col-md-6">
                <input type="text" class="form-control start_date datepicker" placeholder="Начало периода">
                    <div id="start_day" class="jalendar"></div>
                    </div>
            
            <div class="form-group jalendar-input col-md-6">
                <input type="text" class="form-control stop_date datepicker" placeholder="Конец периода">
                    <div id="stop_day" class="jalendar"></div>
                    </div>
            </form>
    </div>
<div class="col-md-4">
    <div class="col-md-12 col-12 places_lager">
        <select class="form-control" name="place" style="margin-top: 20px;">
            <option disabled selected>Загрузка...<option>
            <option>
        </select>
    </div>
</div>

    <div class="col-md-3 col-12">
        <div style="margin-top: 15px;">
        <button type="submit" class="btn btn-primary col-md-12 col-12  show_stat">Показать</button>
        </div>
    </div>
    
    <div class="col-md-12" style="float: left;">
        <div>
<button type="submit" class="btn btn-link today">сегодня</button>
            <button type="submit" class="btn btn-link yesterday">вчера</button>
            <button type="submit" class="btn btn-link week">неделя</button>
            <button type="submit" class="btn btn-link month">месяц</button>
        </div>
    </div>
</div>

<div class="col-md-12">
    <hr/>
    <div class="progress" style="height: 20px;" id="progress_stat">
        <div class="progress-bar" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 0%;">

        </div>
        
    </div>
    <br/>
    </div>


<div id="charts_and_tables">
<div class="col-md-6">
<div class="col-md-6">
    <div class="panel panel-default">
        <div class="panel-heading">Выручка</div>
        <h1 class="proceeds" style="padding-top:30px;padding-bottom:30px;margin: 0px;font-size:27px;"><center>0 р.</center></h1>
    </div>
</div>

<div class="col-md-6">
    <div class="panel panel-default">
        <div class="panel-heading">Средний чек</div>
        <h1 class="bg-primary a_b" style="padding-top:25px;padding-bottom:25px;margin: 0px;font-size:27px;"><center>0 р.</center></h1>
    </div>
</div>

<hr/>
</div>

<div class="col-md-6">
    <div class="panel panel-default" id="sales">
        <div class="panel-heading">Продано</div>
        <div class="panel-body">
    <table class="table table_stat"> <thead> <tr>  <th>Наименование</th> <th>Количество</th> <th>На сумму</th> </tr> </thead>
        <tbody>

</tbody>
</table>
    <span class="pull-right total_price">Итого: <b>0</b> руб.</span>
        </div>
    </div>
</div>

<div class="col-md-12">
    <div class="panel panel-default" id="staff_stat">
        <div class="panel-heading">Выработка</div>
        <div class="panel-body">
            <table class="table table_stat"> <thead> <tr>  <th>Сотрудник</th> <th>Отработано</th> <th>Выработка</th></tr> </thead>
                <tbody>
                </tbody>
            </table>
            
        </div>
    </div>
</div>

<div class="col-md-12">
    <div class="panel panel-default">
        <div class="panel-heading chart-head">Выручка по дням
            <span class="chart-keys"><span><i class="k-sum"></i>Выручка</span><span><i class="k-avg"></i>Средний чек</span></span>
        </div>
        <div class="panel-body">
            <div class="mk-chart" id="staff_chart"></div>
        </div>
    </div>
</div>

<div class="col-md-12">
    <div class="panel panel-default">
        <div class="panel-heading">Доля продаж по позициям</div>
        <div class="panel-body">
            <div class="mk-chart" id="piechart"></div>
        </div>
    </div>
</div>
</div>


<div class="row plan-limits-row">
    <section class="plan-limits">
        <h3>Лимиты тарифа</h3>
        <ul>
            <li><b>2</b><span>кофейни</span></li>
            <li><b>5</b><span>категорий меню</span></li>
            <li><b>40</b><span>позиций в меню</span></li>
            <li><b>4</b><span>аккаунта сотрудников</span></li>
        </ul>
    </section>
</div>


</div>

<!---
<div class="col-md-12">
    <div class="panel panel-default" style="padding-bottom:30px;">
        <div class="panel-heading">Динамика по дням</div>
        <div class="panel-body">
<div class="ct-chart4 col-md-12 chrt" style="height:400px;"></div>
        </div>
    </div>
</div>


<div class="col-md-12">
    <div class="panel panel-default" style="padding-bottom:30px;">
        <div class="panel-heading">Рабы</div>
        <div class="panel-body">
<div class="ct-chart3 col-md-12 chrt" style="height:300px;"></div>
        </div>
    </div>
</div>
-->


</div>


<?php if (!defined('MOKKO')) { die; } ?>
<div class="layer" id="menu">


<div>


<ul class="nav nav-tabs special_tabs category_tabs" role="tablist">
<li role="presentation" class="active"><a href="#category" aria-controls="category" role="tab" data-toggle="tab"><i class="glyphicon glyphicon-plus"></i>Категория</a></li>
</ul>


<div class="tab-content">

<!--category--
<div role="tabpanel" class="tab-pane active" id="category">

<div class="empty_contend"><img class="icons_at_tabs" src="<?= BASE_PATH ?>/images/mokko_icons/folder.png"><br/>
<h4>Для начала формирования меню, создайте категории, <br/>например <b>"Напитки"</b></h4>
<button type="submit" style="float: none;" class="btn btn-primary col-md-4 col-8 create_category" href="#">Создать категорию</button>
</div>
</div>
-->


<div role="tabpanel" class="tab-pane" id="category1">

<div class="editor_item row">

<div class="col-8 col-md-3">
<input type="text" class="form-control" placeholder="Название" value="Напитки"/>
</div>

<div class="col-4 col-md-6">
<input id="package1" class="package-toggle toggle-round-flat" type="checkbox" name="lager" data-price="1000" checked>
<label for="package1"></label>
<span class="switch-label">Показывать в кассе</span>
</div>


<div class="col-12 col-md-3">
<button type="submit" style="float: none;" class="pull-right btn btn-primary col-12 no_mobile disabled" href="#">Сохранить</button>
</div>
</div>
<!---items-->

<!---if empty ---
<div class="empty_contend"><img class="icons_at_tabs" src="<?= BASE_PATH ?>/images/mokko_icons/items.png"><br/>
<h4>Заполните ваше меню, добавив<br/>например <b>"Кофе Эспрессо"</b></h4>
<button type="submit" style="float: none;" class="btn btn-primary col-md-4 col-8 create_item" href="#">Добавить пункт</button>
</div>

--->
<button type="submit" style="float: none;margin-top: 10px;margin-bottom: 10px;" class="create_item btn btn-primary col-md-4 col-12 offset-md-4" href="#">Добавить наименование</button>

<ul class="list-group">
<!--
<li class="list-group-item"><b>Кофе Капучино</b><span>0</span> р. <i class="items_edit pull-right glyphicon glyphicon-remove remove_item_cull"></i><i class="pull-right glyphicon glyphicon-pencil"></i></li>
<li class="list-group-item"><b>Кофе Эспрессо</b><span>0</span> р. <i class="items_edit pull-right glyphicon glyphicon-remove remove_item_cull"></i><i class="pull-right glyphicon glyphicon-pencil"></i></li>
<li class="list-group-item"><b>Кофе Латте</b><span>0</span> р. <i class="items_edit pull-right glyphicon glyphicon-remove remove_item_cull"></i><i class="pull-right glyphicon glyphicon-pencil"></i></li>
-->
</ul>

</div>
</div>

</div>



</div>


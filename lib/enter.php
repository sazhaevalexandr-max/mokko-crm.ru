<?php
    if(!defined('MOKKO'))die;
    ?>

<!--[if lt IE 7]> <html class="no-js ie6 oldie" lang="ru" > <![endif]-->
<!--[if IE 7]>    <html class="no-js ie7 oldie" lang="ru" > <![endif]-->
<!--[if IE 8]>    <html class="no-js ie8 oldie" lang="ru" > <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="ru" > <!--<![endif]-->

<head prefix="og: https://ogp.me/ns# article: https://ogp.me/ns/article#">

<meta charset="utf-8">

<meta name="description" content="сrm erp для автоматизации кофейни">
<meta name="keywords" content="site, google, internet">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<meta name="robots" content="index,follow">

<title>MOKKO CRM</title>



<link href="<?= BASE_PATH ?>/css/bootstrap.css" rel="stylesheet">

<link rel="stylesheet" type="text/css" href="<?= BASE_PATH ?>/css/panel.css" />
<link rel="stylesheet" type="text/css" href="<?= BASE_PATH ?>/css/jquery-confirm.css" />

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
<script type="text/javascript" src="<?= BASE_PATH ?>/js/bootstrap.min.js" ></script>
<script type="text/javascript" src="<?= BASE_PATH ?>/js/jquery-confirm.js"></script>

<!-- Custom Fonts -->
<link href='//fonts.googleapis.com/css?family=Open+Sans:300,400,700&amp;subset=latin,cyrillic' rel='stylesheet' type='text/css'>



<meta property="og:title" content="MOKKO CRM" />
<meta property="og:type" content="website" />
<meta property="og:url" content="http://mokko-crm.ru/" />
<meta property="og:site_name" content="MOKKO CRM" />

<script>

var key = <?= mokko_js(isset($_SESSION['key']) ? (string)$_SESSION['key'] : '') ?>;
var uid = <?= mokko_js(isset($_SESSION['uid']) ? (string)$_SESSION['uid'] : '') ?>;
var sid = <?= mokko_js(isset($_SESSION['sid']) ? (string)$_SESSION['sid'] : '') ?>;

</script>


</head>


<body class="layout">

<div id="pageloader">

<div class="loader-item fa fa-spin colored-border"></div>
</div>

<script type="text/javascript" src="<?= BASE_PATH ?>/js/place.js"></script>
<? require_once('metrika.php'); ?>

</body>
</html>


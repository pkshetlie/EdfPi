#!/usr/bin/php5
<?php

header('Content-type: text/html; charset=utf-8');

require_once('lib/TeleInfo.php');

$teleInfo = new TeleInfo();
$teleInfo->registerConsomation();
$teleInfo->registerPuissance();

?>
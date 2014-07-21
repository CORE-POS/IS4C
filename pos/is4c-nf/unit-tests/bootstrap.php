<?php
include(dirname(__FILE__).'/../lib/LocalStorage/UnitTestStorage.php');
$config = dirname(__FILE__).'/../ini.php';
global $CORE_LOCAL;
$CORE_LOCAL = new UnitTestStorage();
include($config);
include(dirname(__FILE__).'/../lib/AutoLoader.php');
AutoLoader::loadMap();
CoreState::loadParams();
?>

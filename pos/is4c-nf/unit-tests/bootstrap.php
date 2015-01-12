<?php
include(dirname(__FILE__).'/../lib/LocalStorage/UnitTestStorage.php');
include(dirname(__FILE__).'/../lib/LocalStorage/CoreLocal.php');
include(dirname(__FILE__).'/../lib/LocalStorage/WrappedStorage.php');
$config = dirname(__FILE__).'/../ini.php';
CoreLocal::setHandler('UnitTestStorage');
CoreLocal::refresh();
global $CORE_LOCAL;
$CORE_LOCAL = new WrappedStorage();
include($config);
include(dirname(__FILE__).'/../lib/AutoLoader.php');
AutoLoader::loadMap();
CoreState::loadParams();
?>

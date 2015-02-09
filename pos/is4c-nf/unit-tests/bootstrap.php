<?php
include(dirname(__FILE__).'/../lib/LocalStorage/UnitTestStorage.php');
include(dirname(__FILE__).'/../lib/LocalStorage/CoreLocal.php');
include(dirname(__FILE__).'/../lib/LocalStorage/WrappedStorage.php');
CoreLocal::setHandler('UnitTestStorage');
global $CORE_LOCAL;
$CORE_LOCAL = new WrappedStorage();
CoreLocal::refresh();
define('CONF_LOADED', true);
include(dirname(__FILE__).'/../lib/AutoLoader.php');
AutoLoader::loadMap();
CoreState::loadParams();


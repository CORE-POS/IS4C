<?php
include(dirname(__FILE__).'/../../pos/is4c-nf/lib/LocalStorage/UnitTestStorage.php');
include(dirname(__FILE__).'/../../pos/is4c-nf/lib/LocalStorage/CoreLocal.php');
include(dirname(__FILE__).'/../../pos/is4c-nf/lib/LocalStorage/WrappedStorage.php');
CoreLocal::setHandler('UnitTestStorage');
global $CORE_LOCAL;
$CORE_LOCAL = new WrappedStorage();
CoreLocal::refresh();
define('CONF_LOADED', true);
include(dirname(__FILE__).'/../../pos/is4c-nf/lib/AutoLoader.php');
AutoLoader::loadMap();
CoreState::loadParams();
AutoLoader::blacklist('CoopCred');
AutoLoader::blacklist('CCredMembershipsModel');
AutoLoader::blacklist('CCredProgramsModel');


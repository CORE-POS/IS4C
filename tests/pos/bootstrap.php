<?php
include(dirname(__FILE__).'/../../pos/is4c-nf/lib/LocalStorage/UnitTestStorage.php');
include(dirname(__FILE__).'/../../pos/is4c-nf/lib/LocalStorage/CoreLocal.php');
include(dirname(__FILE__).'/../../pos/is4c-nf/lib/LocalStorage/WrappedStorage.php');
CoreLocal::setHandler('COREPOS\\pos\\lib\\LocalStorage\\UnitTestStorage');
global $CORE_LOCAL;
$CORE_LOCAL = new COREPOS\pos\lib\LocalStorage\WrappedStorage();
CoreLocal::refresh();
define('CONF_LOADED', true);
include(dirname(__FILE__).'/../../pos/is4c-nf/lib/AutoLoader.php');
COREPOS\pos\lib\LocalStorage\LaneConfig::refresh();
COREPOS\pos\lib\LocalStorage\LaneCache::clear();
AutoLoader::loadMap();
COREPOS\pos\lib\CoreState::loadParams();
CoreLocal::set('CashierNo', 0);
CoreLocal::set('memberID', 0);
AutoLoader::ignoreClass('CoopCred');
AutoLoader::ignoreClass('CCredMembershipsModel');
AutoLoader::ignoreClass('CCredProgramsModel');


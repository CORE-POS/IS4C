<?php
include(dirname(__FILE__).'/../../pos/is4c-nf/lib/LocalStorage/UnitTestStorage.php');
$config = dirname(__FILE__).'/../../pos/is4c-nf/ini.php';
global $CORE_LOCAL;
$CORE_LOCAL = new UnitTestStorage();
include($config);
include(dirname(__FILE__).'/../../pos/is4c-nf/lib/AutoLoader.php');
AutoLoader::LoadMap();
?>

<?php

/**
  Initialize environment & session so testing behaves
  correctly
*/
if (!class_exists('AutoLoader')) include(dirname(__FILE__).'/../lib/AutoLoader.php');

$CORE_LOCAL->set("parse_chain",'');
$CORE_LOCAL->set("preparse_chain",'');
$CORE_LOCAL->set("postparse_chain",'');

AutoLoader::loadMap();
CoreState::initiate_session();

?>

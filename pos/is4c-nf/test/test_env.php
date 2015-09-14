<?php

/**
  Initialize environment & session so testing behaves
  correctly
*/
if (!class_exists('AutoLoader')) include(dirname(__FILE__).'/../lib/AutoLoader.php');

CoreLocal::set("parse_chain",'');
CoreLocal::set("preparse_chain",'');
CoreLocal::set("postparse_chain",'');

AutoLoader::loadMap();
CoreState::initiate_session();


<?php

/**
  Initialize environment & session so testing behaves
  correctly
*/
if (!class_exists('AutoLoader')) include(dirname(__FILE__).'/../lib/AutoLoader.php');

AutoLoader::loadMap();
CoreState::initiate_session();


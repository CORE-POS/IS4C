<?php

include_once(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

/**
*/
class FreshDeals extends \COREPOS\Fannie\API\FanniePlugin 
{

    /**
      Desired settings. These are automatically exposed
      on the 'Plugins' area of the install page and
      written to ini.php
    */
    public $plugin_settings = array(
    );

    public $plugin_description = 'Convenience functions for WFC\'s Fresh Deals sale cycle';
}


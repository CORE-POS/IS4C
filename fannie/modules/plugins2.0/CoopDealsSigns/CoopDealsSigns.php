<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

*********************************************************************************/

include_once(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

/**
*/
class CoopDealsSigns extends \COREPOS\Fannie\API\FanniePlugin 
{

    /**
      Desired settings. These are automatically exposed
      on the 'Plugins' area of the install page and
      written to ini.php
    */
    public $plugin_settings = array(
    );

    public $plugin_description = 'Plugin for printing Co+op Deals Signs. Co+op Deals
        logos are owned by National Co-op Grocers (ncg.coop) and may only be used by
        NCG members participating in the program.';
}


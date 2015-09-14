<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of IT CORE.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include_once(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

/**
*/
class EmailReporting extends \COREPOS\Fannie\API\FanniePlugin {

    /**
      Desired settings. These are automatically exposed
      on the 'Plugins' area of the install page and
      written to ini.php
    */
    public $plugin_settings = array(
    'EmailReportingDB' => array('default'=>'email_reporting','label'=>'Database',
            'description'=>'Database for logging usage'),
    );

    public $plugin_description = 'Plugin for logging email usage';


    public function settingChange()
    {
        $db_name = $FANNIE_PLUGIN_SETTINGS['EmailReportingDB'];
        if (empty($db_name)) return;

        $dbc = FannieDB::get($db_name);
        if (!$dbc->tableExists('EmailUsageLog')) {
            $obj = new EmailUsageLogModel($dbc);
            $obj->create();
        }
    }
}


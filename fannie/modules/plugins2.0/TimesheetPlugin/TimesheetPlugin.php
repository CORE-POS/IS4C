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

global $FANNIE_ROOT;
if (!class_exists('FannieAPI'))
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
if (!class_exists('FannieAuth'))
    include($FANNIE_ROOT.'classlib2.0/auth/FannieAuth.php');

/**
*/
class TimesheetPlugin extends FanniePlugin {

    /**
      Desired settings. These are automatically exposed
      on the 'Plugins' area of the install page and
      written to ini.php
    */
    public $plugin_settings = array(
    'TimesheetDatabase' => array('default'=>'core_timesheet','label'=>'Database',
            'description'=>'Database to store timesheet information. Can
                    be one of the default CORE databases or a 
                    separate one.')
    );

    public $plugin_description = 'Plugin for timeclock operations';


    public function setting_change()
    {
        global $FANNIE_ROOT, $FANNIE_PLUGIN_SETTINGS;

        $db_name = $FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'];
        if (empty($db_name)) return;

        $dbc = FannieDB::get($db_name);

        $errors = array();

        $models = array(
            'PayPeriodsModel',
            'ShiftsModel',
            'TimesheetModel',
        );

        foreach ($models as $model) {
            $obj = new $model($dbc);
            $obj->create();
        }
    }

    public function plugin_enable(){
        ob_start();
        $try = FannieAuth::createClass('timesheet_access',
            'Grants user permission to use the
             Timesheet plugin');
        ob_end_clean();
        if ($try === False){
            echo 'Failed to create authentication class.
                Make sure authentication is enabled in
                Fannie and you\'re logged in as an admin
                then try turning Timesheet on and off
                again';
        }
    }
}

?>

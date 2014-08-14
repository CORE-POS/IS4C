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
if (!class_exists('FannieAPI')) include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

/**
*/
class CalendarPlugin extends FanniePlugin {

    /**
      Desired settings. These are automatically exposed
      on the 'Plugins' area of the install page and
      written to ini.php
    */
    public $plugin_settings = array(
    'CalendarDatabase' => array('default'=>'core_calendar','label'=>'Database',
            'description'=>'Database to calendars. Can
                    be one of the default CORE databases or a 
                    separate one.')
    );

    public $plugin_description = 'Plugin for calendars';


    public function setting_change()
    {
        global $FANNIE_ROOT, $FANNIE_PLUGIN_SETTINGS;

        $db_name = $FANNIE_PLUGIN_SETTINGS['CalendarDatabase'];
        if (empty($db_name)) return;

        $dbc = FannieDB::get($db_name);

        $tables = array(
            'AccountClasses',
            'Attendees',
            'Calendars',
            'MonthviewEvents',
            'Permissions',
        );
        foreach($tables as $t){
            $model_class = $t.'Model';
            if (!class_exists($model_class))
                include_once(dirname(__FILE__).'/models/'.$model_class.'.php');
            $instance = new $model_class($dbc);
            $instance->create();        
        }

        if ($dbc->table_exists('account_classes')) {
            $model = new AccountClassesModel($dbc);
            /* populate account classes */
            $classes = array(
                1 => 'VIEWER',
                2 => 'CONTRIBUTOR',
                3 => 'ADMIN',
                4 => 'OWNER'
            );
            foreach ($classes as $id => $desc) {
                $model->classID($id);
                $model->classDesc($desc);
                $model->save();
            }
        }
    }

    public function plugin_enable(){
    }
}

?>

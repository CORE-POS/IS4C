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

/**
*/
class OverShortTools extends FanniePlugin {

    /**
      Desired settings. These are automatically exposed
      on the 'Plugins' area of the install page and
      written to ini.php
    */
    // 17Dec13 EL Change code style and sequence of elements, putting 'label' first.
    public $plugin_settings = array(
        'OverShortDatabase' => array(
            'label'=>'Database',
            'default'=>'core_overshort',
            'description'=>'Database to store tables of plugin-specific
                    tender counts and related info.
                    Can be one of the default CORE databases or a separate one.'
        )
    );

    public $plugin_description = 'Plugin for comparing tender totals counted by cashiers 
            to totals from the POS transactions database.';

    public function setting_change(){
        global $FANNIE_ROOT, $FANNIE_PLUGIN_SETTINGS;

        $db_name = $FANNIE_PLUGIN_SETTINGS['OverShortDatabase'];
        if (empty($db_name)) return;

        // Creates the database if it doesn't already exist.
        $dbc = FannieDB::get($db_name);
        
        $tables = array(
            'DailyChecks',
            'DailyCounts',
            'DailyDeposit',
            'DailyNotes',
            'OverShortsLog'
        );

        foreach($tables as $t){
            $model_class = $t.'Model';
            if (!class_exists($model_class))
                include_once(dirname(__FILE__).'/models/'.$model_class.'.php');
            $instance = new $model_class($dbc);
            $instance->create();        
        }
    }

    public static $EXCLUDE_TENDERS = array('MA', 'RR');
}

?>

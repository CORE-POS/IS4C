<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

/**
*/
class OpenBookFinancingV2 extends \COREPOS\Fannie\API\FanniePlugin 
{
    public $plugin_settings = array(
    'ObfDatabaseV2' => array('default'=>'OpenBookFinancingV2','label'=>'Database',
            'description'=>'Database for storing OBF info'),
    );

    public $plugin_description = 'WFC Plugin for weekly Open Book Financing';

    public function setting_change()
    {
        global $FANNIE_ROOT, $FANNIE_PLUGIN_SETTINGS;

        $db_name = $FANNIE_PLUGIN_SETTINGS['ObfDatabaseV2'];
        if (empty($db_name)) return;

        // Creates the database if it doesn't already exist.
        $dbc = FannieDB::get($db_name);
        
        $tables = array(
            'ObfWeeks',
            'ObfCategories',
            'ObfCategorySuperDeptMap',
            'ObfLabor',
            'ObfSalesCache',
            'ObfQuarters',
        );

        foreach($tables as $t){
            $model_class = $t.'ModelV2';
            if (!class_exists($model_class))
                include_once(dirname(__FILE__).'/models/'.$model_class.'.php');
            $instance = new $model_class($dbc);
            $instance->create();        
        }
    }
}


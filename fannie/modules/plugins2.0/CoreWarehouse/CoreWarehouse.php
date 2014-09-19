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
class CoreWarehouse extends FanniePlugin {

    /**
      Desired settings. These are automatically exposed
      on the 'Plugins' area of the install page and
      written to ini.php
    */
    public $plugin_settings = array(
    'WarehouseDatabase' => array('default'=>'core_warehouse','label'=>'Database',
            'description'=>'Database to store transaction information. Can
                    be one of the default CORE databases or a 
                    separate one.')
    );

    public $plugin_description = 'Plugin for managing data warehouse. No end-user facing
        functionality here. The plugin is just a set of tools for creating summary
        tables and loading historical transaction data into said tables. Reports may
        utilize the warehouse when available. In some cases it may just mean simpler
        queries; in others there may be a performance benefit to querying
        pre-aggregated data.';


    public function setting_change(){
        global $FANNIE_ROOT, $FANNIE_PLUGIN_SETTINGS;

        $db_name = $FANNIE_PLUGIN_SETTINGS['WarehouseDatabase'];
        if (empty($db_name)) return;

        $dbc = FannieDB::get($db_name);
        
        if (!class_exists('WarehouseModel'))
            include(dirname(__FILE__).'/models/WarehouseModel.php');

        $tables = array(
            'SumDeptSalesByDay',
            'SumDiscountsByDay',
            'SumMemSalesByDay',
            'SumMemTypeSalesByDay',
            'SumRingSalesByDay',
            'SumTendersByDay',
            'SumUpcSalesByDay',
            'TransactionSummary'
        );

        foreach($tables as $t){
            $model_class = $t.'Model';
            if (!class_exists($model_class))
                include_once(dirname(__FILE__).'/models/'.$model_class.'.php');
            $instance = new $model_class($dbc);
            $instance->create();        
        }
    }
}

?>

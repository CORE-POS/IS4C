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

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

/**
*/
class StaffArPayrollDeduction extends \COREPOS\Fannie\API\FanniePlugin 
{
    public $plugin_settings = array(
    'StaffArPayrollDB' => array('default'=>'StaffArPayrollDB','label'=>'Database',
            'description'=>'Database for related information. Stores payroll processing
            dates and which accounts are part of the program.'),
    'StaffArPayrollEmpNo' => array('default'=>1001, 'label'=>'Emp#',
            'description'=>'Employee number for transaction records generated via plugin'),
    'StaffArPayrollRegNo' => array('default'=>20, 'label'=>'Reg#',
            'description'=>'Register number for transaction records generated via plugin'),
    );

    public $plugin_description = 'Plugin for scheduling automated AR payments';

    public function settingChange() {
        global $FANNIE_ROOT, $FANNIE_PLUGIN_SETTINGS;

        $db_name = $FANNIE_PLUGIN_SETTINGS['StaffArPayrollDB'];
        if (empty($db_name)) return;

        $dbc = FannieDB::get($db_name);
        
        $tables = array(
            'StaffArAccounts',
            'StaffArDates',
        );

        foreach($tables as $t) {
            $model_class = $t.'Model';
            if (!class_exists($model_class)) {
                include_once(dirname(__FILE__).'/models/'.$model_class.'.php');
            }
            $instance = new $model_class($dbc);
            $instance->create();        
        }
    }
}


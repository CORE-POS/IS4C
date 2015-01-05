<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

/**
*/
class CwReloadYesterdayTask extends FannieTask 
{
    public $name = 'CORE Warehouse Maintenance';

    public $description = 'Rebuilds summary tables from
yesterday\'s transaction data';

    public $default_schedule = array(
        'min' => 20,
        'hour' => 2,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        if (!class_exists('CwLoadDataPage')) {
            include(dirname(__FILE__) . '/CwLoadDataPage.php');
        }
        $obj = new CwLoadDataPage();
        
        $yesterday = strtotime('yesterday');
        $year = date('Y', $yesterday);
        $month = date('n', $yesterday);
        $day = date('j', $yesterday);

        $plugin_settings = $this->config->get('PLUGIN_SETTINGS');
        $archive = $this->config->get('ARCHIVE_DB');

        $con = FannieDB::get($plugin_settings['WarehouseDatabase']);
        foreach ($obj->getModels() as $class) {
            $obj = new $class($con);
            $obj->refresh_data($archive, $month, $year, $day);
        }
    }

}



<?php
/*******************************************************************************

    Copyright 2016 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
}

class DashBoard extends FannieRESTfulPage
{
    public $description = '[Dashboard] displays current system status';
    protected $header = 'Dashboard';
    protected $title = 'Dashboard';

    public function get_view()
    {
        $mods = FannieAPI::listModules('\COREPOS\Fannie\API\monitor\Monitor');
        $cache = unserialize(COREPOS\Fannie\API\data\DataCache::getFile('forever', 'monitoring'));
        if (!$cache) {
            return '<div class="alert alert-danger">No Dashboard data available. Is the Monitoring Task enabled?</div>';
        }
        ob_start();
        foreach ($mods as $class) {
            if (!isset($cache[$class])) {
                echo "No data for $class<br />";
            } else {
                $obj = new $class($this->config);
                $out = $obj->display($cache[$class]);
                echo '<strong>' . $class . '</strong><br />';
                echo $out . "<br />";
            }
        }

        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec();


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

class WfcHtLib {

    static public function hours_dbconnect()
    {
        global $FANNIE_ROOT, $FANNIE_PLUGIN_SETTINGS;
        if (!class_exists("SQLManager")) {
            require($FANNIE_ROOT.'src/SQLManager.php');
        }
        if (!class_exists('FannieAPI')) {
            include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
        }

        return FannieDB::get($FANNIE_PLUGIN_SETTINGS['WfcHtDatabase']);
    }
}


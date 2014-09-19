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
class WfcClassMenuTask extends FannieTask 
{
    public $name = 'WFC Class Menu Task';

    public $description = 'Tracks usage of WFC virtual coupon and updates custdata.blueLine
    to indicate whether the coupon is available. Replaces older script "nightly.memcoupon.php".';

    public function run()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $dbc->query('DELETE FROM QuickLookups WHERE lookupSet=708 AND sequence < 32766');
        $dbc->query('INSERT QuickLookups (lookupSet, label, action, sequence) 
                    SELECT 708, u.description, p.upc, TO_DAYS(expires)-(1970*365) 
                    FROM products AS p 
                        INNER JOIN productUser AS u ON p.upc=u.upc 
                        LEFT JOIN productExpires AS e ON p.upc=e.upc 
                    WHERE p.department=708 AND CURDATE() <= e.expires');

        SyncLanes::pushTable('QuickLookups');
    }
}


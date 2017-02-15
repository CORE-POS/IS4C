<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

namespace COREPOS\pos\lib\Kickers;
use COREPOS\pos\lib\Database;
use \CoreLocal;

/**
  @class WFC_Kicker
  Opens drawer for cash, credit card over $25,
  credit card refunds, and stamp sales
*/
class WFC_Kicker extends Kicker 
{

    public function doKick($trans_num)
    {
        $dbc = Database::tDataConnect();

        $query = "SELECT trans_id 
                  FROM localtranstoday 
                  WHERE ( 
                    (trans_subtype = 'CA' and total <> 0) 
                    OR upc='0000000001065'
                  ) AND " . $this->refToWhere($trans_num);

        $result = $dbc->query($query);
        $numRows = $dbc->numRows($result);

        $ret = ($numRows > 0) ? true : false;

        // use session to override default behavior
        // based on specific cashier actions rather
        // than transaction state
        $override = CoreLocal::get('kickOverride');
        CoreLocal::set('kickOverride',false);
        if ($override === true) $ret = true;

        return $ret;
    }

    public function kickOnSignIn() {
        return false;
    }
}


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

use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\DeptLib;
use COREPOS\pos\lib\TotalActions\TotalAction;

class AutomaticEquityAction extends TotalAction
{
    /**
      If the member has an entry in the automatic
      equity table and no equity has been added to
      the transaction, add an open ring
    */
    public function apply()
    {
        $card_no = CoreLocal::get('memberID');
        $dbc = Database::pDataConnect();
        $prep = $dbc->prepare('
            SELECT amount, department
            FROM AutomaticEquity
            WHERE cardNo=?
        ');
        $row = $dbc->getRow($prep, array($card_no));
        if ($row) {
            $dbc = Database::tDataConnect();
            $prep = $dbc->prepare('
                SELECT trans_id
                FROM localtemptrans
                WHERE department=?
            ');
            $exists = $dbc->getValue($prep, array($row['department']));
            if (!$exists) {
                DeptLib::deptkey($row['amount']*100, $row['department']*10);
            }
        }
    }
}


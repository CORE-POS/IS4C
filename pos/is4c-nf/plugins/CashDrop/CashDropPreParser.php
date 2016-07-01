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

use COREPOS\pos\parser\PreParser;

class CashDropPreParser extends PreParser {

    function check($str)
    {
        // only check & warn once per transaction
        if (CoreLocal::get('cashDropWarned') == True) return False;

        // checking one time
        CoreLocal::set('cashDropWarned',True);

        // cannot check in standalone
        if (CoreLocal::get('standalone') == 1) return False;

        // lookup cashier total
        $db = Database::mDataConnect();
        $q = sprintf("SELECT sum(-total) FROM dtransactions WHERE
            trans_subtype='CA' AND trans_status <> 'X' AND emp_no=%d",
            CoreLocal::get('CashierNo'));
        $r = $db->query($q);
        $ttl = 0;
        if ($db->num_rows($r) > 0) {
            $row = $db->fetch_row($r);
            $ttl = $row[0];
        }

        if ($ttl > CoreLocal::get('cashDropThreshold'))
            return True;
        else
            return False;
    }

    function parse($str){
        // modify input to trigger CashDropParser
        return 'DROPDROP'.$str;
    }
}

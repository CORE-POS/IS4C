<?php
/*******************************************************************************

    Copyright 2007 Whole Foods Co-op

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

use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\parser\Parser;

class TenderKeyCoopCred extends Parser 
{

    function check($str)
    {
        if (
            substr($str, -2) == "TQ" && strlen($str) >=3 &&
            is_numeric(substr($str, 0, strlen($str)-2))
            ) {
            return true;
        } else if ($str == "TQ") {
            return true;
        }

        return false;
    }

    function parse($str)
    {
        global $CORE_LOCAL;
        $my_url = MiscLib::base_url();

        $amt = substr($str,0,strlen($str)-2);
        if ($amt === "") {
            $amt = 100*$CORE_LOCAL->get("amtdue");
        }
        $ret = $this->default_json();

        $CORE_LOCAL->set("tenderTotal",$amt);
        $ret['main_frame'] = $my_url.'plugins/CoopCred/tenderlist_coopCred.php';
        //$ret['main_frame'] = $my_url.'gui-modules/tenderlist_coopCred.php';

        return $ret;
    }

    function doc()
    {
        return "<table cellspacing=0 cellpadding=3 border=1>
            <tr>
                <th>Input</th><th>Result</th>
            </tr>
            <tr>
            <td><i>[amount]</i>TQ<i></td>
            <td>Display a picklist of Coop Cred tenders.
            The <i>amount</i>, if specified, or else the remaining Amount Due,
            will be tendered.
            </td>
            </tr>
            </table>";
    }
}


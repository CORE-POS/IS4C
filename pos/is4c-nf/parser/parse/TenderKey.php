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

namespace COREPOS\pos\parser\parse;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\parser\Parser;

class TenderKey extends Parser 
{

    function check($str)
    {
        if (substr($str, -2) == "TT" && strlen($str) >=3 && is_numeric(substr($str, 0, strlen($str)-2))) {
            return true;
        } elseif ($str == "TT") {
            return true;
        }

        return false;
    }

    function parse($str)
    {
        $my_url = MiscLib::base_url();

        $amt = substr($str,0,strlen($str)-2);
        if ($amt === "") {
            $amt = 100*$this->session->get("amtdue");
        }
        $ret = $this->default_json();
        $ret['main_frame'] = $my_url.'gui-modules/tenderlist.php?amt=' . $amt;

        return $ret;
    }

    function doc()
    {
        return "<table cellspacing=0 cellpadding=3 border=1>
            <tr>
                <th>Input</th><th>Result</th>
            </tr>
            <tr>
                <td><i>amount</i>DP<i>department</i>0</td>
                <td>Ring up <i>amount</i> to the specified
                <i>department</i>. The trailing zero is
                necessary for historical purposes</td>
            </tr>
            </table>";
    }
}


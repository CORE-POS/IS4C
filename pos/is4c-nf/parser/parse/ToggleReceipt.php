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

class ToggleReceipt extends Parser {
    
    function check($str){
        if ($str == "NR")
            return True;
        return False;
    }

    function parse($str)
    {
        $rt = $this->session->get("receiptToggle");
        $this->session->set("receiptToggle", $rt == 1 ? 0 : 1);
        $ret = $this->default_json();
        // redirect to main screen so receipt icon reloads
        $ret['main_frame'] = MiscLib::base_url().'gui-modules/pos2.php';
        return $ret;
    }

    function doc(){
        return "<table cellspacing=0 cellpadding=3 border=1>
            <tr>
                <th>Input</th><th>Result</th>
            </tr>
            <tr>
                <td>NR</td>
                <td>Disable receipt printing. 
                </td>
            </tr>
            </table>";
    }
}


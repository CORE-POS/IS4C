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

namespace COREPOS\pos\parser\preparse;
use COREPOS\pos\parser\PreParser;

class MemStatusToggle extends PreParser {

    function check($str)
    {
        // Andy: theory is this shortcircuits steering
        return false;
        /*
        if (substr($str,0,4) == "MSTG"){
            return True;
        }
        return False;    
        */
    }

    function parse($str)
    {
        /*
        $remainder = "";
        if (substr($str,0,4) == "MSTG"){
            if (CoreLocal::get("memType") != 1 || CoreLocal::get("memType") != 2) {
                CoreLocal::set("isMember", 1);
                CoreLocal::set("memType", 1);
                CoreLocal::set("boxMsg", "Member Status Toggled!");
            } else {
                CoreLocal::set("boxMsg", "Cannot UNset a member status");
            }
        }
        return $remainder;    
        */

        return $str;
    }

    function doc(){
        return "<table cellspacing=0 cellpadding=3 border=1>
            <tr>
                <th>Input</th><th>Result</th>
            </tr>
            <tr>
                <td>MSTG<i>ENTER</i></td>
                <td>Toggle membership status for <i>transaction</i>.
                Will make inactive membership active for this transaction
                only. Requires mgr. auth.
                </td>
            </tr>

            </table>";
    }
}


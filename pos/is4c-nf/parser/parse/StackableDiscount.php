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
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\lib\PrehLib;
use COREPOS\pos\parser\Parser;

class StackableDiscount extends Parser 
{
    private $ret;

    function check($str)
    {
        $this->ret = $this->default_json();
        if (substr($str,-2) == "SD"){
            $strl = substr($str,0,strlen($str)-2);
            if (!is_numeric($strl)) {
                return False;
            } elseif ($this->session->get("tenderTotal") != 0) {
                $this->ret['output'] = DisplayLib::boxMsg(
                    _("discount not applicable after tender"),
                    '',
                    false,
                    DisplayLib::standardClearButton()
                );
            } elseif ($strl > 50) {
                $this->ret['output'] = DisplayLib::boxMsg(
                    _("discount exceeds maximum"),
                    '',
                    false,
                    DisplayLib::standardClearButton()
                );
            } elseif ($strl <= 0) {
                $this->ret['output'] = DisplayLib::boxMsg(
                    _("discount must be greater than zero"),
                    '',
                    false,
                    DisplayLib::standardClearButton()
                );
            } elseif ($strl <= 50 and $strl > 0) {
                $existingPD = $this->session->get("percentDiscount");
                $stackablePD = $strl;
                $equivalentPD = ($existingPD + $stackablePD); // sum discounts
                $this->ret = PrehLib::percentDiscount($equivalentPD,$this->ret);
            } else {
                return false;
            }
            return true;
        }
        return false;
    }

    function parse($str)
    {
        return $this->ret;
    }

    function doc(){
        return "<table cellspacing=0 cellpadding=3 border=1>
            <tr>
                <th>Input</th><th>Result</th>
            </tr>
            <tr>
                <td><i>number</i>SD</td>
                <td>Add percent discount in amount
                <i>number</i></td>
            </tr>
            </table>";
    }
}


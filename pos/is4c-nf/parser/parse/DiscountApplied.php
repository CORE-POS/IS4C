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

class DiscountApplied extends Parser 
{
    private $ret;

    function check($str)
    {
        $this->ret = $this->default_json();
        if (substr($str,-2) == "DA"){
            $strl = substr($str,0,strlen($str)-2);
            if (substr($str,0,2) == "VD") {
                $this->ret = PrehLib::percentDiscount(0,$this->ret);
            } elseif (!is_numeric($strl)) {
                return false;
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
            } elseif ($strl < 0) {
                $this->ret['output'] = DisplayLib::boxMsg(
                    _("discount cannot be negative"),
                    '',
                    false,
                    DisplayLib::standardClearButton()
                );
            } elseif ($strl <= 50 and $strl > 0) {
                $this->ret = PrehLib::percentDiscount($strl,$this->ret);
                $this->ret['redraw_footer'] = true;
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
                <td><i>number</i>DA</td>
                <td>Add a percent discount of the specified
                amount <i>number</i></td>
            </tr>
            </table>";
    }
}


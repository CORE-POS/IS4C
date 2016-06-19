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

use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\parser\Parser;

class VirtualCouponParser extends Parser {
    
    function check($str){
        if ($str == "VC"){
            return True;
        }
        return False;
    }

    function parse($str)
    {
        $ret = $this->default_json();

        if (CoreLocal::get("memberID") == 0){
            $ret['output'] = DisplayLib::boxMsg(
                _("Apply member number first"),
                _('No member selected'),
                false,
                array_merge(array('Member Search [ID]' => 'parseWrapper(\'ID\');'), DisplayLib::standardClearButton())
            );
        } else {
            $plugin_info = new VirtualCoupon();
            $ret['main_frame'] = $plugin_info->pluginUrl().'/VirtCoupDisplay.php';
        }
        return $ret;
    }

    function doc(){
        return "<table cellspacing=0 cellpadding=3 border=1>
            <tr>
                <th>Input</th><th>Result</th>
            </tr>
            <tr>
                <td>VC</td>
                <td>
                View virtual coupons for the current member
                </td>
            </tr>
            </table>";
    }
}


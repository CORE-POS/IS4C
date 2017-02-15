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
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\TransRecord;
use COREPOS\pos\parser\Parser;

class AutoTare extends Parser 
{
    function check($str)
    {
        if (substr($str,-2) == "TW") {
            $left = substr($str,0,strlen($str)-2);
            if ($left == "" || (is_numeric($left) && !strstr($left, '.'))) {
                return true;
            }
        }

        return false;
    }

    function parse($str)
    {
        $ret = $this->default_json();

        $left = substr($str,0,strlen($str)-2);
        if ($left == "")
            $left = 1;    

        if (strlen($left) > 4) {
            $ret['output'] = DisplayLib::boxMsg(
                MiscLib::truncate2($left/100) . _(" tare not supported"),
                _('Invalid Tare'),
                false,
                DisplayLib::standardClearButton()
            );
        } elseif ($left/100 > $this->session->get("weight") && $this->session->get("weight") > 0) {
            $ret['output'] = DisplayLib::boxMsg(
                _("Tare cannot be")."<br />"._("greater than item weight"),
                _('Excess Tare'),
                false,
                DisplayLib::standardClearButton()
            );
        } else {
            TransRecord::addTare($left);
            $ret['output'] = DisplayLib::lastpage();
        }

        return $ret;
    }

    function doc(){
        return "<table cellspacing=0 cellpadding=3 border=1>
            <tr>
                <th>Input</th><th>Result</th>
            </tr>
            <tr>
                <td><i>number</i>TW</td>
                <td>Set tare weight to <i>number</i></td>
            </tr>
            <tr>
                <td>TW</td>
                <td>Set tare weight 1. Same as 1TW</td>
            </tr>
            </table>";
    }

}


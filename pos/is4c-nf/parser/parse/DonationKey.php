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

namespace COREPOS\pos\parser\parse;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\DeptLib;
use COREPOS\pos\parser\Parser;

class DonationKey extends Parser 
{
    function check($str)
    {
        if ($str == "RU" || substr($str,-2)=="RU") {
            return true;
        }

        return false;
    }

    function parse($str)
    {
        $dept = $this->session->get('roundUpDept');
        if ($dept === '') {
            $dept = 701;
        }

        $ret = $this->default_json();
        $lib = new DeptLib($this->session);
        if ($str == "RU") {
            Database::getsubtotals();
            $ttl = $this->session->get("amtdue");    
            $next = ceil($ttl);
            $amt = sprintf('%.2f',(($ttl == $next) ? 1.00 : ($next - $ttl)));
            $ret = $lib->deptkey($amt*100, $dept.'0', $ret);
        } else {
            $amt = substr($str,0,strlen($str)-2);
            $ret = $lib->deptkey($amt, $dept.'0', $ret);
        }

        return $ret;
    }

    function doc()
    {
        return "<table cellspacing=0 cellpadding=3 border=1>
            <tr>
                <th>Input</th><th>Result</th>
            </tr>
            <tr>
                <td>DONATE</td>
                <td>
                Round transaction up to next dollar
                with open ring to donation department.
                </td>
            </tr>
            </table>";
    }
}


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

class Refund extends PreParser 
{
    function check($str)
    {
        /* Ignore product searches and comments; they may have all sorts of
         * random character combinations.
         */
        if (
            substr($str, -2, 2) == "PV" ||
            substr($str, 0, 2) == "PV" ||
            substr($str, 0, 2) == "CM"
        ) {
            return false;
        }

        if (strstr($str, 'RF')) {
            // void and refund cannot combine
            if (strstr($str, 'VD')) {
                return false;
            }
            return true;
        }
        return false;
    }

    function parse($str)
    {
        $remainder = "";
        $parts = explode('RF', $str, 2);
        foreach ($parts as $p) {
            $remainder .= $p;
        }
        $this->session->set("refund",1);

        return $remainder;
    }

    function doc(){
        return "<table cellspacing=0 cellpadding=3 border=1>
            <tr>
                <th>Input</th><th>Result</th>
            </tr>
            <tr>
                <td>RF<i>ringable</i>
                OR <i>ringable</i>RF
                </td>
                <td>Refund the specified item(s). <i>Ringable
                </i> can be a single UPC, an open-department
                ring, or a multiple using *</td>
            </tr>
            </table>";
    }
}


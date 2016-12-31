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

class CCMenu extends PreParser 
{
    private $remainder;
    
    function check($str)
    {
        $plugins = $this->session->get('PluginList');
        if ($str == "CC" && is_array($plugins) && in_array('Paycards', $plugins)){
            $this->remainder = "QM1";
            return true;
        } elseif ($str == "MANUALCC") {
            $this->remainder = ("".$this->session->get("runningTotal") * 100)."CC";
            return true;
        }

        return false;
    }

    function parse($str)
    {
        return $this->remainder;
    }

    function doc()
    {
        return "<table cellspacing=0 cellpadding=3 border=1>
            <tr>
                <th>Input</th><th>Result</th>
            </tr>
            <tr>
                <td>>CC</td>
                <td>Open the integrated card menu if
                Paycards is enabled</td>
            </tr>
            <tr>
                <td>MANUALCC</td>
                <td>Tender current totals as credit card</td>
            </tr>
            </table>";
    }
}


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
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\parser\Parser;

class EquityCheck extends Parser 
{
    function check($str)
    {
        if ($str == "EQUITY") {
            return true;
        }

        return false;
    }

    function parse($str)
    {
        $equityPaid = 0;
        $equityBalance = 0;
        $dbc = Database::mDataConnect();
        $dbName = Database::mAltName();

        $query = $dbc->prepare('SELECT payments FROM ' . $dbName . 'equity_live_balance WHERE memnum= ? ');
        $args = $this->session->get('memberID');
        $result = $dbc->execute($query, $args);
        while ($row = $dbc->fetch_row($result)) {
            $equityPaid = $row['payments']; 
        }
        $equityBalance = 10000 - $equityPaid;

        $ret = $this->default_json();
        $title = _('Member #') . $this->session->get('memberID');
        $msg = _("Current amount of Equity paid is ") . $equityPaid . "<br />"
             . _("Amount of Equity left is ") . (100.00 - $equityPaid) . ".";
        $ret['output'] = DisplayLib::boxMsg(
            $msg, 
            $title, 
            true, 
            array_merge(array(_('Tender [Remaining Equity]') => 'parseWrapper(\' ' . $equityBalance . 'DP9910\');'), DisplayLib::standardClearButton())
        );

        return $ret;
    }

    function doc(){
        return "<table cellspacing=0 cellpadding=3 border=1>
            <tr>
                <th>Input</th><th>Result</th>
            </tr>
            <tr>
                <td>BQ</td>
                <td>Display equity paid for
                currently entered member</td>
            </tr>
            </table>";
    }

}


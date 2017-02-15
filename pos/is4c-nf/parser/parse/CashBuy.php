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

class CashBuy extends Parser 
{
    function check($str)
    {
        if ($str == "CASHBUY") {
            return true;
        }

        return false;
    }

    function parse($str)
    {
        $dbc = Database::mDataConnect();
        $query = $dbc->prepare('SELECT payments FROM equity_live_balance WHERE memnum= ? ');
        $args = $this->session->get('memberID');
        $result = $dbc->execute($query, $args);
        while ($row = $dbc->fetch_row($result)) {
            $equityPaid = $row['payments'];
        }
        
        $ret = $this->default_json();
        $title = _('Cash Buy') . $this->session->get('laneno');
        $msg = '
            <form method="post">
                <table>
                    <tr><td align="center">$1</td>
                        <td><input type="text" name="one"></td>
                    <tr><td align="center">$5</td>
                        <td><input type="text" name="five"></td>
                    <tr><td align="center">$10</td>
                        <td><input type="text" name="ten"></td>
                    <tr><td align="center">$20</td>
                        <td><input type="text" name="twenty"></td>
                    <tr><td align="center">Pennies</td>
                        <td><input type="text" name="pennies"></td>
                    <tr><td align="center">Dimes</td>
                        <td><input type="text" name="dimes"></td>
                    <tr><td align="center">Quarters</td>
                        <td><input type="text" name="quarters"></td>
                    <tr><td align="center"></td>
                        <td><input type="submit" value="submit"></td>
                </table>
            </form>
        ';
        $ret['output'] = DisplayLib::boxMsg(
            $msg, 
            $title, 
            true, 
            array_merge(array('Enter' => 'parseWrapper(\'cm ' . 
                'cashbuy ' 
                . $_POST['one'] 
                
                . '\');'), DisplayLib::standardClearButton())
            //array_merge(array('Enter' => 'parseWrapper(\' ' . $equityBalance . 'DP9910\');'), DisplayLib::standardClearButton()) --the orig.
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


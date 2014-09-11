<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op, Duluth, MN

    This file is part of Fannie.

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

class Equity extends MemberModule {

    function showEditForm($memNum, $country="US"){
        global $FANNIE_URL,$FANNIE_TRANS_DB;

        $dbc = $this->db();
        $trans = $FANNIE_TRANS_DB.$dbc->sep();
        
        $infoQ = $dbc->prepare_statement("SELECT payments
                FROM {$trans}equity_live_balance
                WHERE memnum=?");
        $infoR = $dbc->exec_statement($infoQ,array($memNum));
        $equity = 0;
        if ($dbc->num_rows($infoR) > 0) {
            $w = $dbc->fetch_row($infoR);
            $equity = $w['payments'];
        }

        $ret = "<fieldset><legend>Equity</legend>";
        $ret .= "<table class=\"MemFormTable\" 
            border=\"0\">";

        $ret .= "<tr><th>Stock Purhcased</th>";
        $ret .= sprintf('<td>%.2f</td>',$equity);

        $ret .= "<td><a href=\"{$FANNIE_URL}reports/Equity/index.php?memNum=$memNum\">History</a></td></tr>";
        $ret .= "<tr><td><a href=\"{$FANNIE_URL}mem/correction_pages/MemEquityTransferTool.php?memIN=$memNum\">Transfer Equity</a></td>";
        $ret .= "<td><a href=\"{$FANNIE_URL}mem/correction_pages/MemArEquitySwapTool.php?memIN=$memNum\">Convert Equity</a></td></tr>";


        $ret .= "</table></fieldset>";
        return $ret;
    }
}

?>

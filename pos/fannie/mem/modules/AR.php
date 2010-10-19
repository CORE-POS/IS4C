<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op, Duluth, MN

    This file is part of Fannie.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

class AR extends MemberModule {

	function ShowEditForm($memNum){
		global $FANNIE_URL;

		$dbc = $this->db();
		
		$infoQ = sprintf("SELECT c.memDiscountLimit,n.balance
				FROM custdata AS c LEFT JOIN
				newBalanceToday_cust AS n ON
				c.CardNo=n.memnum
				WHERE c.CardNo=%d AND c.personNum=1",$memNum);
		$infoR = $dbc->query($infoQ);
		$infoW = $dbc->fetch_row($infoR);

		$ret = "<fieldset><legend>A/R</legend>";
		$ret .= "<table class=\"MemFormTable\" 
			border=\"0\">";

		$ret .= "<tr><th>Limit</th>";
		$ret .= sprintf('<td><input name="AR_limit" size="4" value="%d" />
				</td>',$infoW['memDiscountLimit']);
		$ret .= "<th>Current Balance</th>";
		$ret .= sprintf('<td>%.2f</td>',$infoW['balance']);	

		$ret .= "<td><a href=\"{$FANNIE_URL}reports/AR/index.php?memNum=$memNum\">History</a></td></tr>";
		$ret .= "<tr><td colspan=\"2\"><a href=\"{$FANNIE_URL}mem/corrections.php?type=ar_transfer&memIN=$memNum\">Transfer A/R</a></td>";
		$ret .= "<td><a href=\"{$FANNIE_URL}mem/corrections.php?type=equity_ar_swap&memIN=$memNum\">Convert A/R</a></td></tr>";


		$ret .= "</table></fieldset>";
		return $ret;
	}

	function SaveFormData($memNum){
		$dbc = $this->db();

		$saveQ = sprintf("UPDATE custdata SET memDiscountLimit=%f
				WHERE CardNo=%d",$_REQUEST['AR_limit'],
				$memNum);
		$test = $dbc->query($saveQ);
		
		if ($test === False)
			return 'Error: Problme saving A/R limit<br />';
		else
			return '';
	}
}

?>

<?php

class Equity extends MemberModule {

	function ShowEditForm($memNum){
		global $FANNIE_URL;

		$dbc = $this->db();
		
		$infoQ = sprintf("SELECT payments
				FROM newBalanceStockToday_test
				WHERE memnum=%d",$memNum);
		$infoR = $dbc->query($infoQ);
		$equity = 0;
		if ($dbc->num_rows($infoR) > 0)
			$equity = array_pop($dbc->fetch_row($infoR));

		$ret = "<fieldset><legend>Equity</legend>";
		$ret .= "<table class=\"MemFormTable\" 
			border=\"0\">";

		$ret .= "<tr><th>Stock Purhcased</th>";
		$ret .= sprintf('<td>%.2f</td>',$equity);

		$ret .= "<td><a href=\"{$FANNIE_URL}reports/Equity/index.php?memNum=$memNum\">History</a></td></tr>";
		$ret .= "<tr><td><a href=\"{$FANNIE_URL}mem/corrections.php?type=equity_transfer&memIN=$memNum\">Transfer Equity</a></td>";
		$ret .= "<td><a href=\"{$FANNIE_URL}mem/corrections.php?type=equity_ar_swap&memIN=$memNum\">Convert Equity</a></td></tr>";


		$ret .= "</table></fieldset>";
		return $ret;
	}
}

?>

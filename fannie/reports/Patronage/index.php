<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');

$page_title = "Fannie : Patronage Report";
$header = "Patronage Report";
if (!isset($_REQUEST['excel']))
	include($FANNIE_ROOT.'src/header.html');
else {
	header('Content-Type: application/ms-excel');
	header('Content-Disposition: attachment; filename="patronage.xls"');
}

$fy = isset($_REQUEST['fy'])?$_REQUEST['fy']:'';
if (!isset($_REQUEST['excel'])){
	$fyQ = $dbc->prepare_statement("SELECT FY FROM patronage GROUP BY FY ORDER BY FY DESC");
	$fyR = $dbc->exec_statement($fyQ);
	echo '<select onchange="location=\'index.php?fy=\'+this.value;">';
	echo '<option value="">Select FY</option>';
	while($fyW = $dbc->fetch_row($fyR)){
		printf('<option value="%d" %s>%d</option>',
			$fyW['FY'],
			($fyW['FY']==$fy?'selected':''),
			$fyW['FY']
		);
	}
	echo '</select>';
	echo '<hr />';
}

if ($fy != ""){
	$pQ = $dbc->prepare_statement("SELECT cardno,purchase,discounts,rewards,net_purch,
		tot_pat,cash_pat,equit_pat,m.type,m.ttl FROM patronage as p
		LEFT JOIN patronageRedemption AS m ON p.cardno=m.card_no
		AND p.FY=m.fy
		WHERE p.FY=? ORDER BY cardno");
	$pR = $dbc->exec_statement($pQ,array($fy));
	if (!isset($_REQUEST['excel']))
		printf('<a href="index.php?fy=%d&excel=yes">Download Report</a>',$fy);
	echo '<table cellspacing="0" cellpadding="4" border="1">';
	echo '<tr><th colspan="10">Patronage FY'.$fy.'</th></tr>';
	echo '<tr><th>&nbsp;</th><th colspan="4">Spending</th><th colspan="3">Rebate</th>
		<th colspan="2">Redeemed</th></tr>';
	echo '<tr><th>#</th><th>Gross</th><th>Discounts</th><th>Rewards</th>
		<th>Net</th><th>Cash</th><th>Equity</th><th>TTL</th>
		<th>Type</th><th>Amt</th></tr>';
	while($pW = $dbc->fetch_row($pR)){
		printf('<tr>
			<td>%d</td>
			<td>%.2f</td>
			<td>%.2f</td>
			<td>%.2f</td>
			<td>%.2f</td>
			<td>%.2f</td>
			<td>%.2f</td>
			<td>%.2f</td>
			<td>%s</td>
			<td>%.2f</td>
			</tr>',
			$pW['cardno'],$pW['purchase'],-1*$pW['discounts'],
			-1*$pW['rewards'],$pW['net_purch'],$pW['cash_pat'],
			$pW['equit_pat'],$pW['tot_pat'],
			($pW['type']==''?'n/a':strtoupper($pW['type'])),
			-1*$pW['ttl']
		);
	}
	echo '</table>';
}

if (!isset($_REQUEST['excel']))
	include($FANNIE_ROOT.'src/footer.html');
?>

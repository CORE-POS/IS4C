<?php

include('../../config.php');
include($FANNIE_ROOT.'src/trans_connect.php');

$memNum = isset($_REQUEST['memNum'])?(int)$_REQUEST['memNum']:0;

$header = "A/R History for Member $memNum";
$page_title = "Fannie :: A/R History";
include($FANNIE_ROOT.'src/header.html');

$q = $dbc->prepare_statement("select charges,trans_num,payments,
		year(tdate),month(tdate),day(tdate)
		from ar_history AS s 
		WHERE s.card_no=? ORDER BY tdate DESC");
if ($memNum == 0){
	echo "<i>Error: no member specified</i>";
}
else {
	echo "<table cellpadding=\"4\" cellspacing=\"0\" border=\"1\">";
	echo "<tr><th>Date</th><th>Receipt</th><th>Amount</th><th>Type</th></tr>";
	$r = $dbc->exec_statement($q,array($memNum));
	$items = 0;
	$total = 0;
	while($w = $dbc->fetch_row($r)){
		printf('<tr><td>%d/%d/%d</td><td>
			<a href="%sadmin/LookupReceipt/RenderReceiptPage.php?year=%d&month=%d&day=%d&receipt=%s">%s</a>
			</td>
			<td style="text-align:right;">%.2f</td>
			<td>%s</td></tr>',
			$w[4],$w[5],$w[3],
			$FANNIE_URL,$w[3],$w[4],$w[5],$w[1],$w[1],
			($w['charges']!=0?$w['charges']:$w['payments']),
			($w['charges']!=0?'Charge':'Payment'));
		$items++;
		$total += ($w['charges']!=0?$w['charges']:$w['payments']);
	}
	printf('<tr>
		<td style="font-weight:bold; text-align:right;">%s</td>
		<td style="text-align:right;">%d</td>
		<td style="text-align:right;">%.2f</td>
		<td>%s</td></tr>',
		'Totals:',
		$items,
		$total,
		' &nbsp; ');
	echo "</table>";
}

include($FANNIE_ROOT.'src/footer.html');

?>

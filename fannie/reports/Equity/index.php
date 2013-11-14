<?php

include('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');

$memNum = isset($_REQUEST['memNum'])?(int)$_REQUEST['memNum']:'';
header('Location: EquityReport.php?memNum='.$memNum);
exit;

$header = "Equity History for Member $memNum";
$page_title = "Fannie :: Equity History";
include($FANNIE_ROOT.'src/header.html');

$trans = $FANNIE_TRANS_DB;
if ($FANNIE_SERVER_DBMS=='MSSQL') $trans .= ".dbo";

$q = $dbc->prepare_statement("select stockPurchase,trans_num,dept_name,
		year(tdate),month(tdate),day(tdate)
		from {$trans}.stockpurchases AS s LEFT JOIN
		departments AS d ON s.dept=d.dept_no
		WHERE s.card_no=? ORDER BY tdate DESC");
if ($memNum == 0){
	echo "<i>Error: no member specified</i>";
}
else {
	echo "<table cellpadding=\"4\" cellspacing=\"0\" border=\"1\">";
	echo "<tr><th>Date</th><th>Receipt</th><th>Amount</th><th>Type</th></tr>";
	$r = $dbc->exec_statement($q,array($memNum));
	while($w = $dbc->fetch_row($r)){
		printf('<tr><td>%d/%d/%d</td><td>
			<a href="%sadmin/LookupReceipt/RenderReceiptPage.php?year=%d&month=%d&day=%d&receipt=%s">%s</a>
			</td><td>%.2f</td><td>%s</td></tr>',
			$w[4],$w[5],$w[3],$FANNIE_URL,$w[3],$w[4],$w[5],$w[1],$w[1],
			$w[0],$w[2]);
	}
	echo "</table>";
}

include($FANNIE_ROOT.'src/footer.html');

?>

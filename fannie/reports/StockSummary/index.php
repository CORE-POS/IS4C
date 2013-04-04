<?php

require('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');

$trans = $FANNIE_TRANS_DB;
if ($FANNIE_SERVER_DBMS == "MSSQL") $trans .= ".dbo";

$q = $dbc->prepare_statement("select 
	card_no,
	LastName,FirstName,Type,
	sum(case when tdate <= '2005-11-26 23:59:59' then stockPurchase else 0 end) as unknown,
	sum(case when tdate > '2005-11-26 23:59:59' and dept=992 then stockPurchase else 0 end) as classA,
	sum(case when tdate > '2005-11-26 23:59:59' and dept=991 then stockPurchase else 0 end) as classB
	from $trans.stockpurchases as s
	left join custdata as c
	on s.card_no=c.CardNo and c.personNum=1
	where card_no > 0
	group by card_no,LastName,FirstName,Type
	order by card_no");
$r = $dbc->exec_statement($q);

if (!isset($_REQUEST['excel']))
	echo "<a href=index.php?excel=yes>Save as Excel</a>";

ob_start();

echo "<table cellspacing=0 cellpadding=4 border=1>
<tr><th>Mem#</th><th>Name</th><th>Status</th><th>A</th><th>B</th>
<th>Unknown</th></tr>";
$types = array('PC'=>'Member','REG'=>'NonMember',
	'TERM'=>'Termed','INACT'=>'Inactive',
	'INACT2'=>'Term Pending');
while($w = $dbc->fetch_row($r)){
	printf("<tr><td>%d</td><td>%s, %s</td><td>%s</td>
		<td>%.2f</td><td>%.2f</td><td>%.2f</td></tr>",
		$w['card_no'],$w['LastName'],$w['FirstName'],
		$types[$w['Type']],$w['classA'],$w['classB'],
		$w['unknown']);
}
echo "</table>";

$page = ob_get_contents();
ob_end_clean();

if (!isset($_REQUEST['excel']))
	echo $page;
else {
	include($FANNIE_ROOT.'src/ReportConvert/HtmlToArray.php');
	include($FANNIE_ROOT.'src/ReportConvert/ArrayToXls.php');
	header('Content-Type: application/ms-excel');
	header('Content-Disposition: attachment; filename="StockSummary'.date('Y-m-d').'.xls"');

	//$array = HtmlToArray($page);
	//$xls = ArrayToXls($array);
	echo $page;
}

?>

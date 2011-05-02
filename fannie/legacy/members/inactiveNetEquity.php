<?php

include('../../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include('../db.php');
include('functMem.php');

$query = "SELECT c.cardno,c.lastname+', '+c.firstname,m.start_date,c.balance,s.payments,
		s.payments - c.balance
		FROM custData as c LEFT JOIN newBalanceStockToday_test
		AS s ON c.cardno = s.memnum LEFT JOIN memDates
		AS m on c.cardno = m.card_no
		WHERE c.personnum = 1 AND c.type = 'INACT'
		ORDER BY convert(int,c.cardno)";
$result = $sql->query($query);

if (isset($_GET['excel'])){
	header('Content-Type: application/ms-excel');
	header('Content-Disposition: attachment; filename="InactiveNetEquity.xls"');
}
else
	echo "<a href=inactiveNetEquity.php?excel=yes>Save to Excel</a><p />";

echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><th>Account#</th><th>Name</th><th>Start date</th>";
echo "<th>AR Balance</th><th>Stock Balance</th>";
echo "<th>Net Equity</th></tr>";
$colors = array("#ffffcc","#ffffff");
$c = 0;
while($row = $sql->fetch_row($result)){
	echo "<tr>";
	for($i=0;$i<6;$i++)
		echo "<td bgcolor=$colors[$c]>$row[$i]</td>";
	echo "</tr>";
	$c = ($c+1)%2;
}
echo "</table>";

?>

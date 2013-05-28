<?php

include('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');


if (isset($_REQUEST['submit'])){
	$trans = $FANNIE_TRANS_DB;
	if ($FANNIE_SERVER_DBMS=='MSSQL') $trans .= ".dbo";

	$type_restrict = "c.Type IN ('PC')";
	if ($_REQUEST['memtypes'] == 2)
		$type_restrict = "c.Type IN ('PC','INACT','INACT2')";
	elseif ($_REQUEST['memtypes'] == 3)
		$type_restrict = "c.Type IN ('PC','INACT','INACT2','TERM')";

	$equity_restrict = "(n.payments > 0)";
	if ($_REQUEST['owed'] == 2)
		$equity_restrict = "(n.payments > 0 AND n.payments < 100)";

	$q = $dbc->prepare_statement("SELECT n.memnum,c.LastName,c.FirstName,n.payments,m.end_date
		FROM custdata AS c LEFT JOIN {$trans}.newBalanceStockToday_test as n ON
		n.memnum=c.CardNo AND c.personNum=1
		LEFT JOIN memDates as m ON c.CardNo=m.card_no
		WHERE $type_restrict AND $equity_restrict
		ORDER BY n.memnum");

	echo "<table cellpadding=\"4\" cellspacing=\"0\" border=\"1\">";
	echo "<tr><th>Mem #</th><th>Last Name</th><th>First Name</th><th>Equity</th><th>Due Date</th></tr>";
	$r = $dbc->exec_statement($q);
	while($w = $dbc->fetch_row($r)){
		echo "<tr>";
		printf('<td><a href="%s%d">%d</a></td>',$FANNIE_URL."reports/Equity/index.php?memNum=",$w['memnum'],$w['memnum']);
		echo "<td>".$w['LastName']."</td>";
		echo "<td>".$w['FirstName']."</td>";
		printf('<td>%.2f</td>',$w['payments']);
		echo "<td>".$w['end_date']."</td>";
		echo "</tr>";
	}
	echo "</table>";
	exit;
}

$header = "Current Member Equity";
$page_title = "Fannie :: Equity History";
include($FANNIE_ROOT.'src/header.html');
?>
<form action="index.php" method="get">
<b>Active status</b>:
<select name="memtypes">
	<option value=1>Active Owners</option>
	<option value=2>Non-termed Owners</option>
	<option value=3>All Owners</option>
</select>
<br /><br />
<b>Equity balance</b>:
<select name="owed">
	<option value=1>Any balance</option>
	<option value=2>less than $100</option>
</select>
<br /><br />
<input type="submit" name="submit" value="Get Report" />
</form>
<?php
include($FANNIE_ROOT.'src/footer.html');

?>

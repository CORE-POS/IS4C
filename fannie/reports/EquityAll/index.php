<?php

include('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');

$memNum = isset($_REQUEST['memNum'])?(int)$_REQUEST['memNum']:0;

$header = "Current Member Equity";
$page_title = "Fannie :: Equity History";
include($FANNIE_ROOT.'src/header.html');

$q = "SELECT n.memnum,c.LastName,c.FirstName,n.payments
	FROM newBalanceStockToday_test as n LEFT JOIN 
	custdata AS c ON n.memnum=c.CardNo AND c.personNum=1
	ORDER BY n.memnum";

echo "<table cellpadding=\"4\" cellspacing=\"0\" border=\"1\">";
echo "<tr><th>Mem #</th><th>Last Name</th><th>First Name</th><th>Equity</th></tr>";
$r = $dbc->query($q);
while($w = $dbc->fetch_row($r)){
	echo "<tr>";
	printf('<td><a href="%s%d">%d</a></td>',$FANNIE_URL."reports/Equity/index.php?memNum=",$w['memnum'],$w['memnum']);
	echo "<td>".$w['LastName']."</td>";
	echo "<td>".$w['FirstName']."</td>";
	printf('<td>%.2f</td>',$w['payments']);
	echo "</tr>";
}
echo "</table>";

include($FANNIE_ROOT.'src/footer.html');

?>

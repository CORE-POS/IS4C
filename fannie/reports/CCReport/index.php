<?php
include('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');
include($FANNIE_ROOT.'src/select_dlog.php');

$header = "Credit Card Report (supplemental)";
$page_title = "Fannie : Integrated CC Report";
include($FANNIE_ROOT.'src/header.html');
?>
<style type=text/css>
.hilite {
	background: #ffffcc;
}
</style>
<?php

$date = date("Y-m-d",mktime(0,0,0,date("m"),date("d")-1,date("Y")));
if (isset($_GET['date']))
	$date = $_GET['date'];

echo "<form action=index.php method=get>
<b>Date</b>: <input type=text name=date /> <input type=submit value=Submit />
</form>";

echo "<h3>Integrated CC Report for $date</h3>";

$query = "SELECT q.datetime,q.laneno,q.cashierno,q.transno,q.amount,
	q.PAN, datepart(yy,q.datetime),datepart(dd,q.datetime),
	datepart(mm,q.datetime),r.xresultmessage
	FROM efsnetRequest q LEFT JOIN efsnetResponse r
	on r.date=q.date and r.cashierno=q.cashierno and 
	r.transno=q.transno and r.laneno=q.laneno
	and r.transid=q.transid
	left join efsnetRequestMod m
	on m.date = q.date and m.cashierno=q.cashierno and
	m.transno=q.transno and m.laneno=q.laneno
	and m.transid=q.transid
	where datediff(dd,q.datetime,'$date')=0
	and q.laneno <> 99 and q.cashierno <> 9999
	and m.transid is null
	order by q.datetime,q.laneno,q.transno,q.cashierno";	
$result = $dbc->query($query);

echo "<table cellspacing=0 cellpadding=4 border=1>
<tr><th>Date &amp; Time</th><th>Card</th><th>Amount</th>
<th>Response</th><th>POS receipt</th></tr>";
$sum = 0;
$htable = array();
while($row = $dbc->fetch_row($result)){
	printf("<tr %s><td>%s</td><td>%s</td><td>%.2f</td>
		<td>%s</td>
		<td><a href=\"{$FANNIE_URL}admin/LookupReceipt/reprint.php?month=%d&year=%d&day=%d&receipt=%s\">
		POS receipt</td></tr>", 
		(isset($htable[$row[4]."+".$row[5]])||$row[9]=="")?"class=hilite":"",
		$row[0],$row[5],$row[4],$row[9],
		$row[8],$row[6],$row[7],($row[2]."-".$row[1]."-".$row[3]));
	if (strstr($row[9],"APPROVED") || $row[9] == "" || strstr($row[9],"PENDING")){
		$sum += $row[4];
		$htable[$row[4]."+".$row[5]] = 1;
	}
}
printf("<tr><th colspan=2>Total Approved</th><td>%.2f</td>",$sum);
echo "<td colspan=2>&nbsp;</td></tr>";
echo "</table>";

?>

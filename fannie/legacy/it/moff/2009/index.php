<?php

include('../../../sql/SQLManager.php');
include('../../../db.php');

$dlog = DTransactionsModel::selectDlog('2009-08-08');

$query1 = "SELECT u.likecode,l.likecodedesc,sum(d.quantity),sum(d.total)
	FROM $dlog as d LEFT JOIN upclikemoff as u
	ON d.upc = u.upc LEFT JOIN likecodes as l
	ON u.likecode=l.likecode
	WHERE u.upc is not null
	AND d.register_no=30
	AND d.trans_type in ('I','D')
	GROUP BY u.likecode,l.likecodedesc
	ORDER BY sum(d.total) desc";

$query2 = "SELECT d.upc,p.description,sum(d.quantity),sum(d.total)
	FROM $dlog as d LEFT JOIN products as p
	ON p.upc=d.upc LEFT JOIN upclikemoff as u
	ON d.upc=u.upc
	WHERE u.upc IS NULL
	AND d.register_no=30
	AND d.trans_type in ('I','D')
	GROUP BY d.upc,p.description
	ORDER BY sum(d.total) desc";

if (isset($_GET['excel'])){
	header('Content-Type: application/ms-excel');
	header('Content-Disposition: attachment; filename="moff2009movement.xls"');
}
else{
	echo "<a href=index.php?excel=yes>Save as Excel</a><p />";
}

?>
<h3>Likecoded sales</h3>
<table cellspacing=0 cellpadding=4 border=1>
<tr><th>LC</th><th>Desc</th><th>Qty</th><th>$</th></tr>
<?php
$result = $sql->query($query1);
while($row = $sql->fetch_row($result)){
	printf("<tr><td>%s</td><td>%s</td><td>%.2f</td><td>%.2f</td></tr>",
		$row[0],$row[1],$row[2],$row[3]);
}
?>
</table>
<h3>Non-likecoded sales</h3>
<table cellspacing=0 cellpadding=4 border=1>
<tr><th>LC</th><th>Desc</th><th>Qty</th><th>$</th></tr>
<?php
$result = $sql->query($query2);
while($row = $sql->fetch_row($result)){
	printf("<tr><td>%s</td><td>%s</td><td>%.2f</td><td>%.2f</td></tr>",
		$row[0],$row[1],$row[2],$row[3]);
}
?>
</table>

<?php
include('../../config.php');
header("Location: {$FANNIE_URL}reports/ProductMovement/");
exit;

include('../select_dlog.php');
if (!class_exists("SQLManager")) require_once('../sql/SQLManager.php');

include('../db.php');

if (isset($_GET['upc'])){
	$upc = str_pad($upc,13,'0',STR_PAD_LEFT);
	$date1 = $_GET['date1'];
	$date2 = $_GET['date2'];
	$dlog = select_dlog($date1,$date2);
	//echo $dlog."<br />";
	
	$q = "select year(tdate),month(tdate),day(tdate),sum(quantity),sum(total)
		from $dlog as d where
		datediff(dd,tdate,'$date1') <= 0 and
		datediff(dd,tdate,'$date2') >= 0 and
		upc = '$upc'
		group by year(tdate),month(tdate),day(tdate)
		order by year(tdate),month(tdate),day(tdate)";
	//echo $q."<br />";
	$r = $sql->query($q);
	
	$out = "Sales report for item: $upc<br />";
	$out .= "From $date1 to $date2<br />";
	$out .= "<table border=1 cellpadding=2 cellspacing=2>";
	$out .= "<tr><th>Date</th><th>Qty</th><th>Sales</th></tr>";
	while ($w = $sql->fetch_array($r))
		$out .= "<tr><td>$w[0]-$w[1]-$w[2]</td><td>$w[3]</td><td>$w[4]</td></tr>";
	$out .= "</table>";
	
	header('Content-Type: application/ms-excel');
	header('Content-Disposition: attachment; filename="dailySales-'.$upc.'.xls"');
	
	echo $out;
	
}
else {
?>
<html><head><title>Single UPC movement</title>
<link href="../CalendarControl/CalendarControl.css"
      rel="stylesheet" type="text/css">
<script src="../CalendarControl/CalendarControl.js"
        language="javascript"></script>
</head>
<form action=dailySalesUPC.php method=get>
<table>
<tr><td>UPC</td><td><input type=text name=upc /></td></tr>
<tr><td>Start date</td><td><input type=text name=date1 onfocus="showCalendarControl(this);" /></td></tr>
<tr><td>End date</td><td><input type=text name=date2 onfocus="showCalendarControl(this);" /></td></tr>
</table>
<input type=submit value=Submit />
</form>

<?php	
}
?>

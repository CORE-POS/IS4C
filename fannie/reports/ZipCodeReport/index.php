<?php
include('../../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
$dbc = FannieDB::get($FANNIE_OP_DB);

if (isset($_GET['excel'])){
	header('Content-Type: application/ms-excel');
	header('Content-Disposition: attachment; filename="zipReport.xls"');
}

?>
<html>
<head>
<title>Zip Code Report</title>
<?php
if (!isset($_GET['excel'])){
?>
<link href="<?php echo $FANNIE_URL; ?>src/style.css"
      rel="stylesheet" type="text/css">
<script src="<?php echo $FANNIE_URL; ?>src/CalendarControl.js"
        language="javascript"></script>
<?php
}
?>
</head>
<?php
if (!isset($_GET['excel'])){
?>
<form method=get action=index.php>
<table>
<tr>
<td>Start date (YYYY-MM-DD):</td><td><input type=text name=startDate onfocus="showCalendarControl(this);" /></td>
</tr><tr>
<td>End date (YYYY-MM-DD):</td><td><input type=text name=endDate onfocus="showCalendarControl(this);" /></td>
</tr><tr>
<td>Limit (zero for all):</td><td><input type=text name=limit value=0 /></td>
</tr>
</table>
<input type=submit value=Submit /> <input type=checkbox name=excel /> Excel
 <input type=checkbox name=mem11 /> Omit Member #11
</form>
<?php
}

if (isset($_GET['startDate'])){
	$startDate = $_GET['startDate']." 00:00:00";
	$endDate = $_GET['endDate']." 23:59:59";
	$limit = $_GET['limit'];
	
	$mem11Str = "";
	if (isset($_GET['mem11']))
		$mem11Str = " and d.card_no <> 11 ";


	$dlog = DTransactionsModel::selectDlog($startDate);

	$fetchQ = $dbc->prepare_statement("select d.card_no,sum(d.total),
		case when m.zip='' then 'None Given' else m.zip end
		from $dlog as d left join meminfo as m
		on d.card_no = m.card_no 
		where trans_type='I' $mem11Str
		d.tdate BETWEEN ? AND ?
		group by d.card_no,m.zip
		order by sum(d.total) desc");
	//echo $fetchQ."<br />";
	$fetchR = $dbc->exec_statement($fetchQ,array($startDate,$endDate));

	echo "<table cellspacing=0 cellpadding=4 border=1>";
	echo "<tr><th>Mem No.</th><th>Total</th><th>Zipcode</th></tr>";
	while ($fetchW = $dbc->fetch_array($fetchR)){
		echo "<tr>";
		echo "<td>$fetchW[0]</td><td>$fetchW[1]</td><td>$fetchW[2]</td>";
		echo "</tr>";
	}
	echo "</table>";
}
?>
